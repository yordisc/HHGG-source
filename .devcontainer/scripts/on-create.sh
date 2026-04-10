#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# on-create.sh
# Hook de inicializacion para el Codespace actual.
# Instala soporte MySQL/MariaDB y deja el proyecto listo para desarrollo.
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

MYSQL_READY=1

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Instituto de Certificaciones Dudosas™ — Setup inicial      ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

WORKSPACE_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$WORKSPACE_DIR"

run_privileged() {
  if [ "$(id -u)" -eq 0 ]; then
    "$@"
  elif command -v sudo >/dev/null 2>&1; then
    sudo "$@"
  else
    return 1
  fi
}

has_mysql_driver() {
  php -m 2>/dev/null | grep -qi '^pdo_mysql$'
}

disable_broken_apt_sources() {
  for source_file in /etc/apt/sources.list.d/* /etc/apt/sources.list; do
    [ -e "$source_file" ] || continue

    if grep -qi 'dl.yarnpkg.com/debian' "$source_file" 2>/dev/null; then
      echo "   • Desactivando repositorio roto de Yarn: $(basename "$source_file")"
      run_privileged sed -i 's|^deb \(.*dl\.yarnpkg\.com/debian.*\)$|# disabled by on-create.sh: deb \1|g' "$source_file"
    fi
  done
}

install_mysql_server() {
  echo "   • Instalando servidor y cliente MySQL..."
  if ! run_privileged env DEBIAN_FRONTEND=noninteractive apt-get update; then
    echo "   ⚠ No se pudo actualizar apt antes de instalar MySQL"
    MYSQL_READY=0
    return 1
  fi

  if ! run_privileged env DEBIAN_FRONTEND=noninteractive apt-get install -y default-mysql-server default-mysql-client; then
    echo "   ⚠ Fallo al instalar el servidor/cliente MySQL"
    MYSQL_READY=0
    return 1
  fi
}

install_mysql_php_driver() {
  if has_mysql_driver; then
    echo "   ✓ Driver PDO MySQL ya disponible"
    return 0
  fi

  echo "   ⚠ El driver PDO MySQL no esta disponible en la imagen del contenedor"
  echo "   • Rebuild necesario: revisa .devcontainer/Dockerfile"
  return 1
}

install_mysql() {
  echo "► Instalando soporte MySQL para Codespaces..."

  if command -v apt-get >/dev/null 2>&1; then
    disable_broken_apt_sources
    if ! install_mysql_server; then
      return 0
    fi

    if ! install_mysql_php_driver; then
      echo "   ⚠ Fallo al instalar el driver PDO MySQL"
      MYSQL_READY=0
      return 0
    fi
  else
    echo "   ⚠ Este contenedor solo soporta Debian/apt para el setup inicial"
    MYSQL_READY=0
    return 0
  fi

  if ! has_mysql_driver; then
    echo "   ⚠ El driver PDO MySQL no quedo disponible tras la instalacion"
    MYSQL_READY=0
    return 0
  fi

  echo "   ✓ Soporte MySQL instalado"
}

start_mysql_service() {
  if [ "$MYSQL_READY" -ne 1 ]; then
    echo "► Saltando inicio de MySQL (instalacion no disponible)"
    return 0
  fi

  echo "► Iniciando servicio MySQL/MariaDB..."

  if run_privileged service mysql start >/dev/null 2>&1; then
    :
  elif run_privileged service mariadb start >/dev/null 2>&1; then
    :
  else
    echo "   ⚠ No se pudo iniciar el servicio MySQL/MariaDB"
    MYSQL_READY=0
    return 0
  fi

  echo "   ✓ Servicio MySQL/MariaDB iniciado"
}

wait_for_mysql() {
  if [ "$MYSQL_READY" -ne 1 ]; then
    echo "► Saltando verificacion de MySQL"
    return 0
  fi

  echo "► Esperando a que MySQL responda..."

  for _ in $(seq 1 30); do
    if mysqladmin ping -h 127.0.0.1 -u root --silent >/dev/null 2>&1; then
      echo "   ✓ MySQL disponible"
      return 0
    fi
    sleep 1
  done

  echo "   ⚠ MySQL no respondió a tiempo"
  MYSQL_READY=0
  return 0
}

configure_mysql_database() {
  if [ "$MYSQL_READY" -ne 1 ] || ! command -v mysql >/dev/null 2>&1; then
    echo "► Saltando creacion de DB (MySQL no disponible)"
    return 0
  fi

  echo "► Creando base de datos y usuario de desarrollo..."

  if ! mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS certificados_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON certificados_dev.* TO 'laravel'@'%';
FLUSH PRIVILEGES;
SQL
  then
    echo "   ⚠ No se pudo configurar MySQL automaticamente"
    MYSQL_READY=0
    return 0
  fi

  echo "   ✓ Base de datos y usuario listos"
}

ensure_env() {
  if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    cp .env.example .env
    echo "   .env creado desde .env.example"
  fi

  if [ -f ".env" ]; then
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env || true
    sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env || true
    sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env || true
    sed -i 's/^DB_DATABASE=.*/DB_DATABASE=certificados_dev/' .env || true
    sed -i 's/^DB_USERNAME=.*/DB_USERNAME=laravel/' .env || true
    sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=secret/' .env || true
    sed -i 's/^CACHE_STORE=.*/CACHE_STORE=database/' .env || true
    sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env || true
    sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/' .env || true
  fi
}

install_dependencies() {
  if [ -f composer.json ]; then
    echo "► Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist
  fi

  if [ -f package.json ]; then
    echo "► Instalando dependencias de Node..."
    npm install
  fi
}

prepare_database() {
  php artisan key:generate --force --no-interaction 2>/dev/null || true
  php artisan cache:table 2>/dev/null || true
  php artisan session:table 2>/dev/null || true
  php artisan migrate --no-interaction --force 2>/dev/null || true
}

cleanup() {
  php artisan config:clear 2>/dev/null || true
  php artisan route:clear 2>/dev/null || true
  php artisan view:clear 2>/dev/null || true

  grep -q '^\.env$' .gitignore 2>/dev/null || echo ".env" >> .gitignore
  grep -q '^\.env\.local$' .gitignore 2>/dev/null || echo ".env.local" >> .gitignore
  grep -q '^\.env\.production$' .gitignore 2>/dev/null || echo ".env.production" >> .gitignore
}

echo "► Preparando entorno..."
install_mysql
if [ "$MYSQL_READY" -ne 1 ]; then
  echo "   ✗ No se pudo preparar MySQL local; abortando setup inicial."
  exit 1
fi
start_mysql_service
wait_for_mysql
ensure_env

echo "► Instalando dependencias del proyecto..."
install_dependencies

configure_mysql_database

echo "► Preparando base de datos y cache..."
prepare_database

echo "► Limpiando cache y ajustando gitignore..."
cleanup

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Setup inicial completado                                   ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
