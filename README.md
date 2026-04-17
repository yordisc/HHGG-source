# HHGG-source

Plataforma web satirica de certificados para entretenimiento, con apariencia formal y flujo completo de evaluacion, resultado, certificado publico y descarga en PDF.

No es una certificacion real ni sustituye evaluaciones medicas, psicologicas o legales.

## Resumen rapido

- MVP funcional en Laravel 11 + Livewire 4.
- Quiz de 30 preguntas por certificacion.
- Certificado con serial unico y vista publica verificable.
- Descarga PDF y acceso rapido para LinkedIn.
- Soporte multilenguaje (7 idiomas).
- Reglas de limite de intento y limpieza automatica diaria.

## Cambios recientes (2026-04-14)

- Seeders de preguntas reforzados para evitar duplicados en reseeds parciales.
- Traducciones localizadas ajustadas para preservar prompt real por pregunta y mapear opciones por locale.
- Plantilla de seeder (`CertificationSeederTemplate`) actualizada para ejecucion idempotente.
- Validacion de dataset en arranque dev endurecida (global + por certificacion).
- Suite de regresion de seeders agregada para prevenir regresiones funcionales.

## Caracteristicas principales

- Home con seleccion de certificaciones y buscador.
- Registro de candidato por tipo de certificacion.
- Motor de quiz con opciones remezcladas en cada intento y resultado por umbral de errores.
- Emision de certificado con serial unico.
- Consulta publica por serial y descarga PDF.
- Busqueda por serial o documento (hash de consulta).
- Selector de idioma + deteccion por navegador.
- Backoffice para gestion/importacion/exportacion de preguntas.

## Stack tecnico

- PHP 8.4+
- Laravel 11
- Livewire 4
- Tailwind CSS + Vite
- MySQL / PostgreSQL
- barryvdh/laravel-dompdf

## Produccion vs Local

- Produccion Docker: Nginx + PHP-FPM (no usa `php artisan serve`).
- Local/Codespaces: se mantiene flujo tradicional con `php artisan serve` y scripts en `scripts/`.
- Proxies inversos: la app confia en proxy headers para IP real del cliente.
- Sesiones y cache: recomendado Redis en produccion; local puede seguir con `database`.
- Cola de trabajos: recomendado `sync` para evitar dependencias extra en despliegues simples.

## Inicio rapido local

Para preparar el entorno y validar la suite en un solo paso:

```bash
sh scripts/local-test.sh
```

El script instala dependencias, crea `.env` si falta, usa MySQL local, ejecuta migraciones y seeders, y lanza `php artisan test`.

Para levantar el stack de desarrollo de inmediato:

```bash
sh scripts/dev-local.sh
```

Ese comando arranca `php artisan serve`, `queue:listen` y `npm run dev`. Si no tienes el driver `pdo_mysql`, instala soporte MySQL para PHP antes de arrancarlo. Si quieres validar antes de levantarlo, usa `sh scripts/dev-local.sh --all`.

Tambien puedes usar el modo de desarrollo con validacion previa:

```bash
sh scripts/dev-local.sh --all
```

## Variables de entorno clave

- APP_NAME / APP_ENV / APP_URL / APP_DEBUG
- APP_TIMEZONE / APP_LOCALE / APP_FALLBACK_LOCALE / APP_FAKER_LOCALE
- DB_CONNECTION / DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD
- DB_URL o DATABASE_URL (si tu proveedor entrega URL unica)
- DB_SSLMODE (recomendado en DB externa)
- CACHE_STORE / SESSION_DRIVER / SESSION_LIFETIME / QUEUE_CONNECTION
- REDIS_URL o REDIS_HOST / REDIS_PORT / REDIS_USERNAME / REDIS_PASSWORD
- ADMIN_ACCESS_KEY (requerida para panel admin y webhook scheduler)
- ENABLE_SANDBOX_SEED_DATA / SANDBOX_TEST_PASSWORD (solo local/dev)
- CERTIFICATE_MODE (demo u official)
- LINKEDIN_ORG_ID (opcional)
- MYMEMORY_EMAIL (opcional)
- MAIL_MAILER / MAIL_FROM_ADDRESS / MAIL_FROM_NAME
- VITE_APP_NAME

Plantillas disponibles:

- `.env.example` para desarrollo local.
- `.env.production.example` para despliegue en Render + Neon/Aiven + Upstash.

Consejo:

- Si ya tienes `.env`, puedes reordenarlo y actualizarlo tomando como base la estructura de `.env.example`.

## Despliegue en Render

