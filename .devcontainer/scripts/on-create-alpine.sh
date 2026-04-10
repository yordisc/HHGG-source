#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# on-create-alpine.sh
# Versión para Alpine Linux
# Se ejecuta UNA SOLA VEZ cuando el Codespace es creado por primera vez.
# ─────────────────────────────────────────────────────────────────────────────

set -e

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Instituto de Certificaciones Dudosas™ — Setup inicial      ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# ─── 1. Extensiones PHP requeridas ──────────────────────────────────────────
echo "► Instalando extensiones PHP necesarias..."
apk update
apk add --no-cache \
  php php-cli php-json php-pdo php-pdo_mysql php-pdo_sqlite \
  php-ctype php-dom php-mbstring php-openssl php-curl php-zip \
  php-xml php-intl php-gd php-bcmath \
  mysql-client \
  unzip git

echo "   ✓ Extensiones PHP instaladas"

# ─── 1.1 Herramientas para tests y cobertura ───────────────────────────────
echo ""
echo "► Configurando herramientas para tests/cobertura..."

if command -v phpdbg >/dev/null 2>&1; then
  echo "   ✓ phpdbg disponible para cobertura"
else
  echo "   ⚠ phpdbg no disponible"
fi

if php -m | grep -qi '^xdebug$'; then
  echo "   ✓ xdebug disponible para cobertura"
else
  echo "   ⚠ xdebug no disponible (se podrá usar phpdbg si existe)"
fi

# ─── 2. Verificar que Composer está disponible ──────────────────────────────
echo ""
echo "► Verificando Composer..."
if ! command -v composer &> /dev/null; then
  echo "   Instalando Composer..."
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
echo "   ✓ Composer $(composer --version --no-ansi | head -1)"

# ─── 3. Verificar Node.js ───────────────────────────────────────────────────
echo ""
echo "► Verificando Node.js..."
echo "   ✓ Node.js $(node --version 2>/dev/null || echo 'no instalado') / npm $(npm --version 2>/dev/null || echo 'no instalado')"

# ─── 4. Trabajar en el directorio del workspace ────────────────────────────
WORKSPACE_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$WORKSPACE_DIR"
CURRENT_DIR=$(pwd)

echo ""
echo "► Directorio de trabajo: $CURRENT_DIR"

# ─── 5. Instalar dependencias Composer del proyecto ─────────────────────────
echo ""
echo "► Instalando dependencias PHP (Composer)..."
if [ -f "composer.json" ]; then
  composer install --no-interaction --prefer-dist 2>&1 | tail -5
  echo "   ✓ Dependencias PHP instaladas"
else
  echo "   ⚠ composer.json no encontrado"
fi

# ─── 6. Instalar dependencias NPM ───────────────────────────────────────────
echo ""
echo "► Instalando dependencias JavaScript (npm)..."
if [ -f "package.json" ]; then
  npm install 2>&1 | tail -5
  echo "   ✓ Dependencias JavaScript instaladas"
else
  echo "   ⚠ package.json no encontrado"
fi

# ─── 7. Configurar el archivo .env ──────────────────────────────────────────
echo ""
echo "► Configurando archivo .env..."

if [ ! -f ".env" ]; then
  if [ -f ".env.example" ]; then
    cp .env.example .env
    echo "   Archivo .env creado desde .env.example"
  else
    echo "   ⚠ .env.example no encontrado"
  fi
fi

# Generar APP_KEY si no existe
php artisan key:generate --force 2>/dev/null || true

# Configurar conexión a MySQL local
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=certificados_dev/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=laravel/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=secret/' .env

# Configurar caché y sesión en base de datos (sin Redis)
sed -i 's/CACHE_STORE=.*/CACHE_STORE=database/' .env
sed -i 's/SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env
sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/' .env

echo "   ✓ Archivo .env configurado"

# ─── 8. Crear tablas de caché y sesión ──────────────────────────────────────
echo ""
echo "► Preparando base de datos..."

php artisan cache:table 2>/dev/null || true
php artisan session:table 2>/dev/null || true

echo "   ✓ Base de datos preparada"

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Setup completado — El proyecto está listo                  ║"
echo "╚══════════════════════════════════════════════════════════════╝"
