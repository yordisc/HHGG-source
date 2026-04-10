#!/usr/bin/env sh
set -eu

# Setup local para Laravel 11 compatible con Codespaces y Linux Mint.
# Objetivo: dejar el proyecto listo para desarrollo sin pasos manuales complejos.

PROJECT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
TMP_DIR="/tmp/certificados-app"
IS_CODESPACES="${CODESPACES:-false}"
SUDO=""

log() {
  printf '%s\n' "$1"
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

start_mysql_service() {
  if service mysql start >/dev/null 2>&1; then
    return 0
  fi

  if service mariadb start >/dev/null 2>&1; then
    return 0
  fi

  log "[WARN] No se pudo iniciar el servicio MySQL/MariaDB automaticamente."
  return 1
}

wait_for_mysql() {
  for _ in $(seq 1 30); do
    if mysqladmin ping -h 127.0.0.1 -u root --silent >/dev/null 2>&1; then
      return 0
    fi

    sleep 1
  done

  return 1
}

configure_mysql_database() {
  mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS certificados_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON certificados_dev.* TO 'laravel'@'%';
FLUSH PRIVILEGES;
SQL
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

  log "[ERROR] Se requieren privilegios para instalar paquetes. Ejecuta el script con sudo o como root."
  exit 1
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    log "[ERROR] Falta comando requerido: $1"
    exit 1
  fi
}

install_deps() {
  if command -v apk >/dev/null 2>&1; then
    log "[INFO] Detectado Alpine. Instalando dependencias con apk..."
    run_privileged apk add --no-cache \
      php php-phar php-tokenizer php-xml php-xmlwriter php-mbstring php-openssl \
      php-curl php-pdo php-pdo_mysql php-pdo_sqlite php-zip php-dom php-fileinfo \
      php-session php-ctype php-json php-bcmath php-intl composer nodejs npm \
      sqlite mariadb mariadb-client git unzip curl rsync
  elif command -v apt-get >/dev/null 2>&1; then
    log "[INFO] Detectado Debian/Ubuntu/Linux Mint. Instalando dependencias con apt-get..."
    run_privileged apt-get update -y
    run_privileged apt-get install -y \
      php php-cli php-mbstring php-xml php-curl php-mysql php-zip \
      php-bcmath php-intl composer nodejs npm default-mysql-server default-mysql-client \
      git unzip curl rsync
  else
    log "[WARN] No se detecto apk ni apt-get. Se omite instalacion automatica de paquetes."
  fi
}

scaffold_laravel_if_missing() {
  if [ -f "$PROJECT_DIR/artisan" ]; then
    log "[INFO] Proyecto Laravel ya existe."
    return
  fi

  log "[INFO] Creando proyecto Laravel 11 en temporal..."
  rm -rf "$TMP_DIR"
  composer create-project laravel/laravel "$TMP_DIR" "^11.0" --no-interaction

  log "[INFO] Copiando base Laravel al repo (sin tocar PLANIFICACION ni .devcontainer)..."
  rsync -a --ignore-existing "$TMP_DIR"/ "$PROJECT_DIR"/

  log "[INFO] Proyecto base copiado."
}

install_project_packages() {
  cd "$PROJECT_DIR"

  log "[INFO] Instalando dependencias Composer del proyecto..."
  composer install --no-interaction --prefer-dist

  log "[INFO] Instalando dependencias frontend..."
  npm install
}

set_env_value() {
  key="$1"
  value="$2"
  env_file="$3"

  if grep -q "^${key}=" "$env_file"; then
    sed -i "s|^${key}=.*|${key}=${value}|" "$env_file"
  else
    printf '%s=%s\n' "$key" "$value" >> "$env_file"
  fi
}

ensure_optional_key() {
  key="$1"
  env_file="$2"

  grep -q "^${key}=" "$env_file" || printf '%s=\n' "$key" >> "$env_file"
}

configure_db_for_codespaces() {
  env_file="$1"

  log "[INFO] Configurando MySQL para Codespaces (editable en .env)..."

  set_env_value "DB_CONNECTION" "mysql" "$env_file"
  set_env_value "DB_HOST" "127.0.0.1" "$env_file"
  set_env_value "DB_PORT" "3306" "$env_file"
  set_env_value "DB_DATABASE" "certificados_dev" "$env_file"
  set_env_value "DB_USERNAME" "laravel" "$env_file"
  set_env_value "DB_PASSWORD" "secret" "$env_file"
}

configure_db_for_linuxmint() {
  env_file="$1"

  log "[INFO] Configurando MySQL por defecto para Linux local (editable en .env)..."

  set_env_value "DB_CONNECTION" "mysql" "$env_file"
  set_env_value "DB_HOST" "127.0.0.1" "$env_file"
  set_env_value "DB_PORT" "3306" "$env_file"
  set_env_value "DB_DATABASE" "certificados_dev" "$env_file"
  set_env_value "DB_USERNAME" "laravel" "$env_file"
  set_env_value "DB_PASSWORD" "secret" "$env_file"
}

prepare_env() {
  cd "$PROJECT_DIR"

  if [ ! -f .env ]; then
    cp .env.example .env
  fi

  if [ "$IS_CODESPACES" = "true" ]; then
    configure_db_for_codespaces .env
  else
    configure_db_for_linuxmint .env
  fi

  set_env_value "CACHE_STORE" "database" .env
  set_env_value "SESSION_DRIVER" "database" .env
  set_env_value "QUEUE_CONNECTION" "database" .env

  ensure_optional_key "LINKEDIN_ORG_ID" .env
  ensure_optional_key "MYMEMORY_EMAIL" .env

  php artisan key:generate --force
}

prepare_mysql() {
  log "[INFO] Iniciando MySQL/MariaDB..."
  start_mysql_service || true

  if ! wait_for_mysql; then
    log "[ERROR] MySQL/MariaDB no respondió en el tiempo esperado."
    exit 1
  fi

  log "[INFO] Configurando base de datos y usuario de desarrollo..."
  configure_mysql_database
}

run_migrations_if_possible() {
  cd "$PROJECT_DIR"

  log "[INFO] Intentando ejecutar migraciones..."
  if php artisan migrate --force; then
    log "[OK] Migraciones ejecutadas correctamente."
  else
    log "[WARN] No se pudieron ejecutar migraciones automaticamente."
    log "[WARN] Revisa credenciales DB en .env y luego ejecuta: php artisan migrate --seed"
  fi
}

post_setup_info() {
  log ""
  log "[OK] Setup completado."
  log "Siguientes comandos:"
  log "1) php artisan migrate --seed"
  log "2) php artisan serve"
  log "3) npm run dev"
  if [ "$IS_CODESPACES" = "true" ]; then
    log ""
    log "[INFO] Detectado Codespaces: DB configurada con MySQL en .env"
  else
    log ""
    log "[INFO] Detectado Linux local: DB configurada con MySQL en .env"
  fi
  log ""
}

main() {
  detect_sudo
  require_cmd sh
  install_deps
  require_cmd mysqladmin
  require_cmd mysql
  require_cmd composer
  require_cmd npm
  scaffold_laravel_if_missing
  install_project_packages
  prepare_mysql
  prepare_env
  run_migrations_if_possible
  post_setup_info
}

main "$@"
