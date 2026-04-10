#!/usr/bin/env sh
set -eu

PROJECT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
ENV_FILE="$PROJECT_DIR/.env"
SUDO=""

log() {
  printf '%s\n' "$1"
}

fail() {
  log "[ERROR] $1"
  exit 1
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    fail "Falta el comando requerido: $1"
  fi
}

detect_sudo() {
  if [ "$(id -u)" -eq 0 ]; then
    SUDO=""
    return
  fi

  if command -v sudo >/dev/null 2>&1; then
    SUDO="sudo"
    return
  fi

  SUDO=""
}

run_privileged() {
  if [ "$(id -u)" -eq 0 ]; then
    "$@"
    return
  fi

  if [ -n "$SUDO" ]; then
    $SUDO "$@"
    return
  fi

  fail "Se requieren privilegios de administrador para instalar dependencias del sistema. Ejecuta el script con sudo o instala php-mysql manualmente."
}

has_mysql_driver() {
  php -m 2>/dev/null | grep -qi '^pdo_mysql$'
}

install_mysql_driver() {
  if has_mysql_driver; then
    log "[INFO] Driver PDO MySQL ya disponible."
    return
  fi

  log "[INFO] Falta el driver PDO MySQL; intentando instalarlo..."

  if ! command -v apt-get >/dev/null 2>&1; then
    fail "Este flujo está pensado para Debian/apt. Si necesitas otra base, usa el Dockerfile correspondiente."
  fi

  if ! has_mysql_driver; then
    fail "El driver PDO MySQL debe venir preinstalado en la imagen del devcontainer. Rebuild de la imagen requerido."
  fi

  log "[OK] Driver PDO MySQL instalado correctamente."
}

ensure_env_file() {
  if [ ! -f "$ENV_FILE" ]; then
    log "[INFO] Creando .env desde .env.example..."
    cp "$PROJECT_DIR/.env.example" "$ENV_FILE"
  fi
}

ensure_app_key() {
  if ! grep -q '^APP_KEY=base64:.*' "$ENV_FILE" 2>/dev/null; then
    log "[INFO] Generando APP_KEY..."
    php artisan key:generate --force --no-interaction >/dev/null
  fi
}

ensure_mysql_database() {
  if ! command -v mysql >/dev/null 2>&1; then
    log "[WARN] mysql client no disponible; se asume que la base certificados_dev ya existe."
    return
  fi

  log "[INFO] Verificando que MySQL responda..."
  if ! mysql -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "${DB_USERNAME:-laravel}" -p"${DB_PASSWORD:-secret}" -e 'SELECT 1;' >/dev/null 2>&1; then
    log "[WARN] No se pudo validar MySQL con las credenciales del proyecto. Asegura que el servidor y la base certificados_dev existan."
  fi
}

install_php_dependencies() {
  log "[INFO] Instalando dependencias de Composer..."
  composer install --no-interaction --prefer-dist
}

install_node_dependencies() {
  if [ -f "$PROJECT_DIR/package.json" ]; then
    if [ ! -d "$PROJECT_DIR/node_modules" ]; then
      log "[INFO] Instalando dependencias de Node..."
      npm install --no-audit --no-fund
    else
      log "[INFO] node_modules ya existe, se omite npm install."
    fi
  fi
}

run_migrations_and_seeders() {
  log "[INFO] Ejecutando migraciones y seeders en MySQL local..."
  APP_ENV=local \
  DB_CONNECTION=mysql \
  DB_HOST="${DB_HOST:-127.0.0.1}" \
  DB_PORT="${DB_PORT:-3306}" \
  DB_DATABASE="${DB_DATABASE:-certificados_dev}" \
  DB_USERNAME="${DB_USERNAME:-laravel}" \
  DB_PASSWORD="${DB_PASSWORD:-secret}" \
  CACHE_STORE=database \
  SESSION_DRIVER=database \
  QUEUE_CONNECTION=database \
  MAIL_MAILER=log \
  ADMIN_ACCESS_KEY="${ADMIN_ACCESS_KEY:-test-admin-key}" \
  php artisan migrate --seed --force
}

run_tests() {
  log "[INFO] Ejecutando pruebas Feature..."
  php artisan test --testsuite=Feature --stop-on-failure

  log "[INFO] Ejecutando pruebas Unit..."
  php artisan test --testsuite=Unit --stop-on-failure
}

main() {
  cd "$PROJECT_DIR"

  detect_sudo
  require_cmd php
  require_cmd composer
  require_cmd npm
  install_mysql_driver

  ensure_env_file
  install_php_dependencies
  install_node_dependencies
  ensure_app_key
  ensure_mysql_database
  run_migrations_and_seeders
  run_tests

  log "[OK] Entorno local validado correctamente."
}

main "$@"
