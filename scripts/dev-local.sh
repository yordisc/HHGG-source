#!/usr/bin/env sh
set -eu

PROJECT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"

ENV_FILE="$PROJECT_DIR/.env"
LOCAL_APP_ENV="local"
LOCAL_DB_CONNECTION="mysql"
LOCAL_DB_HOST="127.0.0.1"
LOCAL_DB_PORT="3306"
LOCAL_DB_DATABASE="certificados_dev"
LOCAL_DB_USERNAME="laravel"
LOCAL_DB_PASSWORD="secret"
LOCAL_CACHE_STORE="database"
LOCAL_SESSION_DRIVER="database"
LOCAL_QUEUE_CONNECTION="database"
LOCAL_MAIL_MAILER="log"

MODE="serve"
APP_PORT="${PORT:-8000}"
VITE_PORT="${VITE_PORT:-5173}"
KILL_STALE="0"
APP_PUBLIC_URL=""
VITE_PUBLIC_URL=""
XDEBUG_MODE_SETTING="${DEV_LOCAL_XDEBUG_MODE:-off}"
FRONTEND_MODE="vite"

read_env_value() {
  KEY="$1"

  if [ ! -f "$ENV_FILE" ]; then
    return 0
  fi

  VALUE="$(grep -E "^${KEY}=" "$ENV_FILE" | tail -n 1 | cut -d '=' -f2- || true)"
  if [ -z "$VALUE" ]; then
    return 0
  fi

  FIRST_CHAR="$(printf '%s' "$VALUE" | cut -c1)"
  LAST_CHAR="$(printf '%s' "$VALUE" | awk '{print substr($0, length, 1)}')"

  if [ "$FIRST_CHAR" = '"' ] && [ "$LAST_CHAR" = '"' ]; then
    VALUE="${VALUE#\"}"
    VALUE="${VALUE%\"}"
  elif [ "$FIRST_CHAR" = "'" ] && [ "$LAST_CHAR" = "'" ]; then
    VALUE="${VALUE#\'}"
    VALUE="${VALUE%\'}"
  fi

  printf '%s' "$VALUE"
}

resolve_config_value() {
  KEY="$1"
  DEFAULT_VALUE="$2"

  CURRENT_VALUE="$(printenv "$KEY" 2>/dev/null || true)"
  if [ -n "$CURRENT_VALUE" ]; then
    printf '%s' "$CURRENT_VALUE"
    return 0
  fi

  ENV_FILE_VALUE="$(read_env_value "$KEY")"
  if [ -n "$ENV_FILE_VALUE" ]; then
    printf '%s' "$ENV_FILE_VALUE"
    return 0
  fi

  printf '%s' "$DEFAULT_VALUE"
}

init_local_runtime_config() {
  LOCAL_APP_ENV="$(resolve_config_value APP_ENV "$LOCAL_APP_ENV")"
  LOCAL_DB_CONNECTION="$(resolve_config_value DB_CONNECTION "$LOCAL_DB_CONNECTION")"
  LOCAL_DB_HOST="$(resolve_config_value DB_HOST "$LOCAL_DB_HOST")"
  LOCAL_DB_PORT="$(resolve_config_value DB_PORT "$LOCAL_DB_PORT")"
  LOCAL_DB_DATABASE="$(resolve_config_value DB_DATABASE "$LOCAL_DB_DATABASE")"
  LOCAL_DB_USERNAME="$(resolve_config_value DB_USERNAME "$LOCAL_DB_USERNAME")"
  LOCAL_DB_PASSWORD="$(resolve_config_value DB_PASSWORD "$LOCAL_DB_PASSWORD")"
  LOCAL_CACHE_STORE="$(resolve_config_value CACHE_STORE "$LOCAL_CACHE_STORE")"
  LOCAL_SESSION_DRIVER="$(resolve_config_value SESSION_DRIVER "$LOCAL_SESSION_DRIVER")"
  LOCAL_QUEUE_CONNECTION="$(resolve_config_value QUEUE_CONNECTION "$LOCAL_QUEUE_CONNECTION")"
  LOCAL_MAIL_MAILER="$(resolve_config_value MAIL_MAILER "$LOCAL_MAIL_MAILER")"
}

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

