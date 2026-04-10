#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# post-start.sh — MySQL/MariaDB para Codespaces
# Se ejecuta CADA VEZ que el Codespace arranca (no solo la primera vez).
# Verifica que MySQL/MariaDB esté disponible y ejecuta migraciones/seeders.
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Instituto de Certificaciones Dudosas™ — Iniciando...       ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

WORKSPACE_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$WORKSPACE_DIR"

start_mysql_service() {
  echo "► Iniciando servicio MySQL/MariaDB..."

  if service mysql start >/dev/null 2>&1; then
    :
  elif service mariadb start >/dev/null 2>&1; then
    :
  fi
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
  echo "► Asegurando base de datos y usuario de desarrollo..."

  mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS certificados_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON certificados_dev.* TO 'laravel'@'%';
FLUSH PRIVILEGES;
SQL

  echo "   ✓ MySQL listo para el proyecto"
}

run_migrations() {
  echo ""
  echo "► Ejecutando migraciones de base de datos..."

  php artisan cache:table 2>/dev/null || true
  php artisan session:table 2>/dev/null || true

  DB_CONNECTION=mysql \
  DB_HOST=127.0.0.1 \
  DB_PORT=3306 \
  DB_DATABASE=certificados_dev \
  DB_USERNAME=laravel \
  DB_PASSWORD=secret \
  php artisan migrate --no-interaction --force

  echo "   ✓ Migraciones ejecutadas"
}

run_seeders_if_needed() {
  echo ""
  echo "► Verificando datos de ejemplo en la base de datos..."

  QUESTION_COUNT=$(DB_CONNECTION=mysql \
    DB_HOST=127.0.0.1 \
    DB_PORT=3306 \
    DB_DATABASE=certificados_dev \
    DB_USERNAME=laravel \
    DB_PASSWORD=secret \
    php artisan tinker --execute="echo \\App\\Models\\Question::count();" 2>/dev/null | tail -1 | tr -d '[:space:]' || echo "0")

  if [ "$QUESTION_COUNT" = "0" ] || [ -z "$QUESTION_COUNT" ]; then
    echo "   No hay preguntas en la DB — ejecutando seeders..."
    DB_CONNECTION=mysql \
    DB_HOST=127.0.0.1 \
    DB_PORT=3306 \
    DB_DATABASE=certificados_dev \
    DB_USERNAME=laravel \
    DB_PASSWORD=secret \
    php artisan db:seed --no-interaction
    echo "   ✓ Datos de ejemplo cargados"
  else
    echo "   ✓ Ya existen $QUESTION_COUNT preguntas en la DB (saltando seeders)"
  fi
}

clear_dev_cache() {
  echo ""
  echo "► Limpiando caché de desarrollo..."
  php artisan config:clear 2>/dev/null || true
  php artisan route:clear 2>/dev/null || true
  php artisan view:clear 2>/dev/null || true
  echo "   ✓ Caché limpiada"
}

show_status() {
  echo ""
  echo "╔══════════════════════════════════════════════════════════════╗"
  echo "║   ✅ Codespace listo para desarrollar                        ║"
  echo "╠══════════════════════════════════════════════════════════════╣"
  echo "║                                                              ║"
  echo "║   COMANDOS PARA EMPEZAR:                                     ║"
  echo "║                                                              ║"
  echo "║   Servidor de desarrollo:                                    ║"
  echo "║   $ php artisan serve                                        ║"
  echo "║                                                              ║"
  echo "║   Assets (en otra terminal):                                 ║"
  echo "║   $ npm run dev                                              ║"
  echo "║                                                              ║"
  echo "║   Ver todas las rutas disponibles:                           ║"
  echo "║   $ php artisan route:list                                   ║"
  echo "║                                                              ║"
  echo "║   Consola interactiva (Tinker):                              ║"
  echo "║   $ php artisan tinker                                       ║"
  echo "║                                                              ║"
  echo "║   Ver el plan del proyecto:                                  ║"
  echo "║   $ cat plan_certificados_laravel.md                         ║"
  echo "╚══════════════════════════════════════════════════════════════╝"
  echo ""
}

start_mysql_service
wait_for_mysql
configure_mysql_database
run_migrations
run_seeders_if_needed
clear_dev_cache
show_status
