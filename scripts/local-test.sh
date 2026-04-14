#!/usr/bin/env sh
set -eu

PROJECT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
ENV_FILE="$PROJECT_DIR/.env"
SUDO=""
LOCAL_DB_CONNECTION="${DB_CONNECTION:-mysql}"
TEST_DB_HOST="${DB_HOST:-127.0.0.1}"
TEST_DB_PORT="${DB_PORT:-3306}"
DEV_DB_NAME="${DB_DATABASE:-certificados_dev}"
TEST_DB_NAME="certificados_test"
TEST_DB_USER="${DB_USERNAME:-laravel}"
TEST_DB_PASSWORD="${DB_PASSWORD:-secret}"

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

is_safe_mysql_name() {
  VALUE="$1"
  case "$VALUE" in
    *[!A-Za-z0-9_]*|'')
      return 1
      ;;
    *)
      return 0
      ;;
  esac
}

can_connect_as_mysql_root() {
  if ! command -v mysql >/dev/null 2>&1; then
    return 1
  fi

  if mysql -u root -e 'SELECT 1;' >/dev/null 2>&1; then
    return 0
  fi

  if mysql --protocol=TCP -h "$TEST_DB_HOST" -P "$TEST_DB_PORT" -u root -e 'SELECT 1;' >/dev/null 2>&1; then
    return 0
  fi

  run_privileged mysql -u root -e 'SELECT 1;' >/dev/null 2>&1
}

run_mysql_as_root() {
  MYSQL_SQL="$1"

  if mysql -u root -e 'SELECT 1;' >/dev/null 2>&1; then
    mysql -u root -e "$MYSQL_SQL" >/dev/null
    return
  fi

  if mysql --protocol=TCP -h "$TEST_DB_HOST" -P "$TEST_DB_PORT" -u root -e 'SELECT 1;' >/dev/null 2>&1; then
    mysql --protocol=TCP -h "$TEST_DB_HOST" -P "$TEST_DB_PORT" -u root -e "$MYSQL_SQL" >/dev/null
    return
  fi

  run_privileged mysql -u root -e "$MYSQL_SQL" >/dev/null
}

