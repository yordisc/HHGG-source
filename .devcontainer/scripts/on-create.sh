#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# on-create.sh
# Se ejecuta UNA SOLA VEZ cuando el Codespace es creado por primera vez.
# Instala todas las dependencias del proyecto y configura el entorno.
# ─────────────────────────────────────────────────────────────────────────────

set -e  # Detener si cualquier comando falla

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Instituto de Certificaciones Dudosas™ — Setup inicial      ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# ─── 1. Extensiones PHP requeridas ──────────────────────────────────────────
echo "► Instalando extensiones PHP necesarias..."
sudo apt-get update -qq
sudo apt-get install -y -qq \
  php8.2-bcmath \
  php8.2-xml \
  php8.2-curl \
  php8.2-mbstring \
  php8.2-mysql \
  php8.2-gd \
  php8.2-zip \
  php8.2-intl \
  libfreetype6-dev \
  libjpeg62-turbo-dev \
  libpng-dev \
  default-mysql-client \
  unzip

echo "   ✓ Extensiones PHP instaladas"

# ─── 2. Verificar que Composer está disponible ──────────────────────────────
echo ""
echo "► Verificando Composer..."
if ! command -v composer &> /dev/null; then
  echo "   Instalando Composer..."
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
fi
echo "   ✓ Composer $(composer --version --no-ansi | head -1)"

# ─── 3. Verificar Node.js ───────────────────────────────────────────────────
echo ""
echo "► Verificando Node.js..."
echo "   ✓ Node.js $(node --version) / npm $(npm --version)"

# ─── 4. Instalar el proyecto Laravel (solo si no existe aún) ────────────────
WORKSPACE_DIR="/workspaces/$(basename $(pwd))"
cd "$WORKSPACE_DIR" 2>/dev/null || cd /workspaces/*/ 2>/dev/null || true
CURRENT_DIR=$(pwd)

echo ""
echo "► Directorio de trabajo: $CURRENT_DIR"

# Verificar si ya existe un proyecto Laravel
if [ ! -f "$CURRENT_DIR/artisan" ]; then
  echo ""
  echo "► No se detectó proyecto Laravel. Creando proyecto nuevo..."
  echo "   (Esto puede tardar 2-3 minutos)"
  echo ""

  # Crear el proyecto Laravel en una carpeta temporal y moverlo
  cd /tmp
  composer create-project laravel/laravel certificados-app --prefer-dist --no-interaction 2>&1 | tail -5

  # Mover los archivos al workspace
  cp -r /tmp/certificados-app/. "$CURRENT_DIR/"
  echo "   ✓ Proyecto Laravel creado"
else
  echo "   ✓ Proyecto Laravel ya existe — saltando creación"
fi

cd "$CURRENT_DIR"

# ─── 5. Instalar dependencias Composer del proyecto ─────────────────────────
echo ""
echo "► Instalando dependencias PHP (Composer)..."
composer install --no-interaction --prefer-dist 2>&1 | tail -5
echo "   ✓ Dependencias PHP instaladas"

# ─── 6. Instalar paquetes específicos del proyecto ──────────────────────────
echo ""
echo "► Instalando paquetes específicos del proyecto..."

# Livewire (componentes interactivos)
composer require livewire/livewire --no-interaction 2>&1 | tail -3

# DomPDF (generación de certificados PDF)
composer require barryvdh/laravel-dompdf --no-interaction 2>&1 | tail -3

# QR Code (para el certificado PDF)
composer require simplesoftwareio/simple-qrcode --no-interaction 2>&1 | tail -3

# Lista de países del mundo
composer require league/iso3166 --no-interaction 2>&1 | tail -3

echo "   ✓ Paquetes del proyecto instalados"

# ─── 7. Instalar dependencias NPM ───────────────────────────────────────────
echo ""
echo "► Instalando dependencias JavaScript (npm)..."
npm install 2>&1 | tail -5
npm install -D tailwindcss postcss autoprefixer 2>&1 | tail -3
npm install alpinejs 2>&1 | tail -3
echo "   ✓ Dependencias JavaScript instaladas"

# ─── 8. Configurar Tailwind CSS ─────────────────────────────────────────────
echo ""
echo "► Configurando Tailwind CSS..."

# Solo inicializar si no existe ya
if [ ! -f "tailwind.config.js" ]; then
  npx tailwindcss init -p 2>&1 | tail -3
fi

# Escribir configuración de Tailwind con paths correctos
cat > tailwind.config.js << 'TAILWIND_EOF'
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./app/Livewire/**/*.php",
    "./vendor/livewire/**/*.blade.php",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
TAILWIND_EOF

echo "   ✓ Tailwind CSS configurado"

# ─── 9. Configurar el archivo .env ──────────────────────────────────────────
echo ""
echo "► Configurando archivo .env..."

if [ ! -f ".env" ]; then
  cp .env.example .env
  echo "   Archivo .env creado desde .env.example"
fi

# Configurar conexión a MySQL local del Codespace
sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env 2>/dev/null || true
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sed -i 's/# DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/# DB_PORT=.*/DB_PORT=3306/' .env
sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
sed -i 's/# DB_DATABASE=.*/DB_DATABASE=certificados_dev/' .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=certificados_dev/' .env
sed -i 's/# DB_USERNAME=.*/DB_USERNAME=laravel/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=laravel/' .env
sed -i 's/# DB_PASSWORD=.*/DB_PASSWORD=secret/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=secret/' .env

# Configurar caché y sesión en base de datos (sin Redis)
sed -i 's/CACHE_STORE=.*/CACHE_STORE=database/' .env
sed -i 's/SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env
sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/' .env

# Agregar variables del proyecto si no existen
grep -q "LINKEDIN_ORG_ID" .env || echo "" >> .env
grep -q "LINKEDIN_ORG_ID" .env || echo "# LinkedIn - Agregar ID de tu página de empresa" >> .env
grep -q "LINKEDIN_ORG_ID" .env || echo "LINKEDIN_ORG_ID=" >> .env
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
