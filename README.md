# CertificacionHHGG

Plataforma web humoristica de certificados para entretenimiento.

Este proyecto genera resultados tipo certificacion con apariencia profesional, pero con enfoque satirico.
No reemplaza evaluaciones medicas, psicologicas o legales.

## Estado actual

MVP funcional implementado en Laravel 11 + Livewire 4, con i18n, quiz, generacion de certificado, PDF, busqueda publica, limites de intento y limpieza programada.

## Funcionalidades principales

- Home con seleccion de certificaciones.
- Registro de candidato por tipo de certificado.
- Quiz de 30 preguntas aleatorias.
- Resultado final segun aciertos/errores.
- Emision de certificado con serial unico.
- Vista publica de certificado por serial.
- Descarga de PDF del certificado.
- Boton para agregar certificacion en LinkedIn.
- Busqueda por serial o documento (hash de consulta).
- Selector de idioma + deteccion por navegador.
- Limite de intento por dia y regla de renovacion.
- Limpieza automatica diaria de certificados vencidos.

## Stack

- PHP 8.2+
- Laravel 11
- Livewire 4
- Tailwind + Vite (npm)
- MySQL o MariaDB
- DomPDF

## Instalacion local

1. Clonar repositorio e instalar dependencias de PHP:

```bash
composer install
```

2. Instalar dependencias frontend:

```bash
npm install
```

3. Configurar entorno:

```bash
cp .env.example .env
php artisan key:generate
```

4. Configurar base de datos en .env (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).

5. Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

6. Iniciar entorno de desarrollo:

```bash
composer run dev
```

## Variables de entorno recomendadas

- APP_NAME
- APP_ENV
- APP_URL
- APP_LOCALE
- APP_FALLBACK_LOCALE
- ADMIN_ACCESS_KEY (requerida para acceder al panel admin de preguntas)
- LINKEDIN_ORG_ID (opcional, para precargar organizacion en LinkedIn)

## Rutas principales

- / : inicio
- /search : busqueda de certificado
- /exam/{certType}/register : registro de candidato
- /exam/start : inicio de intento quiz
- /exam/{certType} : ejecucion del quiz
- /result/{serial} : pantalla de resultado
- /cert/{serial} : certificado publico
- /cert/{serial}/pdf : descarga PDF
- /locale/{locale} : cambio de idioma

## Idiomas

Idiomas habilitados:

- en
- es
- pt
- zh
- hi
- ar
- fr

Archivos de traduccion en lang/{locale}/app.php.

## Banco de preguntas

Tablas clave:

- questions
- question_translations

Actualmente el quiz consume preguntas base desde questions. La tabla question_translations ya existe para evolucionar a preguntas por idioma.

Seeders iniciales:

- database/seeders/SocialEnergyQuestionsSeeder.php
- database/seeders/LifeStyleQuestionsSeeder.php

Panel admin de preguntas:

- Login: /admin/login (requiere ADMIN_ACCESS_KEY)
- Importar CSV: /admin/questions
- Exportar CSV: /admin/questions
- Descargar plantilla CSV de ejemplo: /admin/questions (boton Plantilla CSV)

## Operacion y mantenimiento

- Comando de limpieza diaria: certificates:clean
- Programacion de scheduler: routes/console.php

Para ejecutar tareas programadas en servidor:

```bash
php artisan schedule:run
```

(o configurar cron para schedule:run cada minuto en produccion)

Documentacion operativa extendida:

- Indice de documentacion: docs/README.md
- Politica de retencion y borrado de datos: docs/planificacion/POLITICA_RETENCION_DATOS.md
- Flujo de despliegue staging/produccion: docs/planificacion/DESPLIEGUE_STAGING_PRODUCCION.md
- Documentacion detallada del proyecto: docs/PROYECTO_DETALLADO.md

## Calidad y pruebas

Comandos utiles:

```bash
php artisan test
php artisan optimize:clear
```

## Disclaimer

Proyecto satirico para entretenimiento.
No es una certificacion real y no determina identidad, valor o capacidades de una persona.
