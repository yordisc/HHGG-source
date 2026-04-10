#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# on-create.sh
# Hook de inicializacion para el Codespace actual.
# Instala soporte MySQL/MariaDB y deja el proyecto listo para desarrollo.
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Instituto de Certificaciones Dudosas™ — Setup inicial      ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

WORKSPACE_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$WORKSPACE_DIR"

install_mysql() {
  echo "► Instalando soporte MySQL para Codespaces..."

  if command -v apt-get >/dev/null 2>&1; then
    apt-get update -y
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
      default-mysql-server default-mysql-client php-mysql
  elif command -v apk >/dev/null 2>&1; then
    apk add --no-cache mariadb mariadb-client php82-mysqli php82-pdo_mysql || apk add --no-cache mariadb mariadb-client php-mysqli php-pdo_mysql
  else
    echo "   ⚠ No se pudo detectar gestor de paquetes para instalar MySQL"
    return 1
  fi

  echo "   ✓ Soporte MySQL instalado"
}

start_mysql_service() {
  echo "► Iniciando servicio MySQL/MariaDB..."

  if service mysql start >/dev/null 2>&1; then
    :
  elif service mariadb start >/dev/null 2>&1; then
    :
  else
    echo "   ⚠ No se pudo iniciar el servicio MySQL/MariaDB"
    return 1
  fi

  echo "   ✓ Servicio MySQL/MariaDB iniciado"
}

wait_for_mysql() {
  echo "► Esperando a que MySQL responda..."

  for _ in $(seq 1 30); do
    if mysqladmin ping -h 127.0.0.1 -u root --silent >/dev/null 2>&1; then
      echo "   ✓ MySQL disponible"
      return 0
    fi
    sleep 1
  done

  echo "   ⚠ MySQL no respondió a tiempo"
  return 1
}

configure_mysql_database() {
  echo "► Creando base de datos y usuario de desarrollo..."

  mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS certificados_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON certificados_dev.* TO 'laravel'@'%';
FLUSH PRIVILEGES;
SQL

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