run_privileged() {
  if [ "$(id -u)" -eq 0 ]; then
    "$@"
    return
  fi

  if command -v sudo >/dev/null 2>&1; then
    sudo "$@"
    return
  fi

  fail "Se requieren privilegios de administrador para instalar el driver PDO MySQL o ejecuta el comando con sudo."
}

has_mysql_driver() {
  php -r 'exit(extension_loaded("pdo_mysql") ? 0 : 1);' >/dev/null 2>&1
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
    XDEBUG_MODE=off php artisan key:generate --force --no-interaction >/dev/null
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

can_connect_with_app_mysql_credentials() {
  if ! command -v mysql >/dev/null 2>&1; then
    return 1
  fi

  mysql --protocol=TCP \
    -h "$LOCAL_DB_HOST" \
    -P "$LOCAL_DB_PORT" \
    -u "$LOCAL_DB_USERNAME" \
    -p"$LOCAL_DB_PASSWORD" \
    -e 'SELECT 1;' >/dev/null 2>&1
}

can_connect_as_mysql_root() {
  if ! command -v mysql >/dev/null 2>&1; then
    return 1
  fi

  if mysql -u root -e 'SELECT 1;' >/dev/null 2>&1; then
    return 0
  fi

  if mysql --protocol=TCP -h "$LOCAL_DB_HOST" -P "$LOCAL_DB_PORT" -u root -e 'SELECT 1;' >/dev/null 2>&1; then
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

  if mysql --protocol=TCP -h "$LOCAL_DB_HOST" -P "$LOCAL_DB_PORT" -u root -e 'SELECT 1;' >/dev/null 2>&1; then
    mysql --protocol=TCP -h "$LOCAL_DB_HOST" -P "$LOCAL_DB_PORT" -u root -e "$MYSQL_SQL" >/dev/null
    return
  fi

  run_privileged mysql -u root -e "$MYSQL_SQL" >/dev/null
}

bootstrap_mysql_permissions_if_needed() {
  if can_connect_with_app_mysql_credentials; then
    return 0
  fi

  if [ "$LOCAL_DB_CONNECTION" != "mysql" ]; then
    return 0
  fi

  if ! command -v mysql >/dev/null 2>&1; then
    fail "No se pudo validar MySQL (falta cliente mysql) y las credenciales actuales no funcionan para migrar."
  fi

  log "[WARN] No se pudo autenticar en MySQL con ${LOCAL_DB_USERNAME}@${LOCAL_DB_HOST}:${LOCAL_DB_PORT}."
  log "[INFO] Intentando crear base/usuario con root..."

  if ! is_safe_mysql_name "$LOCAL_DB_DATABASE"; then
    fail "DB_DATABASE invalido para bootstrap automatico: ${LOCAL_DB_DATABASE}"
  fi

  if ! is_safe_mysql_name "$LOCAL_DB_USERNAME"; then
    fail "DB_USERNAME invalido para bootstrap automatico: ${LOCAL_DB_USERNAME}"
  fi

  if ! can_connect_as_mysql_root; then
    fail "No fue posible autenticarse como root para crear permisos. Ajusta .env o crea el usuario manualmente."
  fi

  ESCAPED_DB_PASSWORD="$(printf '%s' "$LOCAL_DB_PASSWORD" | sed "s/'/''/g")"

  ROOT_SQL="CREATE DATABASE IF NOT EXISTS ${LOCAL_DB_DATABASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  ROOT_SQL="${ROOT_SQL} CREATE USER IF NOT EXISTS '${LOCAL_DB_USERNAME}'@'%' IDENTIFIED BY '${ESCAPED_DB_PASSWORD}';"
  ROOT_SQL="${ROOT_SQL} CREATE USER IF NOT EXISTS '${LOCAL_DB_USERNAME}'@'localhost' IDENTIFIED BY '${ESCAPED_DB_PASSWORD}';"
  ROOT_SQL="${ROOT_SQL} GRANT ALL PRIVILEGES ON ${LOCAL_DB_DATABASE}.* TO '${LOCAL_DB_USERNAME}'@'%';"
  ROOT_SQL="${ROOT_SQL} GRANT ALL PRIVILEGES ON ${LOCAL_DB_DATABASE}.* TO '${LOCAL_DB_USERNAME}'@'localhost';"
  ROOT_SQL="${ROOT_SQL} FLUSH PRIVILEGES;"

  run_mysql_as_root "$ROOT_SQL"

  if ! can_connect_with_app_mysql_credentials; then
    fail "Se crearon permisos en MySQL, pero ${LOCAL_DB_USERNAME} todavia no puede autenticarse. Revisa DB_HOST/DB_PORT/DB_PASSWORD en .env."
  fi

  log "[OK] Permisos MySQL listos para ${LOCAL_DB_USERNAME}."
}

install_php_dependencies() {
  if [ ! -f "$PROJECT_DIR/vendor/autoload.php" ]; then
    log "[INFO] Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist
  fi
}

install_node_dependencies() {
  if [ ! -d "$PROJECT_DIR/node_modules" ]; then
    log "[INFO] Instalando dependencias de Node..."
    npm install --no-audit --no-fund
  fi
}

prepare_application_state() {
  ensure_env_file
  init_local_runtime_config
  resolve_frontend_mode
  install_php_dependencies
  install_node_dependencies
  ensure_app_key
  bootstrap_mysql_permissions_if_needed

  if [ "$FRONTEND_MODE" = "build-watch" ]; then
    log "[INFO] Generando build inicial de assets (modo Codespaces)..."
    npm run build >/dev/null
    cleanup_hot_file
  fi

  log "[INFO] Ejecutando migraciones locales..."
  APP_ENV="$LOCAL_APP_ENV" \
  DB_CONNECTION="$LOCAL_DB_CONNECTION" \
  DB_HOST="$LOCAL_DB_HOST" \
  DB_PORT="$LOCAL_DB_PORT" \
  DB_DATABASE="$LOCAL_DB_DATABASE" \
  DB_USERNAME="$LOCAL_DB_USERNAME" \
  DB_PASSWORD="$LOCAL_DB_PASSWORD" \
  CACHE_STORE="$LOCAL_CACHE_STORE" \
  SESSION_DRIVER="$LOCAL_SESSION_DRIVER" \
  QUEUE_CONNECTION="$LOCAL_QUEUE_CONNECTION" \
  MAIL_MAILER="$LOCAL_MAIL_MAILER" \
  XDEBUG_MODE=off php artisan migrate --force --no-interaction
}

has_pcntl() {
  php -r 'exit(function_exists("pcntl_fork") ? 0 : 1);' >/dev/null 2>&1
}

is_codespaces() {
  [ "${CODESPACES:-}" = "true" ]
}

resolve_frontend_mode() {
  REQUESTED_FRONTEND_MODE="${DEV_LOCAL_FRONTEND_MODE:-}"

  if [ -n "$REQUESTED_FRONTEND_MODE" ]; then
    FRONTEND_MODE="$REQUESTED_FRONTEND_MODE"
    return
  fi

  if is_codespaces; then
    FRONTEND_MODE="build-watch"
    return
  fi

  FRONTEND_MODE="vite"
}

is_port_available() {
  APP_PORT_TO_CHECK="$1" php -r '
    $port = getenv("APP_PORT_TO_CHECK");
    $socket = @stream_socket_server("tcp://0.0.0.0:" . $port, $errno, $errstr);
    if ($socket === false) {
      exit(1);
    }
    fclose($socket);
    exit(0);
  ' >/dev/null 2>&1
}

pick_available_port() {
  BASE_PORT="$1"
  MAX_JUMPS="${2:-20}"
  CURRENT_PORT="$BASE_PORT"
  CURRENT_JUMP=0

  while [ "$CURRENT_JUMP" -le "$MAX_JUMPS" ]; do
    if is_port_available "$CURRENT_PORT"; then
      printf '%s' "$CURRENT_PORT"
      return 0
    fi

    CURRENT_PORT=$((CURRENT_PORT + 1))
    CURRENT_JUMP=$((CURRENT_JUMP + 1))
  done

  return 1
}

resolve_ports() {
  REQUESTED_APP_PORT="$APP_PORT"
  REQUESTED_VITE_PORT="$VITE_PORT"

  if is_codespaces; then
    if ! is_port_available "$REQUESTED_APP_PORT"; then
      fail "El puerto ${REQUESTED_APP_PORT} ya esta ocupado en Codespaces. Ejecuta con --kill-stale o libera ese puerto antes de iniciar."
    fi
    APP_PORT="$REQUESTED_APP_PORT"
  else
    APP_PORT="$(pick_available_port "$REQUESTED_APP_PORT" 40)" || fail "No se encontro puerto libre para Laravel a partir de ${REQUESTED_APP_PORT}."
  fi

  VITE_PORT="$(pick_available_port "$REQUESTED_VITE_PORT" 40)" || fail "No se encontro puerto libre para Vite a partir de ${REQUESTED_VITE_PORT}."

  if [ "$APP_PORT" != "$REQUESTED_APP_PORT" ]; then
    log "[WARN] Puerto ${REQUESTED_APP_PORT} ocupado; Laravel usara ${APP_PORT}."
  fi

  if [ "$VITE_PORT" != "$REQUESTED_VITE_PORT" ]; then
    log "[WARN] Puerto ${REQUESTED_VITE_PORT} ocupado; Vite usara ${VITE_PORT}."
  fi
}

kill_port_processes() {
  TARGET_PORT="$1"
  PIDS=""

  if command -v lsof >/dev/null 2>&1; then
    PIDS="$(lsof -ti "tcp:${TARGET_PORT}" 2>/dev/null | tr '\n' ' ' || true)"
  elif command -v fuser >/dev/null 2>&1; then
    PIDS="$(fuser -n tcp "${TARGET_PORT}" 2>/dev/null | tr '\n' ' ' || true)"
  else
    log "[WARN] No hay lsof ni fuser; no se puede limpiar puerto ${TARGET_PORT} automaticamente."
    return
  fi

  PIDS="$(printf '%s' "$PIDS" | xargs echo 2>/dev/null || true)"
  if [ -z "$PIDS" ]; then
    log "[INFO] Puerto ${TARGET_PORT} sin procesos stale."
    return
  fi

  log "[WARN] Cerrando procesos en puerto ${TARGET_PORT}: ${PIDS}"
  # shellcheck disable=SC2086
  kill $PIDS >/dev/null 2>&1 || true

  if ! is_port_available "$TARGET_PORT"; then
    log "[WARN] Forzando cierre en puerto ${TARGET_PORT}..."
    # shellcheck disable=SC2086
    kill -9 $PIDS >/dev/null 2>&1 || true
  fi
}

prepare_ports() {
  if is_codespaces; then
    KILL_STALE="1"
  fi

  if [ "$KILL_STALE" = "1" ]; then
    log "[INFO] --kill-stale activo: limpiando puertos objetivo antes de iniciar."
    kill_port_processes "$APP_PORT"
    kill_port_processes "$VITE_PORT"
  fi

  resolve_ports
}

resolve_public_urls() {
  if is_codespaces && [ -n "${CODESPACE_NAME:-}" ] && [ -n "${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-}" ]; then
    APP_PUBLIC_URL="https://${CODESPACE_NAME}-${APP_PORT}.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
    VITE_PUBLIC_URL="https://${CODESPACE_NAME}-${VITE_PORT}.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
    return
  fi

  APP_PUBLIC_URL="http://localhost:${APP_PORT}"
  VITE_PUBLIC_URL="http://localhost:${VITE_PORT}"
}

should_use_pail() {
  if [ "${DEV_LOCAL_WITH_PAIL:-}" = "0" ]; then
    return 1
  fi

  if is_codespaces; then
    # En Codespaces preferimos evitar pail para reducir fallos en arranque.
    return 1
  fi

  has_pcntl
}

print_access_hint() {
  resolve_public_urls
  resolve_frontend_mode

  log "[INFO] Laravel escuchando en 0.0.0.0:${APP_PORT}"
  if [ "$FRONTEND_MODE" = "vite" ]; then
    log "[INFO] Vite escuchando en 0.0.0.0:${VITE_PORT}"
  else
    log "[INFO] Assets servidos por Laravel (vite build --watch, sin puerto 5173)"
  fi

  log "[INFO] URL de la aplicacion (usar esta): ${APP_PUBLIC_URL}"
  if [ "$FRONTEND_MODE" = "vite" ]; then
    log "[INFO] URL de Vite (solo assets/HMR): ${VITE_PUBLIC_URL}"
  fi
}

open_url_in_browser() {
  TARGET_URL="$1"

  if [ "${DEV_LOCAL_OPEN_BROWSER:-1}" = "0" ]; then
    return
  fi

  if [ -n "${BROWSER:-}" ]; then
    "$BROWSER" "$TARGET_URL" >/dev/null 2>&1 &
    log "[INFO] Abriendo navegador: ${TARGET_URL}"
    return
  fi

  if command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$TARGET_URL" >/dev/null 2>&1 &
    log "[INFO] Abriendo navegador: ${TARGET_URL}"
    return
  fi

  log "[WARN] No se pudo abrir navegador automaticamente. Usa: \"\$BROWSER\" \"${TARGET_URL}\""
}

normalize_hot_file_url() {
  HOT_FILE="${PROJECT_DIR}/public/hot"

  if [ ! -f "$HOT_FILE" ]; then
    return
  fi

  CURRENT_HOT_URL="$(tr -d '\r\n' < "$HOT_FILE" 2>/dev/null || true)"
  if [ -z "$CURRENT_HOT_URL" ]; then
    return
  fi

  if [ "$CURRENT_HOT_URL" = "$VITE_PUBLIC_URL" ]; then
    return
  fi

  case "$CURRENT_HOT_URL" in
    http://0.0.0.0:*|http://127.0.0.1:*|http://localhost:*|https://*.app.github.dev*)
      printf '%s' "$VITE_PUBLIC_URL" > "$HOT_FILE"
      log "[INFO] Normalizando public/hot: ${CURRENT_HOT_URL} -> ${VITE_PUBLIC_URL}"
      ;;
  esac
}

cleanup_hot_file() {
  HOT_FILE="${PROJECT_DIR}/public/hot"

  if [ -f "$HOT_FILE" ]; then
    rm -f "$HOT_FILE"
    log "[INFO] Limpiando public/hot al salir."
  fi
}

usage() {
  cat <<'EOF'
Uso:
  sh scripts/dev-local.sh [--test|--serve|--all] [--kill-stale]

Opciones:
  --test   Ejecuta solo la validacion y los tests.
  --serve  Levanta el stack de desarrollo sin ejecutar tests.
  --all    Ejecuta tests y luego levanta el stack de desarrollo.
  --kill-stale  Finaliza procesos que ocupen los puertos de app/vite antes de iniciar.
  -h, --help  Muestra esta ayuda.
EOF
}

parse_args() {
  while [ "$#" -gt 0 ]; do
    case "$1" in
      --test)
        MODE="test"
        ;;
      --serve)
        MODE="serve"
        ;;
      --all)
        MODE="all"
        ;;
      --kill-stale)
        KILL_STALE="1"
        ;;
      -h|--help)
        usage
        exit 0
        ;;
      *)
        fail "Argumento no reconocido: $1"
        ;;
    esac
    shift
  done
}

