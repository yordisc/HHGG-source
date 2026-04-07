#!/usr/bin/env sh
set -eu

PROJECT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"

MODE="serve"
APP_PORT="${PORT:-8000}"
VITE_PORT="${VITE_PORT:-5173}"
KILL_STALE="0"
APP_PUBLIC_URL=""
VITE_PUBLIC_URL=""

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

has_pcntl() {
  php -r 'exit(function_exists("pcntl_fork") ? 0 : 1);' >/dev/null 2>&1
}

is_codespaces() {
  [ "${CODESPACES:-}" = "true" ]
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

  APP_PORT="$(pick_available_port "$REQUESTED_APP_PORT" 40)" || fail "No se encontro puerto libre para Laravel a partir de ${REQUESTED_APP_PORT}."
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

  log "[INFO] Laravel escuchando en 0.0.0.0:${APP_PORT}"
  log "[INFO] Vite escuchando en 0.0.0.0:${VITE_PORT}"

  log "[INFO] URL de la aplicacion (usar esta): ${APP_PUBLIC_URL}"
  log "[INFO] URL de Vite (solo assets/HMR): ${VITE_PUBLIC_URL}"
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
  prepare_ports
  print_access_hint
  normalize_hot_file_url
  trap cleanup_hot_file EXIT INT TERM

  if should_use_pail; then
    log "[INFO] Levantando entorno de desarrollo con Pail..."
    APP_PORT="$APP_PORT" composer run dev
    return
  fi

  if is_codespaces; then
    log "[INFO] Codespaces detectado; se omite Pail por estabilidad."
  else
    log "[WARN] La extension pcntl no esta disponible; se omite Pail."
  fi
  log "[INFO] Levantando entorno de desarrollo sin Pail..."
  APP_URL="${APP_PUBLIC_URL}" VITE_PUBLIC_URL="${VITE_PUBLIC_URL}" npx concurrently -c "#93c5fd,#c4b5fd,#fdba74" "php artisan serve --host=0.0.0.0 --port=${APP_PORT}" "php artisan queue:listen --tries=1" "npm run dev -- --host=0.0.0.0 --port=${VITE_PORT} --strictPort" --names=server,queue,vite
}

main() {
  cd "$PROJECT_DIR"

  parse_args "$@"

  require_cmd php
  require_cmd composer
  require_cmd npm

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