La guia recomendada para produccion con Render y base de datos externa esta en [docs/DEPLOY_RENDER_NEON_AIVEN.md](docs/DEPLOY_RENDER_NEON_AIVEN.md). Ese flujo usa el `Dockerfile` de la raiz, pero no cambia el uso local ni en Codespaces, que siguen apoyandose en MySQL.

En produccion, ejecuta migraciones como paso independiente del despliegue (pipeline o comando remoto); el contenedor no corre migraciones automaticamente al arrancar.

Si quieres automatizar en un solo paso, usa `.github/workflows/deploy-production.yml`, que primero migra y luego dispara el deploy hook de Render.

### Scheduler en plan gratuito (sin cron interno)

La app expone un webhook protegido para disparar el scheduler:

- Ruta: `POST /api/webhooks/scheduler`
- Header requerido: `X-Admin-Access-Key: <ADMIN_ACCESS_KEY>`

Puedes usar cron-job.org (u otro scheduler externo) para llamar esta ruta cada minuto.

## Rutas principales

- /
- /search
- /exam/{certType}/register
- /exam/start
- /exam/{certType}
- /result/{serial}
- /cert/{serial}
- /cert/{serial}/pdf
- /locale/{locale}

## Idiomas soportados

- en
- es
- pt
- zh
- hi
- ar
- fr

Archivos de interfaz en lang/{locale}/app.php.

## Banco de preguntas

Tablas principales:

- questions
- question_translations

Seeders iniciales:

- database/seeders/HCertificationSeeder.php (certificado `hetero`)
- database/seeders/GCertificactionSeeder.php (certificado `good_girl`)

### Sandbox de pruebas (aislado de producción)

Para validar el sistema completo con datos no reales, existe un sandbox opcional:

- Certificacion de prueba: `sandbox_system_test` (`[TEST] Sandbox Sistema Completo`)
- Usuarios de prueba:
    - `qa.admin@example.test` (admin)
    - `qa.user1@example.test`
    - `qa.user2@example.test`

Control por entorno:

- `ENABLE_SANDBOX_SEED_DATA=true` habilita estos datos al correr `db:seed`.
- En produccion, estos seeders no se ejecutan aunque la variable este activa.

Contraseña de usuarios de prueba:

- `SANDBOX_TEST_PASSWORD` (por defecto: `Sandbox123!`).

Deshabilitar rapido desde admin:

- Entra a `/admin/certifications` y desactiva la certificacion `[TEST] Sandbox Sistema Completo` con el switch `active`.

## Panel admin

- Login: /admin/login
- Gestion de preguntas: /admin/questions
- Importar/Exportar CSV: /admin/questions
- Plantilla CSV: disponible desde /admin/questions

Requiere ADMIN_ACCESS_KEY en entorno.

Gestion de cuentas admin:

- Script recomendado: `bash scripts/manage-admin.sh --help`
- Permite agregar, modificar y eliminar (o revocar rol admin) de forma segura.

## Operacion y mantenimiento

- Limpieza diaria de certificados: php artisan certificates:clean
- Scheduler definido en routes/console.php
- Webhook scheduler externo: POST /api/webhooks/scheduler

En produccion, ejecutar schedule:run cada minuto por cron interno o via webhook externo si tu plan no soporta cron.

## Documentacion

- Indice general: docs/README.md
- Documento detallado: docs/PROYECTO_DETALLADO.md
- Guia de troubleshooting: docs/TROUBLESHOOTING.md
- Guia visual del builder: docs/VISUAL_BUILDER_GUIDE.md
- Sistema de versionado: docs/VERSIONING_SYSTEM.md
- Politica de ciclo de vida de archivos: docs/FILE_LIFECYCLE_POLICY.md
- Alta de nuevas certificaciones: scripts/README.md

## Politica de migrations

Reglas recomendadas para mantener esquema y rollback estables:

1. No renombrar migrations historicas ya ejecutadas en entornos compartidos.
2. Usar nombres declarativos: `accion + tabla/entidad + objetivo del cambio`.
3. Preferir nuevas migrations para cambios de esquema antes que editar migrations antiguas.
4. En `down()`, evitar perdida silenciosa de datos; usar validaciones previas (guard clauses) cuando aplique.
5. Si hay SQL especifico por motor (`mysql`/`pgsql`/`sqlite`), documentarlo en la propia migration.

Referencia interna de convencion y hardening aplicado:

- database/migrations/README.md
- docs/MIGRATIONS_AUDIT_AND_HARDENING_PLAN_2026-04-14.md

## Calidad y pruebas

```bash
php artisan test
php artisan test --filter=SeederRegressionTest
php artisan optimize:clear
```

## Disclaimer

Proyecto satirico para entretenimiento.
No define identidad, valor o capacidades de ninguna persona.
