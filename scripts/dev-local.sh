#!/usr/bin/env sh
set -eu

PROJECT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"

MODE="serve"

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

usage() {
  cat <<'EOF'
Uso:
  sh scripts/dev-local.sh [--test|--serve|--all]

Opciones:
  --test   Ejecuta solo la validacion y los tests.
  --serve  Levanta el stack de desarrollo sin ejecutar tests.
  --all    Ejecuta tests y luego levanta el stack de desarrollo.
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
  if has_pcntl; then
    log "[INFO] Levantando entorno de desarrollo con Pail..."
    composer run dev
    return
  fi

  log "[WARN] La extension pcntl no esta disponible; se omite Pail."
  log "[INFO] Levantando entorno de desarrollo sin Pail..."
  npx concurrently -c "#93c5fd,#c4b5fd,#fdba74" "php artisan serve --host=0.0.0.0 --port=8000" "php artisan queue:listen --tries=1" "npm run dev" --names=server,queue,vite
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