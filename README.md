# CertificacionHHGG

Plataforma web satirica de certificados para entretenimiento, con apariencia formal y flujo completo de evaluacion, resultado, certificado publico y descarga en PDF.

No es una certificacion real ni sustituye evaluaciones medicas, psicologicas o legales.

## Resumen rapido

- MVP funcional en Laravel 11 + Livewire 4.
- Quiz de 30 preguntas por certificacion.
- Certificado con serial unico y vista publica verificable.
- Descarga PDF y acceso rapido para LinkedIn.
- Soporte multilenguaje (7 idiomas).
- Reglas de limite de intento y limpieza automatica diaria.

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
- MySQL
- barryvdh/laravel-dompdf

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

- APP_NAME
- APP_ENV
- APP_URL
- APP_LOCALE
- APP_FALLBACK_LOCALE
- ADMIN_ACCESS_KEY (requerida para panel admin)
- LINKEDIN_ORG_ID (opcional)

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

- database/seeders/SocialEnergyQuestionsSeeder.php (certificado `hetero`)
- database/seeders/LifeStyleQuestionsSeeder.php (certificado `good_girl`)

## Panel admin

- Login: /admin/login
- Gestion de preguntas: /admin/questions
- Importar/Exportar CSV: /admin/questions
- Plantilla CSV: disponible desde /admin/questions

Requiere ADMIN_ACCESS_KEY en entorno.

## Operacion y mantenimiento

- Limpieza diaria de certificados: php artisan certificates:clean
- Scheduler definido en routes/console.php

En produccion, ejecutar schedule:run cada minuto via cron.

## Documentacion

- Indice general: docs/README.md
- Documento detallado: docs/PROYECTO_DETALLADO.md
- Guia de troubleshooting: docs/TROUBLESHOOTING.md
- Guia visual del builder: docs/VISUAL_BUILDER_GUIDE.md
- Sistema de versionado: docs/VERSIONING_SYSTEM.md
- Alta de nuevas certificaciones: scripts/README.md

## Calidad y pruebas

```bash
php artisan test
php artisan optimize:clear
```

## Disclaimer

Proyecto satirico para entretenimiento.
No define identidad, valor o capacidades de ninguna persona.
