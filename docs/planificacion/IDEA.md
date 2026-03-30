# 🏛️ Instituto de Certificaciones Dudosas™

> Certificamos lo que nadie más se atreve.

Una aplicación web de broma, bien diseñada y completamente funcional, donde los usuarios
realizan cuestionarios para obtener "certificados oficiales". Los certificados son profesionales
en apariencia, compartibles en LinkedIn, descargables en PDF y verificables públicamente.

**Stack:** Laravel 11 · Livewire 3 · Tailwind CSS · MySQL · Railway  
**Costo de hosting:** $0

---

## Posicionamiento de Marca (Humor + Inclusion)

Esta plataforma se comunica con tono humoristico y satirico, pero sin promover discriminacion,
acoso o lenguaje degradante contra ningun grupo.

Lineas de comunicacion obligatorias:
- Es entretenimiento: no evaluacion psicologica, medica ni legal.
- Es inclusivo: respeta todas las identidades, orientaciones y estilos de vida.
- Es transparente: el resultado es un juego basado en preguntas aleatorias.

Disclaimer recomendado para header y footer:

"Sitio humoristico de entretenimiento. No es una certificacion real y no define el valor,
identidad ni capacidades de ninguna persona. Apoyamos y respetamos todas las mentalidades."

Recomendacion de marketing:
- Mantener el estilo institucional de broma (visual serio + contenido satirico).
- Usar copys divertidos, sin insultos directos ni etiquetas estigmatizantes.
- Reforzar el disclaimer antes del examen, al ver resultado y al descargar PDF.

---

## 🚀 Cómo usar este repositorio en GitHub Codespace

### Paso 1 — Crear el repositorio en GitHub

