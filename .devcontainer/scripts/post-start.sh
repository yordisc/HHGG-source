#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# post-start.sh
# Se ejecuta CADA VEZ que el Codespace arranca (no solo la primera vez).
# Verifica que la base de datos esté lista y ejecuta migraciones si es necesario.
# ─────────────────────────────────────────────────────────────────────────────

set -e

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║   Instituto de Certificaciones Dudosas™ — Iniciando...       ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

WORKSPACE_DIR="/workspaces/$(ls /workspaces/ | head -1)"
cd "$WORKSPACE_DIR"

# ─── 1. Esperar a que MySQL esté disponible ──────────────────────────────────
echo "► Esperando a que MySQL esté listo..."
MAX_TRIES=30
TRIES=0

until mysqladmin ping -h 127.0.0.1 -u laravel -psecret --silent 2>/dev/null; do
  TRIES=$((TRIES + 1))
  if [ $TRIES -ge $MAX_TRIES ]; then
    echo "   ✗ MySQL no respondió después de ${MAX_TRIES} intentos"
    echo "   Intenta reiniciar el Codespace o revisar el contenedor MySQL"
    exit 1
  fi
  echo "   Intento $TRIES/$MAX_TRIES — esperando 3 segundos..."
  sleep 3
done

echo "   ✓ MySQL está disponible"

# ─── 2. Ejecutar migraciones (si hay nuevas o es la primera vez) ────────────
echo ""
echo "► Ejecutando migraciones de base de datos..."

# Crear tabla de caché si no existe
php artisan cache:table 2>/dev/null || true
php artisan session:table 2>/dev/null || true

# Ejecutar migraciones
php artisan migrate --no-interaction --force 2>&1

echo "   ✓ Migraciones ejecutadas"

# ─── 3. Ejecutar seeders (solo si la tabla questions está vacía) ─────────────
echo ""
echo "► Verificando datos de ejemplo en la base de datos..."

QUESTION_COUNT=$(php artisan tinker --execute="echo \App\Models\Question::count();" 2>/dev/null | tail -1 | tr -d '[:space:]' || echo "0")

if [ "$QUESTION_COUNT" = "0" ] || [ -z "$QUESTION_COUNT" ]; then
  echo "   No hay preguntas en la DB — ejecutando seeders..."
  php artisan db:seed --no-interaction 2>&1
  echo "   ✓ Datos de ejemplo cargados"
else
  echo "   ✓ Ya existen $QUESTION_COUNT preguntas en la DB (saltando seeders)"
fi

# ─── 4. Limpiar caché de desarrollo ─────────────────────────────────────────
echo ""
echo "► Limpiando caché de desarrollo..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
echo "   ✓ Caché limpiada"

# ─── 5. Verificar que todos los archivos necesarios existen ─────────────────
echo ""
echo "► Verificando estructura del proyecto..."

MISSING=0

check_dir() {
  if [ ! -d "$1" ]; then
    echo "   ⚠ Falta carpeta: $1"
    mkdir -p "$1"
    MISSING=$((MISSING + 1))
  fi
}

check_file() {
  if [ ! -f "$1" ]; then
    echo "   ⚠ Falta archivo: $1"
    MISSING=$((MISSING + 1))
  fi
}

check_dir "app/Livewire"
check_dir "app/Services"
check_dir "app/Console/Commands"
check_dir "resources/views/layouts"
check_dir "resources/views/livewire"
check_dir "resources/views/cert"
check_dir "resources/views/pdf"
check_file ".env"
check_file "artisan"

if [ $MISSING -gt 0 ]; then
  echo "   Se crearon $MISSING elementos faltantes"
else
  echo "   ✓ Estructura del proyecto correcta"
fi

# ─── 6. Mostrar estado del proyecto ─────────────────────────────────────────
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
echo "║   Crear nuevo componente Livewire:                           ║"
echo "║   $ php artisan make:livewire NombreComponente               ║"
echo "║                                                              ║"
echo "║   Crear nueva migración:                                     ║"
echo "║   $ php artisan make:migration nombre_de_la_migracion        ║"
echo "║                                                              ║"
echo "║   Consola interactiva (Tinker):                              ║"
echo "║   $ php artisan tinker                                       ║"
echo "║                                                              ║"
echo "║   Ver el plan del proyecto:                                  ║"
echo "║   $ cat plan_certificados_laravel.md                         ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
