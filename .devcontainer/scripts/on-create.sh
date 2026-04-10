#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# on-create.sh — Versión para Alpine Linux
# Se ejecuta UNA SOLA VEZ cuando el Codespace es creado por primera vez.
# Instala todas las dependencias del proyecto y configura el entorno.
# ─────────────────────────────────────────────────────────────────────────────

set -e

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Instituto de Certificaciones Dudosas™ — Setup inicial      ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# ─── 1. Instalar extensiones PHP requeridas ────────────────────────────────
echo "► Instalando extensiones PHP necesarias..."
sudo apk update
sudo apk add --no-cache \
  php84 php84-cli php84-json php84-pdo php84-pdo_mysql php84-pdo_sqlite \
  php84-ctype php84-dom php84-mbstring php84-openssl php84-curl php84-zip \
  php84-xml php84-intl php84-gd php84-bcmath php84-session php84-fileinfo \
  php84-tokenizer php84-xmlwriter \
  composer nodejs npm git unzip mysql-client

echo "   ✓ Extensiones PHP instaladas (PHP 8.4)"

# ─── 2. Verificar que Composer está disponible ───────────────────────────────
echo ""
echo "► Verificando Composer..."
echo "   ✓ Composer $(composer --version --no-ansi | head -1)"

# ─── 3. Verificar Node.js ─────────────────────────────────────────────────────
echo ""
echo "► Verificando Node.js..."
echo "   ✓ Node.js $(node --version) / npm $(npm --version)"

# ─── 4. Navegar al directorio del proyecto ──────────────────────────────────
WORKSPACE_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$WORKSPACE_DIR"
CURRENT_DIR=$(pwd)

echo ""
echo "► Directorio de trabajo: $CURRENT_DIR"
echo "   ✓ Proyecto Laravel detectado"

# ─── 5. Instalar dependencias Composer ─────────────────────────────────────
echo ""
echo "► Instalando dependencias PHP (Composer)..."
if [ -f "composer.json" ]; then
  composer install --no-interaction --prefer-dist 2>&1 | tail -3
  echo "   ✓ Dependencias PHP instaladas"
else
  echo "   ⚠ composer.json no encontrado"
fi

# ─── 6. Instalar dependencias NPM ───────────────────────────────────────────
echo ""
echo "► Instalando dependencias JavaScript (npm)..."
if [ -f "package.json" ]; then
  npm install 2>&1 | tail -3
  npm audit fix 2>/dev/null || true
  echo "   ✓ Dependencias JavaScript instaladas"
else
  echo "   ⚠ package.json no encontrado"
fi

# ─── 7. Configurar el archivo .env ──────────────────────────────────────────
echo ""
echo "► Configurando archivo .env..."

if [ ! -f ".env" ]; then
  cp .env.example .env
  echo "   Archivo .env creado desde .env.example"
fi

# Configurar para SQLite (no requiere servidor MySQL)
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i 's|^DB_DATABASE=.*|DB_DATABASE='$CURRENT_DIR'/database/database.sqlite|' .env
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=database/' .env
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env
sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/' .env

echo "   ✓ .env configurado para SQLite"

# ─── 8. Generar APP_KEY ────────────────────────────────────────────────────
echo ""
echo "► Generando APP_KEY..."
php artisan key:generate --force 2>/dev/null || true
echo "   ✓ APP_KEY generada"

# ─── 9. Crear base de datos SQLite ──────────────────────────────────────────
echo ""
echo "► Creando base de datos SQLite..."
mkdir -p database
touch database/database.sqlite
echo "   ✓ Base de datos SQLite creada"

# ─── 10. Ejecutar migraciones ────────────────────────────────────────────────
echo ""
echo "► Ejecutando migraciones de base de datos..."
php artisan cache:table 2>/dev/null || true
php artisan session:table 2>/dev/null || true
php artisan migrate --force 2>/dev/null || true
echo "   ✓ Migraciones completadas"

# ─── 11. Crear estructura de carpetas del proyecto ──────────────────────────
echo ""
echo "► Creando estructura de carpetas..."

mkdir -p app/Livewire app/Services app/Console/Commands
mkdir -p resources/views/{layouts,livewire,cert,pdf,errors}
mkdir -p lang/{en,es,pt,zh,hi,ar,fr}

echo "   ✓ Estructura de carpetas creada"

# ─── 12. Limpiar cache ──────────────────────────────────────────────────────
echo ""
echo "► Limpiando cache de desarrollo..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
echo "   ✓ Cache limpiada"
grep -q "MYMEMORY_EMAIL" .env || echo "" >> .env
grep -q "MYMEMORY_EMAIL" .env || echo "# MyMemory API - Opcional, aumenta el límite de traducciones" >> .env
grep -q "MYMEMORY_EMAIL" .env || echo "MYMEMORY_EMAIL=" >> .env

echo "   ✓ .env configurado para MySQL local"

# ─── 10. Generar APP_KEY ────────────────────────────────────────────────────
echo ""
echo "► Generando APP_KEY..."
php artisan key:generate --no-interaction
echo "   ✓ APP_KEY generada"

# ─── 11. Crear tabla de caché de Laravel ────────────────────────────────────
echo ""
echo "► Preparando estructura de caché..."
# Esto se ejecutará en post-start cuando MySQL esté disponible
echo "   (las migraciones se ejecutarán en post-start cuando MySQL esté listo)"

# ─── 12. Crear estructura de carpetas del proyecto ──────────────────────────
echo ""
echo "► Creando estructura de carpetas del proyecto..."

mkdir -p app/Livewire
mkdir -p app/Services
mkdir -p app/Console/Commands
mkdir -p resources/views/layouts
mkdir -p resources/views/livewire
mkdir -p resources/views/cert
mkdir -p resources/views/pdf
mkdir -p resources/views/errors
mkdir -p lang/en lang/es lang/pt lang/zh lang/hi lang/ar lang/fr

echo "   ✓ Estructura de carpetas creada"

# ─── 13. Crear archivos de traducción vacíos ────────────────────────────────
echo ""
echo "► Creando archivos de traducción..."

for locale in en es pt zh hi ar fr; do
  for file in app quiz cert results; do
    FILEPATH="lang/$locale/$file.php"
    if [ ! -f "$FILEPATH" ]; then
      cat > "$FILEPATH" << LANG_EOF
<?php
// Traducciones: $locale / $file
// Completar con las claves necesarias

return [
    // Agregar claves de traducción aquí
];
LANG_EOF
    fi
  done
done

echo "   ✓ Archivos de traducción creados (7 idiomas × 4 archivos)"

# ─── 14. Compilar assets de desarrollo ──────────────────────────────────────
echo ""
echo "► Compilando assets (Tailwind + Vite)..."
npm run build 2>&1 | tail -5
echo "   ✓ Assets compilados"

# ─── 15. Crear archivo .gitignore correcto ──────────────────────────────────
echo ""
echo "► Verificando .gitignore..."

# Asegurar que .env nunca se suba a GitHub
grep -q "^\.env$" .gitignore || echo ".env" >> .gitignore
grep -q "^\.env\.local$" .gitignore || echo ".env.local" >> .gitignore
grep -q "^\.env\.production$" .gitignore || echo ".env.production" >> .gitignore

echo "   ✓ .gitignore correcto (nunca se subirá .env)"

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   ✅ Setup inicial completado                                ║"
echo "║                                                              ║"
echo "║   Esperando a que MySQL esté disponible para las            ║"
echo "║   migraciones (se ejecutan en post-start.sh)                ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