bootstrap_test_database_permissions() {
  if ! command -v mysql >/dev/null 2>&1; then
    log "[WARN] mysql client no disponible; se asume que la base ${TEST_DB_NAME} ya existe con permisos correctos."
    return
  fi

  if [ "$LOCAL_DB_CONNECTION" != "mysql" ]; then
    return
  fi

  if ! is_safe_mysql_name "$TEST_DB_NAME"; then
    fail "DB_DATABASE invalido para bootstrap automatico: ${TEST_DB_NAME}"
  fi

  if ! is_safe_mysql_name "$DEV_DB_NAME"; then
    fail "DB_DATABASE invalido para bootstrap automatico: ${DEV_DB_NAME}"
  fi

  if ! is_safe_mysql_name "$TEST_DB_USER"; then
    fail "DB_USERNAME invalido para bootstrap automatico: ${TEST_DB_USER}"
  fi

  CAN_CONNECT_TEST_DB="false"
  CAN_CONNECT_DEV_DB="false"

  if mysql --protocol=TCP -h "$TEST_DB_HOST" -P "$TEST_DB_PORT" -u "$TEST_DB_USER" -p"$TEST_DB_PASSWORD" "$TEST_DB_NAME" -e 'SELECT 1;' >/dev/null 2>&1; then
    CAN_CONNECT_TEST_DB="true"
  fi

  if mysql --protocol=TCP -h "$TEST_DB_HOST" -P "$TEST_DB_PORT" -u "$TEST_DB_USER" -p"$TEST_DB_PASSWORD" "$DEV_DB_NAME" -e 'SELECT 1;' >/dev/null 2>&1; then
    CAN_CONNECT_DEV_DB="true"
  fi

  if [ "$CAN_CONNECT_TEST_DB" = "true" ] && [ "$CAN_CONNECT_DEV_DB" = "true" ]; then
    return
  fi

  log "[WARN] El usuario ${TEST_DB_USER} no tiene acceso completo a ${DEV_DB_NAME} y/o ${TEST_DB_NAME}."
  log "[INFO] Intentando crear/actualizar permisos MySQL con root..."

  if ! can_connect_as_mysql_root; then
    fail "No fue posible autenticarse como root para crear la base de pruebas. Ajusta .env o crea certificados_test manualmente."
  fi

  ESCAPED_TEST_DB_PASSWORD="$(printf '%s' "$TEST_DB_PASSWORD" | sed "s/'/''/g")"

  ROOT_SQL="CREATE DATABASE IF NOT EXISTS ${DEV_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  ROOT_SQL="${ROOT_SQL} CREATE DATABASE IF NOT EXISTS ${TEST_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  ROOT_SQL="${ROOT_SQL} CREATE USER IF NOT EXISTS '${TEST_DB_USER}'@'%' IDENTIFIED BY '${ESCAPED_TEST_DB_PASSWORD}';"
  ROOT_SQL="${ROOT_SQL} CREATE USER IF NOT EXISTS '${TEST_DB_USER}'@'localhost' IDENTIFIED BY '${ESCAPED_TEST_DB_PASSWORD}';"
  ROOT_SQL="${ROOT_SQL} CREATE USER IF NOT EXISTS '${TEST_DB_USER}'@'127.0.0.1' IDENTIFIED BY '${ESCAPED_TEST_DB_PASSWORD}';"
  ROOT_SQL="${ROOT_SQL} GRANT ALL PRIVILEGES ON ${DEV_DB_NAME}.* TO '${TEST_DB_USER}'@'%';"
  ROOT_SQL="${ROOT_SQL} GRANT ALL PRIVILEGES ON ${DEV_DB_NAME}.* TO '${TEST_DB_USER}'@'localhost';"
  ROOT_SQL="${ROOT_SQL} GRANT ALL PRIVILEGES ON ${DEV_DB_NAME}.* TO '${TEST_DB_USER}'@'127.0.0.1';"
  ROOT_SQL="${ROOT_SQL} GRANT ALL PRIVILEGES ON ${TEST_DB_NAME}.* TO '${TEST_DB_USER}'@'%';"
  ROOT_SQL="${ROOT_SQL} GRANT ALL PRIVILEGES ON ${TEST_DB_NAME}.* TO '${TEST_DB_USER}'@'localhost';"
  ROOT_SQL="${ROOT_SQL} GRANT ALL PRIVILEGES ON ${TEST_DB_NAME}.* TO '${TEST_DB_USER}'@'127.0.0.1';"
  ROOT_SQL="${ROOT_SQL} FLUSH PRIVILEGES;"

  run_mysql_as_root "$ROOT_SQL"

  if ! mysql --protocol=TCP -h "$TEST_DB_HOST" -P "$TEST_DB_PORT" -u "$TEST_DB_USER" -p"$TEST_DB_PASSWORD" "$TEST_DB_NAME" -e 'SELECT 1;' >/dev/null 2>&1; then
    fail "Se crearon permisos en MySQL, pero ${TEST_DB_USER} todavia no puede acceder a ${TEST_DB_NAME}. Revisa DB_HOST/DB_PORT/DB_PASSWORD en .env."
  fi

  if ! mysql --protocol=TCP -h "$TEST_DB_HOST" -P "$TEST_DB_PORT" -u "$TEST_DB_USER" -p"$TEST_DB_PASSWORD" "$DEV_DB_NAME" -e 'SELECT 1;' >/dev/null 2>&1; then
    fail "Se crearon permisos en MySQL, pero ${TEST_DB_USER} todavia no puede acceder a ${DEV_DB_NAME}. Revisa DB_HOST/DB_PORT/DB_PASSWORD en .env."
  fi

  log "[OK] Permisos MySQL listos para ${DEV_DB_NAME} y ${TEST_DB_NAME}."
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

run_test_migrations() {
  log "[INFO] Preparando la base de datos de pruebas..."
  APP_ENV=testing \
  DB_CONNECTION=mysql \
  DB_HOST="$TEST_DB_HOST" \
  DB_PORT="$TEST_DB_PORT" \
  DB_DATABASE="$TEST_DB_NAME" \
  DB_USERNAME="$TEST_DB_USER" \
  DB_PASSWORD="$TEST_DB_PASSWORD" \
  CACHE_STORE=array \
  SESSION_DRIVER=array \
  QUEUE_CONNECTION=sync \
  MAIL_MAILER=array \
  ADMIN_ACCESS_KEY="${ADMIN_ACCESS_KEY:-test-admin-key}" \
  php artisan migrate --force
}

run_tests() {
  TEST_LOG_DIR="$PROJECT_DIR/storage/logs"
  TEST_LOG_FILE="$TEST_LOG_DIR/local-test-tests.log"

  mkdir -p "$TEST_LOG_DIR"

  log "[INFO] Ejecutando pruebas Feature..."
  : > "$TEST_LOG_FILE"
  set +e
  {
    printf '%s\n' "[INFO] Ejecutando pruebas Feature..."
    php artisan test --testsuite=Feature --stop-on-failure

    printf '%s\n' "[INFO] Ejecutando pruebas Unit..."
    php artisan test --testsuite=Unit --stop-on-failure
  } > "$TEST_LOG_FILE" 2>&1

  TEST_EXIT_CODE=$?
  set -e

  cat "$TEST_LOG_FILE"

  if [ "$TEST_EXIT_CODE" -ne 0 ]; then
    log "[WARN] La salida completa quedó guardada en: $TEST_LOG_FILE"
    return "$TEST_EXIT_CODE"
  fi

  log "[OK] La salida completa quedó guardada en: $TEST_LOG_FILE"
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
  bootstrap_test_database_permissions
  run_migrations_and_seeders
  run_test_migrations
  run_tests

  log "[OK] Entorno local validado correctamente."
}

main "$@"
