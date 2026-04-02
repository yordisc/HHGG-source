# Flujo de despliegue: staging y producción

Última actualización: 2026-04-02

## Objetivo

Estandarizar cómo desplegar CertificacionHHGG en staging y producción de forma segura y repetible.

## Requisitos previos

- PHP 8.4+
- Composer
- Node.js + npm
- Base de datos (MySQL/MariaDB o SQLite según entorno)
- Variables de entorno configuradas

## Variables de entorno mínimas

- APP_NAME
- APP_ENV
- APP_KEY
- APP_DEBUG
- APP_URL
- DB_CONNECTION
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD
- APP_LOCALE
- APP_FALLBACK_LOCALE
- ADMIN_ACCESS_KEY
- LINKEDIN_ORG_ID (opcional)

## Proceso recomendado de release

1. Preparación de rama
- Trabajar en rama feature.
- Ejecutar pruebas locales:
  - php artisan test
- Verificar estado general:
  - php artisan optimize:clear

2. Integración
- Abrir PR hacia main.
- Revisar cambios y aprobar.
- Merge a main.

3. Despliegue en staging
- Actualizar código en servidor staging.
- Instalar dependencias:
  - composer install --no-interaction --prefer-dist --optimize-autoloader
  - npm ci
  - npm run build
- Ejecutar migraciones:
  - php artisan migrate --force
- Limpiar y cachear configuración/rutas/vistas:
  - php artisan optimize
- Verificar scheduler:
  - cron o servicio para php artisan schedule:run cada minuto

4. Prueba de humo en staging
- Home carga correctamente.
- Registro de quiz funciona.
- Resultado/certificado/PDF responden.
- Login admin funciona.
- Import/export/template CSV funcionan.

5. Promoción a producción
- Repetir pasos de despliegue de staging en producción.
- APP_ENV=production
- APP_DEBUG=false
- Confirmar APP_KEY configurada.
- Confirmar respaldos de base de datos.

6. Verificación post-despliegue
- Revisar logs de aplicación.
- Confirmar que scheduler ejecuta:
  - certificates:clean
- Validar endpoints clave:
  - /
  - /admin/login
  - /cert/{serial}

## Checklists operativos

### Checklist staging

- Variables de entorno correctas
- Migrate exitoso
- Assets build generados
- Tests/smoke OK
- Scheduler activo

### Checklist producción

- Backup previo aplicado
- APP_DEBUG en false
- ADMIN_ACCESS_KEY segura
- HTTPS y headers de seguridad activos
- Scheduler activo
- Monitoreo/logs revisados

## Rollback rápido

1. Volver a release anterior (código).
2. Limpiar caches:
- php artisan optimize:clear
3. Si una migración causó problema, aplicar rollback controlado:
- php artisan migrate:rollback --step=1
4. Revalidar pruebas de humo.

## Notas de operación

- El proyecto ya incluye observabilidad básica vía logs estructurados y métricas en cache.
- Mantener una cadencia de despliegue corta y reversible.
