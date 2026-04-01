#!/usr/bin/env sh
set -eu

PROJECT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
ENV_FILE="$PROJECT_DIR/.env"
SQLITE_DB="$PROJECT_DIR/database/database.sqlite"
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

  fail "Se requieren privilegios de administrador para instalar dependencias del sistema. Ejecuta el script con sudo o instala php-sqlite3 manualmente."
}

has_sqlite_driver() {
  php -m 2>/dev/null | grep -qi '^pdo_sqlite$'
}

install_sqlite_driver() {
  if has_sqlite_driver; then
    log "[INFO] Driver PDO SQLite ya disponible."
    return
  fi

  log "[INFO] Falta el driver PDO SQLite; intentando instalarlo..."

  if command -v apk >/dev/null 2>&1; then
    run_privileged apk add --no-cache php-pdo_sqlite php-sqlite3 sqlite
  elif command -v apt-get >/dev/null 2>&1; then
    run_privileged apt-get update -y

    php_version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || true)"
    if [ -n "$php_version" ] && run_privileged apt-get install -y "php${php_version}-sqlite3" sqlite3; then
      :
    elif run_privileged apt-get install -y php-sqlite3 sqlite3; then
      :
    else
      fail "No se pudo instalar el paquete de SQLite. En Debian/Ubuntu prueba: sudo apt-get install php-sqlite3 sqlite3"
    fi
  else
    fail "No se detectó apk ni apt-get. Instala manualmente el driver PDO SQLite para PHP."
  fi

  if ! has_sqlite_driver; then
    fail "El driver PDO SQLite sigue sin estar disponible después de la instalación. Verifica tu instalación de PHP."
  fi

  log "[OK] Driver PDO SQLite instalado correctamente."
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

ensure_sqlite_database() {
  mkdir -p "$PROJECT_DIR/database"
  if [ ! -f "$SQLITE_DB" ]; then
    log "[INFO] Creando base SQLite local..."
    : > "$SQLITE_DB"
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
  log "[INFO] Ejecutando migraciones y seeders en SQLite local..."
  APP_ENV=local \
  DB_CONNECTION=sqlite \
  DB_DATABASE="$SQLITE_DB" \
  CACHE_STORE=file \
  SESSION_DRIVER=file \
  QUEUE_CONNECTION=sync \
  MAIL_MAILER=log \
  ADMIN_ACCESS_KEY="${ADMIN_ACCESS_KEY:-test-admin-key}" \
  php artisan migrate --seed --force
}

run_tests() {
  log "[INFO] Ejecutando suite de pruebas..."
  php artisan test
}

main() {
  cd "$PROJECT_DIR"

  detect_sudo
  require_cmd php
  require_cmd composer
  require_cmd npm
  install_sqlite_driver

  ensure_env_file
  install_php_dependencies
  install_node_dependencies
  ensure_app_key
  ensure_sqlite_database
  run_migrations_and_seeders
  run_tests

  log "[OK] Entorno local validado correctamente."
}

main "$@"
