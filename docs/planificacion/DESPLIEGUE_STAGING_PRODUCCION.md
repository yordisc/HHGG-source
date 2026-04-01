# Flujo de despliegue: staging y produccion

Ultima actualizacion: 2026-04-01

## Objetivo

Estandarizar como desplegar CertificacionHHGG en staging y produccion de forma segura y repetible.

## Requisitos previos

- PHP 8.4+
- Composer
- Node.js + npm
- Base de datos (MySQL/MariaDB o SQLite segun entorno)
- Variables de entorno configuradas

## Variables de entorno minimas

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

1. Preparacion de rama
- Trabajar en rama feature.
- Ejecutar pruebas locales:
  - php artisan test
- Verificar lint/estado general:
  - php artisan optimize:clear

2. Integracion
- Abrir PR hacia main.
- Revisar cambios y aprobar.
- Merge a main.

3. Deploy en staging
- Actualizar codigo en servidor staging.
- Instalar dependencias:
  - composer install --no-interaction --prefer-dist --optimize-autoloader
  - npm ci
  - npm run build
- Ejecutar migraciones:
  - php artisan migrate --force
- Limpiar y cachear config/rutas/vistas:
  - php artisan optimize
- Verificar scheduler:
  - cron o servicio para php artisan schedule:run cada minuto

4. Smoke test en staging
- Home carga correctamente.
- Registro de quiz funciona.
- Resultado/certificado/PDF responden.
- Login admin funciona.
- Import/export/template CSV funcionan.

5. Promocion a produccion
- Repetir pasos de deploy de staging en produccion.
- APP_ENV=production
- APP_DEBUG=false
- Confirmar APP_KEY configurada.
- Confirmar backups de base de datos.

6. Verificacion post-deploy
- Revisar logs de aplicacion.
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

### Checklist produccion

- Backup previo aplicado
- APP_DEBUG en false
- ADMIN_ACCESS_KEY segura
- HTTPS y headers de seguridad activos
- Scheduler activo
- Monitoreo/logs revisados

## Rollback rapido

1. Volver a release anterior (codigo).
2. Limpiar caches:
- php artisan optimize:clear
3. Si una migracion causó problema, aplicar rollback controlado:
- php artisan migrate:rollback --step=1
4. Revalidar smoke tests.

## Notas de operacion

- El proyecto ya incluye observabilidad basica via logs estructurados y metricas en cache.
- Mantener una cadencia de despliegue corta y reversible.