run_development_stack() {
  resolve_frontend_mode
  prepare_ports
  print_access_hint
  open_url_in_browser "$APP_PUBLIC_URL"
  trap cleanup_hot_file EXIT INT TERM

  if [ "$FRONTEND_MODE" = "vite" ]; then
    normalize_hot_file_url
  else
    cleanup_hot_file
  fi

  PHP_WITH_XDEBUG="XDEBUG_MODE=${XDEBUG_MODE_SETTING} php"

  if [ "$XDEBUG_MODE_SETTING" = "off" ]; then
    log "[INFO] Xdebug desactivado para server/queue (exporta DEV_LOCAL_XDEBUG_MODE=debug para habilitarlo)."
  else
    log "[INFO] Xdebug activo para server/queue (modo: ${XDEBUG_MODE_SETTING})."
  fi

  if should_use_pail; then
    log "[INFO] Levantando entorno de desarrollo con Pail..."
    APP_ENV="$LOCAL_APP_ENV" \
    APP_URL="$APP_PUBLIC_URL" \
    DB_CONNECTION="$LOCAL_DB_CONNECTION" \
    DB_HOST="$LOCAL_DB_HOST" \
    DB_PORT="$LOCAL_DB_PORT" \
    DB_DATABASE="$LOCAL_DB_DATABASE" \
    DB_USERNAME="$LOCAL_DB_USERNAME" \
    DB_PASSWORD="$LOCAL_DB_PASSWORD" \
    CACHE_STORE="$LOCAL_CACHE_STORE" \
    SESSION_DRIVER="$LOCAL_SESSION_DRIVER" \
    QUEUE_CONNECTION="$LOCAL_QUEUE_CONNECTION" \
    MAIL_MAILER="$LOCAL_MAIL_MAILER" \
    APP_PORT="$APP_PORT" \
    VITE_PORT="$VITE_PORT" \
    XDEBUG_MODE="$XDEBUG_MODE_SETTING" composer run dev
    return
  fi

  if is_codespaces; then
    log "[INFO] Codespaces detectado; se omite Pail por estabilidad."
  else
    log "[WARN] La extension pcntl no esta disponible; se omite Pail."
  fi
  log "[INFO] Levantando entorno de desarrollo sin Pail..."

  FRONTEND_COMMAND="npm run dev"
  FRONTEND_NAME="vite"

  if [ "$FRONTEND_MODE" = "build-watch" ]; then
    FRONTEND_COMMAND="npm run build -- --watch"
    FRONTEND_NAME="assets"
    log "[INFO] Modo frontend build-watch activo (evita problemas CSP/CORS de puertos en Codespaces)."
  fi

  APP_ENV="$LOCAL_APP_ENV" \
  APP_URL="${APP_PUBLIC_URL}" \
  DB_CONNECTION="$LOCAL_DB_CONNECTION" \
  DB_HOST="$LOCAL_DB_HOST" \
  DB_PORT="$LOCAL_DB_PORT" \
  DB_DATABASE="$LOCAL_DB_DATABASE" \
  DB_USERNAME="$LOCAL_DB_USERNAME" \
  DB_PASSWORD="$LOCAL_DB_PASSWORD" \
  CACHE_STORE="$LOCAL_CACHE_STORE" \
  SESSION_DRIVER="$LOCAL_SESSION_DRIVER" \
  QUEUE_CONNECTION="$LOCAL_QUEUE_CONNECTION" \
  MAIL_MAILER="$LOCAL_MAIL_MAILER" \
  VITE_PORT="$VITE_PORT" \
  VITE_PUBLIC_URL="${VITE_PUBLIC_URL}" npx concurrently -c "#93c5fd,#c4b5fd,#fdba74" "${PHP_WITH_XDEBUG} artisan serve --host=0.0.0.0 --port=${APP_PORT}" "${PHP_WITH_XDEBUG} artisan queue:listen --tries=1" "${FRONTEND_COMMAND}" --names=server,queue,"${FRONTEND_NAME}"
}

main() {
  cd "$PROJECT_DIR"

  parse_args "$@"

  require_cmd php
  require_cmd composer
  require_cmd npm
  install_mysql_driver

  ensure_env_file
  init_local_runtime_config

  if [ "$MODE" = "serve" ]; then
    prepare_application_state
  fi

  if [ "$MODE" = "test" ] || [ "$MODE" = "all" ]; then
    log "[INFO] Ejecutando validacion previa con tests..."
    sh scripts/local-test.sh
  fi

  if [ "$MODE" = "serve" ] || [ "$MODE" = "all" ]; then
    log "[INFO] Iniciando stack de desarrollo..."
    run_development_stack
  fi
}

main "$@"
