# Despliegue en Render con Neon o Aiven

Esta guia mantiene el desarrollo local y en Codespaces en MySQL, pero prepara la app para produccion con una base de datos externa.

## Arquitectura recomendada

- Render: servicio web con el Dockerfile de la raiz.
- Neon: PostgreSQL administrado.
- Aiven: MySQL administrado si prefieres seguir en MySQL.

## Variables de entorno

Para Render y la base de datos externa, define al menos:

- `APP_NAME`
- `APP_ENV=production`
- `APP_KEY`
- `APP_DEBUG=false`
- `APP_URL`
- `DB_CONNECTION=pgsql` para Neon o `DB_CONNECTION=mysql` para Aiven
- `DB_URL` o `DATABASE_URL` si tu proveedor entrega una cadena unica de conexion
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` si usas variables separadas
- `DB_SSLMODE=require` para PostgreSQL administrado
- `CACHE_STORE=database`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`
- `ADMIN_ACCESS_KEY` (solo para webhook de scheduler)

## Checklist exacta de variables

Usa esta lista para no dejar huecos al desplegar:

### Render

- `APP_NAME`
- `APP_ENV=production`
- `APP_KEY`
- `APP_DEBUG=false`
- `APP_URL`
- `LOG_CHANNEL=stack`
- `LOG_LEVEL=info`
- `CACHE_STORE=database`
- `SESSION_DRIVER=database`
- `SESSION_LIFETIME=120`
- `QUEUE_CONNECTION=database`
- `ADMIN_ACCESS_KEY` (solo para webhook de scheduler)
- `MAIL_MAILER=log` o el proveedor SMTP que uses
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

### Neon PostgreSQL

- `DB_CONNECTION=pgsql`
- `DB_URL` o `DATABASE_URL`
- `DB_SSLMODE=require`
- `DB_HOST` si tu cadena no incluye host
- `DB_PORT` si tu cadena no incluye puerto
- `DB_DATABASE` si tu cadena no incluye base
- `DB_USERNAME` si tu cadena no incluye usuario
- `DB_PASSWORD` si tu cadena no incluye clave

### Aiven MySQL

- `DB_CONNECTION=mysql`
- `DB_URL` o `DATABASE_URL` si Aiven te da una URL completa
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

### Recomendado para scheduler webhook

- `ADMIN_ACCESS_KEY`

### Opcional

- `LINKEDIN_ORG_ID`
- `MYMEMORY_EMAIL`
- `CERTIFICATE_MODE=demo` o `CERTIFICATE_MODE=official`

## Render

1. Crea el proyecto web en Render y apunta al repositorio.
2. Render detectara el `Dockerfile` de la raiz.
3. Configura las variables de entorno anteriores.
4. Usa el puerto `10000`; el contenedor ya expone `PORT` y sirve la app con Nginx + PHP-FPM.
5. Si quieres automatizar el esquema, el contenedor ejecuta `php artisan migrate --force` al iniciar.

## Scheduler

Para mantener la opcion gratuita, este blueprint deja fuera el cron interno de Render. Si necesitas ejecutar `php artisan schedule:run` cada minuto, usa un cron externo gratuito o programa una tarea aparte; si mas adelante cambias a un plan que soporte cron en Render, puedes añadir ese servicio sin tocar el resto.

El webhook requiere el header `X-Admin-Access-Key` con el valor de `ADMIN_ACCESS_KEY`.

## Neon

1. Crea la base PostgreSQL en Neon.
2. Copia la cadena de conexion.
3. Define `DB_CONNECTION=pgsql` y pasa la URL en `DB_URL` o `DATABASE_URL`.
4. Mantén `DB_SSLMODE=require`.

## Aiven

1. Crea la base MySQL en Aiven.
2. Copia host, puerto, base, usuario y clave, o usa la URL completa si te la dan.
3. Define `DB_CONNECTION=mysql` y rellena `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` o `DB_URL`.

## Desarrollo local y Codespaces

No cambies la configuracion local por esta guia. Los scripts existentes siguen usando MySQL local en Codespaces o en tu maquina, asi que puedes seguir ejecutando:

```bash
sh scripts/dev-local.sh
```

o:

```bash
sh scripts/local-test.sh
```

## Observacion operativa

La app usa comandos programados por Laravel. En produccion necesitas un cron o job externo que ejecute `php artisan schedule:run` cada minuto si mantienes el plan gratuito.

## Baseline de rendimiento (Fase 4)

Antes de evaluar una migracion de serving (por ejemplo FrankenPHP), toma una linea base con el runtime actual.

1. Despliega la version actual en Render.
2. Ejecuta desde una terminal local:

```bash
sh scripts/profile-serving.sh --url "https://tu-servicio.onrender.com" --requests 300
```

3. Si corres local en Docker, puedes agregar snapshot de memoria del contenedor:

```bash
sh scripts/profile-serving.sh --url "http://localhost:10000" --requests 300 --container certificacionhhgg-web
```

4. Revisa el reporte generado en `docs/benchmarks/phase4-baseline-*.md`.

Decision recomendada:

- Mantener stack actual si latencia y memoria son estables.
- Evaluar migracion de serving solo si hay presion de RAM sostenida o P95 alto de forma consistente.

## Estado de fases

- Fase 1: completada.
- Fase 2: completada.
- Fase 3: completada.
- Fase 4: en progreso.