1. Ve a [github.com/new](https://github.com/new)
2. Nombre del repositorio: `certificados-app` (o el que prefieras)
3. Visibilidad: **Privado** (recomendado, para no exponer el `.env`)
4. No marcar ninguna opción adicional (sin README, sin .gitignore)
5. Clic en **Create repository**

### Paso 2 — Subir estos archivos al repositorio

```bash
# En tu máquina local, dentro de la carpeta de este proyecto:
git init
git remote add origin https://github.com/TU-USUARIO/certificados-app.git
git add .
git commit -m "chore: configuración inicial de Codespace"
git push -u origin main
```

### Paso 3 — Abrir el Codespace

1. Ve a tu repositorio en GitHub
2. Clic en el botón verde **`<> Code`**
3. Pestaña **Codespaces**
4. Clic en **"Create codespace on main"**
5. Esperar ~5 minutos (la primera vez instala todo automáticamente)

> La primera vez que abre el Codespace, el script `on-create.sh` instala
> todas las dependencias, configura la base de datos y deja todo listo.

### Paso 4 — Iniciar el servidor de desarrollo

Una vez que el Codespace esté listo, abrir **dos terminales** en VS Code:

**Terminal 1 — Servidor Laravel:**
```bash
php artisan serve
```

**Terminal 2 — Assets (Tailwind + Vite):**
```bash
npm run dev
```

El Codespace abrirá automáticamente una pestaña del navegador con la aplicación.

---

## 📋 Comandos útiles del día a día

```bash
# ── Laravel ──────────────────────────────────────────────────────────────────

# Ver todas las rutas definidas
php artisan route:list

# Crear un componente Livewire
php artisan make:livewire NombreComponente

# Crear un controlador
php artisan make:controller NombreController

# Crear una migración nueva
php artisan make:migration create_tabla_table

# Ejecutar migraciones
php artisan migrate

# Revertir la última migración
php artisan migrate:rollback

# Resetear y re-ejecutar todas las migraciones + seeders
php artisan migrate:fresh --seed

# Crear un seeder
php artisan make:seeder NombreSeeder

# Ejecutar un seeder específico
php artisan db:seed --class=NombreSeeder

# Consola interactiva (para probar queries, modelos, etc.)
php artisan tinker

# Crear un Form Request (validación)
php artisan make:request NombreRequest

# Crear un Artisan Command personalizado
php artisan make:command NombreCommand

# Limpiar todas las cachés de desarrollo
php artisan config:clear && php artisan route:clear && php artisan view:clear

# Ejecutar el scheduler manualmente (para probar el cleanup de expirados)
php artisan schedule:run

# Ejecutar el comando de limpieza de certificados expirados manualmente
php artisan certificates:clean


# ── Base de Datos ─────────────────────────────────────────────────────────────

# Ver el estado de las migraciones
php artisan migrate:status

# Conectar a MySQL directamente
mysql -h 127.0.0.1 -u laravel -psecret certificados_dev


# ── Assets ────────────────────────────────────────────────────────────────────

# Modo desarrollo con hot-reload
npm run dev

# Compilar para producción
npm run build
```

---

## 🗺️ Fases de desarrollo

Consulta el archivo `plan_certificados_laravel.md` para el plan detallado de las 12 fases.

Resumen rápido:

| Fase | Qué hacer |
|------|-----------|
| **0** | Configuración del entorno (ya hecho por `on-create.sh`) |
| **1** | Migraciones y modelos Eloquent |
| **2** | Servicios: QuizService, TranslationService, CertificateService, PDFService |
| **3** | Middleware: SetLocale, QuizRateLimit, SecurityHeaders |
| **4** | Rutas y controladores |
| **5** | Componentes Livewire: RegistrationForm, Quiz, SearchBar |
| **6** | Vistas Blade y layout principal |
| **7** | Template PDF del certificado (DomPDF) |
| **8** | Sistema de traducción de preguntas (MyMemory API + caché DB) |
| **9** | Internacionalización de la interfaz (7 idiomas) |
| **10** | Scheduled Command de limpieza de expirados |
| **11** | Despliegue en Railway |
| **12** | Polish y lanzamiento |

---

## 🗄️ Estructura del proyecto

```
certificados-app/
├── .devcontainer/              ← Configuración de GitHub Codespace
│   ├── devcontainer.json       ← Extensiones VS Code, puertos, scripts
│   ├── docker-compose.yml      ← Contenedor MySQL local
│   ├── init-db.sql             ← Inicialización de la base de datos
│   └── scripts/
│       ├── on-create.sh        ← Setup inicial (se ejecuta una sola vez)
│       └── post-start.sh       ← Arranque (se ejecuta en cada inicio)
│
├── app/
│   ├── Console/Commands/
│   │   └── CleanExpiredCertificates.php   ← (crear en Fase 10)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── HomeController.php          ← (crear en Fase 4)
│   │   │   ├── CertificateController.php   ← (crear en Fase 4)
│   │   │   └── LocaleController.php        ← (crear en Fase 4)
│   │   ├── Middleware/
│   │   │   ├── SetLocale.php               ← (crear en Fase 3)
│   │   │   ├── QuizRateLimit.php           ← (crear en Fase 3)
│   │   │   └── SecurityHeaders.php         ← (crear en Fase 3)
│   │   └── Requests/
│   │       └── StartQuizRequest.php        ← (crear en Fase 3)
│   ├── Livewire/
│   │   ├── RegistrationForm.php            ← (crear en Fase 5)
│   │   ├── Quiz.php                        ← (crear en Fase 5)
│   │   └── SearchBar.php                   ← (crear en Fase 5)
│   ├── Models/
│   │   ├── Certificate.php                 ← (crear en Fase 1)
│   │   ├── Question.php                    ← (crear en Fase 1)
│   │   ├── QuestionTranslation.php         ← (crear en Fase 1)
│   │   └── RateLimit.php                   ← (crear en Fase 1)
│   └── Services/
│       ├── QuizService.php                 ← (crear en Fase 2)
│       ├── CertificateService.php          ← (crear en Fase 2)
│       ├── TranslationService.php          ← (crear en Fase 2)
│       └── PDFService.php                  ← (crear en Fase 2)
│
├── database/
│   ├── migrations/                         ← (crear en Fase 1)
│   └── seeders/                            ← (crear en Fase 1)
│
├── lang/
│   ├── en/ es/ pt/ zh/ hi/ ar/ fr/         ← (completar en Fase 9)
│   │   ├── app.php
│   │   ├── quiz.php
│   │   ├── cert.php
│   │   └── results.php
│
├── resources/views/
│   ├── layouts/app.blade.php               ← (crear en Fase 6)
│   ├── home.blade.php                      ← (crear en Fase 6)
│   ├── results.blade.php                   ← (crear en Fase 6)
│   ├── cert/show.blade.php                 ← (crear en Fase 6)
│   ├── livewire/                           ← (crear en Fase 5)
│   └── pdf/certificate.blade.php           ← (crear en Fase 7)
│
├── routes/
│   ├── web.php                             ← (definir en Fase 4)
│   └── console.php                         ← (configurar en Fase 10)
│
├── .env.example                            ← ✅ Ya incluido
├── nixpacks.toml                           ← ✅ Ya incluido (para Railway)
└── plan_certificados_laravel.md            ← ✅ Plan completo del proyecto
```

---

## 🌐 Despliegue en Railway (producción)

1. Crear cuenta en [railway.app](https://railway.app) con tu cuenta de GitHub
2. **New Project** → **Deploy from GitHub repo** → seleccionar este repositorio
3. **Add Plugin** → **MySQL** (Railway crea la BD automáticamente)
4. En **Variables** del servicio, agregar:

   | Variable | Valor |
   |----------|-------|
   | `APP_KEY` | Copiar de tu `.env` local |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_URL` | `https://tu-app.up.railway.app` |
   | `DB_HOST` | Copiar del plugin MySQL de Railway |
   | `DB_PORT` | `3306` |
   | `DB_DATABASE` | `railway` |
   | `DB_USERNAME` | Copiar del plugin MySQL de Railway |
   | `DB_PASSWORD` | Copiar del plugin MySQL de Railway |
   | `CACHE_STORE` | `database` |
   | `SESSION_DRIVER` | `database` |
   | `QUEUE_CONNECTION` | `database` |
   | `LINKEDIN_ORG_ID` | ID de tu página de empresa |
   | `MYMEMORY_EMAIL` | Tu email (opcional) |

5. Railway hace el deploy automáticamente desde el `nixpacks.toml`
6. Para el Cron Job de limpieza de expirados: en Railway → **New Service** → **Cron** → comando `php artisan schedule:run` → frecuencia `* * * * *`

---

## 🔒 Seguridad

- **Nunca** hacer commit del archivo `.env` (está en `.gitignore`)
- Los números de documento se guardan **hasheados con bcrypt** (nunca en texto plano)
- Las respuestas correctas del quiz **nunca salen del servidor**
- Las IPs se hashean antes de guardarse

---

## 📄 Licencia

Proyecto personal de broma. Todos los certificados son ficticios y sin validez legal.

*"Este sitio es puramente humorístico. Los certificados no tienen validez legal,
médica, psicológica ni oficial de ningún tipo."*

---

## 🌍 Disclaimers multilingües

Para textos legales/inclusivos listos para pegar en home, resultado y PDF, ver:

- `docs/planificacion/DISCLAIMERS_I18N.md`
