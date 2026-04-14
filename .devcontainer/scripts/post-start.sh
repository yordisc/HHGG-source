#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# post-start.sh — MySQL/MariaDB para Codespaces
# Se ejecuta CADA VEZ que el Codespace arranca (no solo la primera vez).
# Verifica que MySQL/MariaDB esté disponible y ejecuta migraciones/seeders.
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

MYSQL_READY=1

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Instituto de Certificaciones Dudosas™ — Iniciando...       ║"
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

start_mysql_service() {
  echo "► Iniciando servicio MySQL/MariaDB..."

  if run_privileged service mysql start >/dev/null 2>&1; then
    :
  elif run_privileged service mariadb start >/dev/null 2>&1; then
    :
  else
    echo "   ⚠ No se pudo iniciar MySQL/MariaDB. Se continúa sin DB local."
    MYSQL_READY=0
  fi
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
    echo "► Saltando configuracion de DB (MySQL no disponible)"
    return 0
  fi

  echo "► Asegurando base de datos y usuario de desarrollo..."

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

  echo "   ✓ MySQL listo para el proyecto"
}

run_migrations() {
  if [ "$MYSQL_READY" -ne 1 ]; then
    echo ""
    echo "► Saltando migraciones (MySQL no disponible)"
    return 0
  fi

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
  if [ "$MYSQL_READY" -ne 1 ]; then
    echo ""
    echo "► Saltando seeders (MySQL no disponible)"
    return 0
  fi

  echo ""
  echo "► Verificando datos de ejemplo en la base de datos..."

  VALIDATION_RESULT=$(DB_CONNECTION=mysql \
    DB_HOST=127.0.0.1 \
    DB_PORT=3306 \
    DB_DATABASE=certificados_dev \
    DB_USERNAME=laravel \
    DB_PASSWORD=secret \
    php artisan tinker --execute="\$locales=['es','pt','fr','zh','hi','ar'];\$certs=\\App\\Models\\Certification::query()->get(['id','slug','questions_required']);\$totalQuestions=\\App\\Models\\Question::count();\$totalEn=\\App\\Models\\QuestionTranslation::query()->where('language','en')->count();\$totalLocalized=\\App\\Models\\QuestionTranslation::query()->whereIn('language',\$locales)->count();\$issues=[];foreach(\$certs as \$cert){\$questionIds=\\App\\Models\\Question::query()->where('certification_id',\$cert->id)->pluck('id');\$questionCount=\$questionIds->count();\$enCount=\$questionCount>0?\\App\\Models\\QuestionTranslation::query()->whereIn('question_id',\$questionIds)->where('language','en')->count():0;\$localizedCount=\$questionCount>0?\\App\\Models\\QuestionTranslation::query()->whereIn('question_id',\$questionIds)->whereIn('language',\$locales)->count():0;\$requiredMin=max(1,(int)\$cert->questions_required);\$expectedLocalized=\$questionCount*count(\$locales);if(\$questionCount<\$requiredMin||\$enCount<\$questionCount||\$localizedCount<\$expectedLocalized){\$issues[]=\$cert->slug.':q='.\$questionCount.',min='.\$requiredMin.',en='.\$enCount.',loc='.\$localizedCount.'/'.\$expectedLocalized;}}echo implode('|',[count(\$certs),\$totalQuestions,\$totalEn,\$totalLocalized,count(\$issues),implode('~',\$issues)]);" 2>/dev/null | tail -1 | tr -d '[:space:]' || echo "0|0|0|0|1|validation-error")

  IFS='|' read -r CERTIFICATION_COUNT QUESTION_COUNT EN_TRANSLATION_COUNT LOCALIZED_TRANSLATION_COUNT ISSUE_COUNT ISSUE_DETAILS <<< "$VALIDATION_RESULT"

  CERTIFICATION_COUNT=${CERTIFICATION_COUNT:-0}
  QUESTION_COUNT=${QUESTION_COUNT:-0}
  EN_TRANSLATION_COUNT=${EN_TRANSLATION_COUNT:-0}
  LOCALIZED_TRANSLATION_COUNT=${LOCALIZED_TRANSLATION_COUNT:-0}

  ISSUE_COUNT=${ISSUE_COUNT:-1}
  ISSUE_DETAILS=${ISSUE_DETAILS:-validation-error}

  if [ "$CERTIFICATION_COUNT" -eq 0 ] \
    || [ "$ISSUE_COUNT" -gt 0 ]; then
    echo "   Datos incompletos detectados (certificaciones=$CERTIFICATION_COUNT, preguntas=$QUESTION_COUNT, en=$EN_TRANSLATION_COUNT, locales=$LOCALIZED_TRANSLATION_COUNT)."
    echo "   Detalle por certificacion: $ISSUE_DETAILS"
    echo "   Ejecutando seeders para completar dataset..."

    DB_CONNECTION=mysql \
    DB_HOST=127.0.0.1 \
    DB_PORT=3306 \
    DB_DATABASE=certificados_dev \
    DB_USERNAME=laravel \
    DB_PASSWORD=secret \
    php artisan db:seed --no-interaction

    echo "   ✓ Datos de ejemplo cargados"
  else
    echo "   ✓ Dataset consistente detectado (certificaciones=$CERTIFICATION_COUNT, preguntas=$QUESTION_COUNT, en=$EN_TRANSLATION_COUNT, locales=$LOCALIZED_TRANSLATION_COUNT)."
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
