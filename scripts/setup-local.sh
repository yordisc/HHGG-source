#!/usr/bin/env sh
set -eu

# Setup local para Laravel 11 compatible con Codespaces y Linux Mint.
# Objetivo: dejar el proyecto listo para desarrollo sin pasos manuales complejos.

PROJECT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
TMP_DIR="/tmp/certificados-app"
IS_CODESPACES="${CODESPACES:-false}"
SUDO=""
TARGET_PHP_VERSION="8.4"
TARGET_NODE_MAJOR="20"

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

disable_broken_apt_sources() {
  for source_file in /etc/apt/sources.list.d/* /etc/apt/sources.list; do
    [ -e "$source_file" ] || continue

    if grep -qi 'dl.yarnpkg.com/debian' "$source_file" 2>/dev/null; then
      log "[WARN] Desactivando repositorio roto de Yarn: $(basename "$source_file")"
      run_privileged sed -i 's|^deb \(.*dl\.yarnpkg\.com/debian.*\)$|# disabled by setup-local.sh: deb \1|g' "$source_file"
    fi
  done
}

detect_apt_codename() {
  if [ -r /etc/os-release ]; then
    . /etc/os-release

    if [ "${ID:-}" = "linuxmint" ] && [ -n "${UBUNTU_CODENAME:-}" ]; then
      printf '%s' "$UBUNTU_CODENAME"
      return 0
    fi

    if [ -n "${VERSION_CODENAME:-}" ]; then
      printf '%s' "$VERSION_CODENAME"
      return 0
    fi

    if [ -n "${UBUNTU_CODENAME:-}" ]; then
      printf '%s' "$UBUNTU_CODENAME"
      return 0
    fi
  fi

  printf '%s' "bullseye"
}

ensure_sury_php_repo() {
  if grep -Rqi 'packages.sury.org/php' /etc/apt/sources.list /etc/apt/sources.list.d 2>/dev/null; then
    return 0
  fi

  log "[INFO] Añadiendo repositorio PHP de Sury..."
  run_privileged mkdir -p /etc/apt/keyrings

  if ! command -v curl >/dev/null 2>&1 || ! command -v gpg >/dev/null 2>&1; then
    log "[ERROR] curl y gpg son necesarios para añadir el repo PHP de Sury"
    return 1
  fi

  if ! curl -fsSL https://packages.sury.org/php/apt.gpg | run_privileged gpg --dearmor -o /etc/apt/keyrings/sury-php.gpg; then
    log "[ERROR] No se pudo importar la clave GPG de Sury"
    return 1
  fi

  codename="$(detect_apt_codename)"

  printf '%s\n' "deb [signed-by=/etc/apt/keyrings/sury-php.gpg] https://packages.sury.org/php/ ${codename} main" | run_privileged tee /etc/apt/sources.list.d/sury-php.list >/dev/null
}

ensure_nodesource_repo() {
  if grep -Rqi 'deb.nodesource.com/node_20.x' /etc/apt/sources.list /etc/apt/sources.list.d 2>/dev/null; then
    return 0
  fi

  log "[INFO] Añadiendo repositorio Node.js 20 (NodeSource)..."
  run_privileged mkdir -p /etc/apt/keyrings

  if ! command -v curl >/dev/null 2>&1 || ! command -v gpg >/dev/null 2>&1; then
    log "[ERROR] curl y gpg son necesarios para añadir el repo NodeSource"
    return 1
  fi

  if ! curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | run_privileged gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg; then
    log "[ERROR] No se pudo importar la clave GPG de NodeSource"
    return 1
  fi

  codename="$(detect_apt_codename)"

  printf '%s\n' "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x ${codename} main" | run_privileged tee /etc/apt/sources.list.d/nodesource-node20.list >/dev/null
}

has_mysql_driver() {
  php -r 'exit(extension_loaded("pdo_mysql") ? 0 : 1);' >/dev/null 2>&1
}

install_repo_prerequisites() {
  run_privileged apt-get install -y ca-certificates curl gnupg lsb-release
}

install_database_packages() {
  if run_privileged apt-get install -y default-mysql-server default-mysql-client; then
    return 0
  fi

  log "[WARN] No se pudo instalar default-mysql-server/client. Reintentando con MariaDB..."
  run_privileged apt-get install -y mariadb-server mariadb-client
}

ensure_runtime_requirements() {
  php_major_minor="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || true)"
  node_major="$(node -p 'process.versions.node.split(".")[0]' 2>/dev/null || true)"

  if [ "$php_major_minor" != "$TARGET_PHP_VERSION" ]; then
    log "[ERROR] Se requiere PHP ${TARGET_PHP_VERSION}. Detectado: ${php_major_minor:-desconocido}"
    exit 1
  fi

  if [ "$node_major" != "$TARGET_NODE_MAJOR" ]; then
    log "[ERROR] Se requiere Node.js ${TARGET_NODE_MAJOR}. Detectado: ${node_major:-desconocido}"
    exit 1
  fi

  if ! has_mysql_driver; then
    log "[ERROR] Falta la extension PDO MySQL para PHP ${TARGET_PHP_VERSION}."
    exit 1
  fi
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    log "[ERROR] Falta comando requerido: $1"
    exit 1
  fi
}

install_deps() {
  if command -v apt-get >/dev/null 2>&1; then
    log "[INFO] Detectado Debian/Ubuntu/Linux Mint. Instalando dependencias con apt-get..."
    php_version="$TARGET_PHP_VERSION"
    mysql_php_package="php${php_version}-mysql"
    php_package_prefix="php${php_version}"

    disable_broken_apt_sources
    run_privileged apt-get update -y
    install_repo_prerequisites

    if ! ensure_sury_php_repo; then
      log "[ERROR] No se pudo preparar el repo PHP de Sury."
      exit 1
    fi

    if ! ensure_nodesource_repo; then
      log "[ERROR] No se pudo preparar el repo NodeSource para Node.js ${TARGET_NODE_MAJOR}."
      exit 1
    fi

    run_privileged apt-get update -y

    if ! run_privileged apt-get install -y \
      "$php_package_prefix" "$php_package_prefix"-cli "$php_package_prefix"-mbstring "$php_package_prefix"-xml "$php_package_prefix"-curl "$mysql_php_package" "$php_package_prefix"-zip "$php_package_prefix"-gd \
      "$php_package_prefix"-bcmath "$php_package_prefix"-intl composer nodejs npm \
      git unzip curl rsync; then
      log "[ERROR] No se pudieron instalar las dependencias requeridas con apt-get."
      exit 1
    fi

    if ! install_database_packages; then
      log "[ERROR] No se pudo instalar ni MySQL ni MariaDB."
      exit 1
    fi
  else
    log "[ERROR] Este bootstrap está pensado solo para Debian/apt."
    exit 1
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
  ensure_runtime_requirements
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
