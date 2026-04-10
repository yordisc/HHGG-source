#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# on-create.sh
# Hook de inicializacion para el Codespace actual.
# No instala paquetes del sistema: el contenedor ya trae PHP, Composer y Node.
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Instituto de Certificaciones Dudosas™ — Setup inicial      ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

WORKSPACE_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$WORKSPACE_DIR"

ensure_env() {
  if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    cp .env.example .env
    echo "   .env creado desde .env.example"
  fi

  if [ -f ".env" ]; then
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env || true
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$WORKSPACE_DIR/database/database.sqlite|" .env || true
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
  mkdir -p database
  touch database/database.sqlite

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
ensure_env

echo "► Instalando dependencias del proyecto..."
install_dependencies

echo "► Preparando base de datos y cache..."
prepare_database

echo "► Limpiando cache y ajustando gitignore..."
cleanup

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Setup inicial completado                                   ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
