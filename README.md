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
- Motor de quiz con calificacion por umbral de aciertos/errores.
- Emision de certificado con serial unico.
- Consulta publica por serial y descarga PDF.
- Busqueda por serial o documento (hash de consulta).
- Selector de idioma + deteccion por navegador.
- Backoffice para gestion/importacion/exportacion de preguntas.

## Stack tecnico

- PHP 8.2+
- Laravel 11
- Livewire 4
- Tailwind CSS + Vite
- MySQL/MariaDB (SQLite en desarrollo si aplica)
- barryvdh/laravel-dompdf

## Inicio rapido local

1. Instalar dependencias backend:

```bash
composer install
```

2. Instalar dependencias frontend:

```bash
npm install
```

3. Preparar entorno:

```bash
cp .env.example .env
php artisan key:generate
```

4. Configurar base de datos en .env:

- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

5. Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

6. Levantar entorno de desarrollo:

```bash
composer run dev
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

- database/seeders/SocialEnergyQuestionsSeeder.php
- database/seeders/LifeStyleQuestionsSeeder.php

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
- Planificacion: docs/planificacion/
- Retencion de datos: docs/planificacion/POLITICA_RETENCION_DATOS.md
- Despliegue staging/produccion: docs/planificacion/DESPLIEGUE_STAGING_PRODUCCION.md

## Calidad y pruebas

```bash
php artisan test
php artisan optimize:clear
```

## Disclaimer

Proyecto satirico para entretenimiento.
No define identidad, valor o capacidades de ninguna persona.
