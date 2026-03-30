# 📜 Plan de Proyecto: Página de Certificados (Broma)
> **Inspiración:** [ghcertified](https://github.com/FidelusAleksander/ghcertified)
> **Tipo:** Aplicación web de broma, responsive y multilingüe
> **Costo:** $0 absolutos
> **Stack:** Laravel 11 + Livewire + MySQL + Railway
> **Versión:** 3.0 (migrado de Next.js a Laravel)

---

## 📌 Índice

1. [Resumen del Proyecto](#resumen)
2. [Certificados Disponibles](#certificados)
3. [Stack Tecnológico 100% Gratuito](#stack)
4. [Arquitectura General](#arquitectura)
5. [Estructura de la Base de Datos](#base-de-datos)
6. [Sistema de Preguntas y Traducción](#sistema-de-preguntas)
7. [Formulario de Registro del Candidato](#formulario)
8. [Lógica de Calificación](#calificacion)
9. [Generación de Certificados PDF](#pdf)
10. [Vista Pública del Certificado por Serial](#vista-publica)
11. [Botón "Agregar a LinkedIn"](#linkedin)
12. [Sistema de Búsqueda y Descarga](#busqueda)
13. [Sistema de Expiración y Renovación](#expiracion)
14. [Seguridad y Cifrado](#seguridad)
15. [Límite de Solicitudes (Rate Limiting)](#rate-limit)
16. [Multilenguaje e i18n](#i18n)
17. [Diseño y UI/UX](#diseno)
18. [Estructura de Carpetas del Proyecto](#estructura)
19. [Dependencias de Composer y NPM](#dependencias)
20. [Fases de Desarrollo con Pasos Detallados](#fases)
21. [Diagrama de Flujo del Usuario](#flujo)
22. [Checklist Final](#checklist)

---

## 1. 📝 Resumen del Proyecto {#resumen}

Una página web de broma, bien diseñada y completamente funcional, donde los usuarios realizan cuestionarios para obtener "certificados oficiales" de broma. Los certificados son profesionales en apariencia, compartibles en LinkedIn, descargables en PDF y verificables públicamente mediante su serial en el navegador.

**Características clave:**
- Página principal con buscador prominente y tarjetas de los certificados disponibles
- Formulario de datos personales (nombre, apellido, documento, país) antes del examen
- Cuestionarios con 30 preguntas aleatorias de un banco extenso
- Cualquier persona puede presentar ambos certificados de forma independiente
- Generación dinámica de certificados PDF (no se almacenan archivos)
- Vista pública del certificado verificable usando solo el serial en la URL
- Botón de "Agregar a LinkedIn" en la pantalla de resultado
- Detección automática del idioma del navegador
- Base de datos de preguntas en inglés con traducción automática cacheada
- Multilingüe (7 idiomas), Responsive, Segura, $0 de costo

---

## 2. 🏅 Certificados Disponibles {#certificados}

### Certificado 1: Orientación Sexual
| Resultado | Condición |
|-----------|-----------|
| ✅ **Heterosexual Certificado/a** | Menos de 1/3 de respuestas incorrectas (máx. 9 errores) |
| ❌ **Homosexual Certificado/a** | 1/3 o más de respuestas incorrectas (10+ errores) |

### Certificado 2: Conducta Personal
| Resultado | Condición |
|-----------|-----------|
| ✅ **Niña Buena Certificada** | Menos de 1/3 de respuestas incorrectas (máx. 9 errores) |
| ❌ **Zorra Certificada** | 1/3 o más de respuestas incorrectas (10+ errores) |

> Cualquier persona puede presentar ambos certificados de forma independiente. Cada uno tiene su propio banco de preguntas, su propio serial y su propio PDF.

---

## 3. 🛠️ Stack Tecnológico 100% Gratuito {#stack}

| Capa | Tecnología | Por qué | Costo |
|------|-----------|---------|-------|
| **Framework PHP** | Laravel 11 | Todo en uno: rutas, ORM, scheduler, auth, i18n | Gratis |
| **UI Reactiva** | Livewire 3 + Alpine.js | Componentes interactivos sin JS framework completo | Gratis |
| **Estilos** | Tailwind CSS 3 | Responsive utility-first | Gratis |
| **Base de datos** | MySQL 8 en Railway | Hosting gratuito | Gratis |
| **Hosting app** | Railway (Free Tier) | $5 créditos/mes, más que suficiente | Gratis* |
| **Caché / Rate Limit** | Laravel Cache (DB driver) | Sin Redis externo necesario | Gratis |
| **Generación PDF** | barryvdh/laravel-dompdf | Genera PDF en servidor con HTML/CSS | Gratis |
| **Traducción** | MyMemory API (free) | 5000 palabras/día gratis, sin registro | Gratis |
| **Código fuente** | GitHub | Repositorio + CI/CD con Railway | Gratis |
| **Dominio** | Subdominio .up.railway.app | Incluido en Railway | Gratis |

> *Railway da $5 USD en créditos gratuitos por mes. Un proyecto Laravel pequeño consume aproximadamente $1-2/mes, por lo que es efectivamente gratis.

### ¿Por qué Railway y no Heroku/Render?
- Railway detecta automáticamente proyectos Laravel y los configura
- Incluye base de datos MySQL en el mismo dashboard
- CI/CD automático desde GitHub sin configuración extra
- El subdominio `.up.railway.app` se asigna automáticamente (sin pagar dominio)

---

## 4. 🏗️ Arquitectura General {#arquitectura}

```
┌─────────────────────────────────────────────────────────────┐
│                  FRONTEND (Blade + Livewire)                 │
│  - Página principal: buscador + tarjetas de certs           │
│  - Componente Livewire: formulario de datos del candidato   │
│  - Componente Livewire: cuestionario interactivo (30 preg.) │
│  - Blade: pantalla de resultado + LinkedIn + descarga PDF   │
│  - Blade: vista pública del certificado por serial          │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTP / Livewire wire:
┌──────────────────────────▼──────────────────────────────────┐
│                   BACKEND (Laravel 11)                       │
│  - Middleware: detección de idioma del navegador            │
│  - Controllers: lógica de certificados, búsqueda, PDF       │
│  - Livewire Components: quiz engine (servidor)              │
│  - Services: QuizService, CertificateService, PDFService    │
│  - Services: TranslationService (MyMemory API + caché DB)   │
│  - Middleware: RateLimitMiddleware (1/día por documento)     │
│  - Scheduler: limpieza de certificados expirados (diario)   │
│  - Jobs: CleanExpiredCertificates (queue)                   │
└──────────────────────────┬──────────────────────────────────┘
                           │ Eloquent ORM
┌──────────────────────────▼──────────────────────────────────┐
│                   BASE DE DATOS (MySQL en Railway)           │
│  - Tabla: certificates                                      │
│  - Tabla: questions                                         │
│  - Tabla: question_translations                             │
│  - Tabla: rate_limits                                       │
│  - Tabla: cache (driver de caché de Laravel)                │
└─────────────────────────────────────────────────────────────┘
```

### Ventaja de Livewire sobre JavaScript puro

Livewire permite construir el quiz de 30 preguntas como un componente PHP interactivo donde toda la lógica (preguntas seleccionadas, respuestas correctas, validación) vive **únicamente en el servidor**. El cliente nunca ve la respuesta correcta. Es la forma más segura y simple de implementarlo en Laravel.

---

## 5. 🗄️ Estructura de la Base de Datos {#base-de-datos}

### Tabla: `certificates`
```sql
CREATE TABLE certificates (
  id              CHAR(36) PRIMARY KEY,              -- UUID
  serial          VARCHAR(25) UNIQUE NOT NULL,
  -- Datos del candidato
  first_name      VARCHAR(100) NOT NULL,
  last_name       VARCHAR(100) NOT NULL,
  doc_hash        VARCHAR(255) NOT NULL,             -- Hash bcrypt del documento
  doc_partial     VARCHAR(6) NOT NULL,               -- Últimos 4 chars (para búsqueda)
  country         VARCHAR(100) NOT NULL,
  -- Resultado
  cert_type       ENUM('sexuality','conduct') NOT NULL,
  result_key      ENUM('heterosexual','homosexual','buena','zorra') NOT NULL,
  score           TINYINT UNSIGNED NOT NULL,         -- Respuestas correctas (0-30)
  total_questions TINYINT UNSIGNED NOT NULL DEFAULT 30,
  -- Control
  language        VARCHAR(10) NOT NULL DEFAULT 'en',
  issued_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at      TIMESTAMP NOT NULL,                -- issued_at + 1 año
  last_renewed    TIMESTAMP NULL,
  ip_hash         VARCHAR(255) NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL
);

CREATE INDEX idx_serial      ON certificates(serial);
CREATE INDEX idx_doc_partial ON certificates(doc_partial);
CREATE INDEX idx_expires     ON certificates(expires_at);
```

### Tabla: `questions`
```sql
CREATE TABLE questions (
  id              CHAR(36) PRIMARY KEY,              -- UUID
  cert_type       ENUM('sexuality','conduct') NOT NULL,
  question_text   TEXT NOT NULL,                     -- Pregunta en inglés (idioma base)
  option_1        TEXT NOT NULL,
  option_2        TEXT NOT NULL,
  option_3        TEXT NOT NULL,
  option_4        TEXT NOT NULL,
  correct_option  TINYINT UNSIGNED NOT NULL,         -- 1, 2, 3 o 4
  active          BOOLEAN NOT NULL DEFAULT TRUE,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL
);

CREATE INDEX idx_questions_type ON questions(cert_type, active);
```

### Tabla: `question_translations`
```sql
CREATE TABLE question_translations (
  id              CHAR(36) PRIMARY KEY,
  question_id     CHAR(36) NOT NULL,
  language        VARCHAR(10) NOT NULL,              -- 'es', 'pt', 'zh', 'hi', 'ar', 'fr'
  question_text   TEXT NOT NULL,
  option_1        TEXT NOT NULL,
  option_2        TEXT NOT NULL,
  option_3        TEXT NOT NULL,
  option_4        TEXT NOT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  UNIQUE KEY uq_translation (question_id, language),
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);
```

### Tabla: `rate_limits`
```sql
CREATE TABLE rate_limits (
  id              CHAR(36) PRIMARY KEY,
  identifier_hash VARCHAR(255) NOT NULL,             -- Hash del documento
  cert_type       ENUM('sexuality','conduct') NOT NULL,
  attempted_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL
);

CREATE INDEX idx_rate_id ON rate_limits(identifier_hash, attempted_at);
```

### Tabla: `cache` *(generada automáticamente por Laravel)*
```sql
-- Creada con: php artisan cache:table
-- Usada para caché de traducciones, sesiones de quiz, etc.
CREATE TABLE cache (
  `key`       VARCHAR(255) NOT NULL PRIMARY KEY,
  value       MEDIUMTEXT NOT NULL,
  expiration  INT NOT NULL
);

CREATE TABLE cache_locks (
  `key`       VARCHAR(255) NOT NULL PRIMARY KEY,
  owner       VARCHAR(255) NOT NULL,
  expiration  INT NOT NULL
);
```

---

## 6. ❓ Sistema de Preguntas y Traducción {#sistema-de-preguntas}

### Estrategia: Traducciones cacheadas en DB + MyMemory API

Las preguntas se guardan en inglés en la tabla `questions`. La primera vez que una pregunta es solicitada en un idioma distinto, se traduce automáticamente via MyMemory API (gratis, 5000 palabras/día, sin registro) y se cachea en `question_translations`. Todas las siguientes peticiones sirven desde la caché sin consumir la API.

```
1. Usuario inicia quiz en idioma "es"
2. Sistema selecciona 30 question_ids aleatorios (Fisher-Yates)
3. Para cada question_id, busca en question_translations donde language='es'
   → Si existe → usar la traducción cacheada ✅ (0 llamadas API)
   → Si no existe → llamar MyMemory API → guardar en question_translations → usar
4. El correct_option (número 1-4) no cambia entre idiomas
5. Las opciones se mezclan aleatoriamente en el componente Livewire
6. El mapa de mezcla queda en la propiedad protegida del componente (solo servidor)
```

### Endpoint de MyMemory API (sin registro)

```
GET https://api.mymemory.translated.net/get?q={texto}&langpair=en|{idioma_destino}
```

Ejemplo:
```
https://api.mymemory.translated.net/get?q=What+is+your+favorite+drink?&langpair=en|es
```

### Idiomas soportados

| Idioma | Código | Dirección |
|--------|--------|-----------|
| Inglés (base) | `en` | LTR |
| Español | `es` | LTR |
| Portugués | `pt` | LTR |
| Chino Simplificado | `zh` | LTR |
| Hindi | `hi` | LTR |
| Árabe | `ar` | RTL |
| Francés | `fr` | LTR |

### Tamaño mínimo del banco de preguntas

- **Mínimo:** 60 preguntas activas por certificado
- **Recomendado:** 100+ preguntas por certificado para maximizar variedad

### Selección aleatoria (Fisher-Yates en PHP)

```php
// En QuizService.php
$questionIds = Question::where('cert_type', $certType)
    ->where('active', true)
    ->pluck('id')
    ->toArray();

shuffle($questionIds);                    // Fisher-Yates nativo de PHP
$selectedIds = array_slice($questionIds, 0, 30);
```

---

## 7. 📋 Formulario de Registro del Candidato {#formulario}

### Campos requeridos

| Campo | Tipo | Validación | Guardado en DB |
|-------|------|-----------|----------------|
| **Nombre** | Texto | required, min:2, max:50, regex letras | Texto plano |
| **Apellido** | Texto | required, min:2, max:50, regex letras | Texto plano |
| **Documento** | Texto | required, min:5, max:20, alfanumérico | Solo hash bcrypt + últimos 4 chars |
| **País** | Select | required, lista de 195 países | Nombre del país |
| **Tipo de certificado** | Fijo | Ya seleccionado, no editable | Sí |

### Regla de unicidad

```
Una persona (identificada por su documento hasheado) puede tener:
  - 1 certificado de orientación sexual vigente
  - 1 certificado de conducta personal vigente

Si ya tiene un certificado vigente del mismo tipo:
  → Si han pasado menos de 30 días desde el último intento: BLOQUEADO
  → Si han pasado 30+ días: se le ofrece renovar (sobreescribe el anterior)

Ambos certificados son independientes entre sí.
```

---

## 8. 📊 Lógica de Calificación {#calificacion}

### Umbral

```
Total preguntas mostradas: 30
Umbral de fallo: ROUND(30 / 3) = 10 incorrectas

Resultado POSITIVO: 0 a 9 incorrectas  (21-30 correctas)
Resultado NEGATIVO: 10 o más incorrectas (0-20 correctas)
```

### Mapeo de resultados

| cert_type | Positivo (< 10 incorrectas) | Negativo (≥ 10 incorrectas) |
|-----------|-----------------------------|-----------------------------|
| `sexuality` | `heterosexual` | `homosexual` |
| `conduct` | `buena` | `zorra` |

### Cómo funciona la calificación en Livewire

```
1. El componente Livewire tiene propiedades protegidas (solo servidor):
   - $selectedQuestions: array de 30 Question objects con correct_option
   - $shuffleMap: array de {pregunta_id => [posición_nueva => posición_original]}

2. Cuando el usuario responde una pregunta, Livewire hace wire:click
   El método PHP del servidor recibe el número de opción elegido
   Lo compara con el correct_option real usando el shuffleMap
   Incrementa $correctCount o $incorrectCount

3. Al terminar pregunta 30:
   - Calcula resultado según umbral
   - Genera serial único
   - Guarda en DB (hash de documento, resultado, serial)
   - Redirige a pantalla de resultado
```

> La respuesta correcta **nunca sale del servidor**. El cliente solo ve texto de opciones mezclado.

---

## 9. 📄 Generación de Certificados PDF {#pdf}

### Tecnología: `barryvdh/laravel-dompdf`

DomPDF convierte HTML + CSS a PDF directamente en el servidor. El template del certificado es una vista Blade que se renderiza a buffer y se envía como respuesta HTTP.

### Flujo de generación

```
GET /cert/{serial}/pdf
→ Validar que el serial existe y no ha expirado
→ Obtener datos del certificado desde la DB
→ Cargar vista Blade: resources/views/pdf/certificate.blade.php
→ Pasar datos: nombre, resultado, serial, fecha, país, puntuación
→ DomPDF renderiza la vista a buffer PDF
→ Response::make($pdf, 200, [
     'Content-Type' => 'application/pdf',
     'Content-Disposition' => 'attachment; filename="cert-{serial}.pdf"'
  ])
```

### Diseño del certificado PDF

```
┌────────────────────────────────────────────────────────┐
│  [Escudo/Logo oficial de broma - estilo gubernamental] │
│                                                        │
│       CERTIFICADO OFICIAL DE [TIPO EN MAYÚSCULAS]      │
│       ══════════════════════════════════               │
│                                                        │
│  Por medio del presente se certifica que:              │
│                                                        │
│          [NOMBRE COMPLETO DEL CANDIDATO]               │
│          [País]                                        │
│                                                        │
│  Habiendo superado el proceso de evaluación, se le     │
│  otorga el título de:                                  │
│                                                        │
│  ┌────────────────────────────────────────────────┐    │
│  │    [RESULTADO EN GRANDE - estilo diploma]      │    │
│  └────────────────────────────────────────────────┘    │
│                                                        │
│  Puntuación obtenida: [XX] / 30                       │
│                                                        │
│  Serial:        CERT-[AÑO]-[TIPO]-[6CHARS]            │
│  Emitido el:    DD/MM/YYYY                             │
│  Válido hasta:  DD/MM/YYYY                             │
│                                                        │
│  [QR Code → /cert/{serial}]                           │
│                                                        │
│  ___________________    [Sello oficial de broma]      │
│  Firma del Director                                    │
│  Instituto de Certificaciones Dudosas™                │
└────────────────────────────────────────────────────────┘
```

### Formato del serial

```
CERT-[AÑO]-[TIPO_2CHARS]-[6CHARS_ALEATORIOS_MAYÚSCULAS]

Ejemplos:
  CERT-2025-SX-7K2M9P   (SX = Sexuality)
  CERT-2025-CD-9P1QRZ   (CD = Conduct)
```

Generación en PHP:
```php
$serial = 'CERT-' . date('Y') . '-' 
        . strtoupper(substr($certType, 0, 2)) . '-' 
        . strtoupper(Str::random(6));
```

---

## 10. 🌐 Vista Pública del Certificado por Serial {#vista-publica}

### URL pública

```
https://[tu-app].up.railway.app/cert/CERT-2025-SX-7K2M9P
```

### Comportamiento

```
1. Usuario visita /cert/{serial}
2. CertificateController busca el serial en la DB
   → No existe: vista de error "Certificado no encontrado"
   → Expirado: vista de error "Este certificado ha expirado"
   → Válido:
     a. Renderizar vista Blade con datos del certificado
     b. Botón "Descargar PDF" → /cert/{serial}/pdf
     c. Botón "Agregar a LinkedIn"
     d. Información: nombre, tipo, resultado, fecha, validez
```

### Metadatos OpenGraph (para compartir en redes)

```blade
{{-- En el layout de la vista pública --}}
<meta property="og:title" content="Certificado de {{ $cert->first_name }} - {{ $cert->result_key }}" />
<meta property="og:description" content="Verifica el certificado oficial de {{ $cert->first_name }} {{ $cert->last_name }}" />
<meta property="og:url" content="{{ url('/cert/' . $cert->serial) }}" />
```

---

## 11. 💼 Botón "Agregar a LinkedIn" {#linkedin}

### Construcción de la URL de LinkedIn

```php
// En CertificateController o helper
$linkedinUrl = 'https://www.linkedin.com/profile/add?' . http_build_query([
    'startTask'       => 'CERTIFICATION_NAME',
    'name'            => $certName,          // "Certificado de Heterosexualidad"
    'organizationId'  => env('LINKEDIN_ORG_ID'),
    'issueYear'       => $cert->issued_at->year,
    'issueMonth'      => $cert->issued_at->month,
    'expirationYear'  => $cert->expires_at->year,
    'expirationMonth' => $cert->expires_at->month,
    'certUrl'         => url('/cert/' . $cert->serial),
    'certId'          => $cert->serial,
]);
```

### Dónde aparece el botón

1. **Pantalla de resultado** — justo después de completar el quiz
2. **Vista pública del certificado** (`/cert/{serial}`)

> Para el `organizationId` debes crear una página de empresa en LinkedIn (gratis). Esto hace que el certificado aparezca asociado a tu organización en el perfil del usuario.

---

## 12. 🔍 Sistema de Búsqueda y Descarga {#busqueda}

### Layout de la página principal

```
┌─────────────────────────────────────────────────────────┐
│                    HEADER / LOGO                        │
│              [Selector de idioma manual]                │
├─────────────────────────────────────────────────────────┤
│                                                         │
│       🏛️ Instituto de Certificaciones Dudosas™          │
│       "Certificamos lo que nadie más se atreve"         │
│                                                         │
│  ┌──────────────────────────────────────────────────┐   │
│  │ 🔍  Ingresa tu serial o documento...   [Buscar] │   │
│  └──────────────────────────────────────────────────┘   │
│                                                         │
├─────────────────────────────────────────────────────────┤
│  ╔═══════════════════╗     ╔═══════════════════╗        │
│  ║ 🏅 Certificado   ║     ║ 🏅 Certificado   ║        │
│  ║ de Orientación   ║     ║ de Conducta      ║        │
│  ║ Sexual           ║     ║ Personal         ║        │
│  ║                  ║     ║                  ║        │
│  ║ [Obtener cert.]  ║     ║ [Obtener cert.]  ║        │
│  ╚═══════════════════╝     ╚═══════════════════╝        │
├─────────────────────────────────────────────────────────┤
│  ⚠️ Disclaimer: Esta página es puramente humorística…  │
└─────────────────────────────────────────────────────────┘
```

### Lógica de búsqueda

```
El usuario puede ingresar:
  - Serial completo: CERT-2025-SX-7K2M9P → búsqueda exacta por campo serial
  - Número de documento → se busca por doc_partial (últimos 4 chars) como primer filtro,
    luego se verifica el hash bcrypt completo para confirmación

Resultado si encuentra:
  → Muestra preview del certificado con botones: [Ver completo] [Descargar PDF]

Resultado si no encuentra:
  → Mensaje: "No se encontró ningún certificado con ese dato"
```

---

## 13. ⏰ Sistema de Expiración y Renovación {#expiracion}

### Reglas de vigencia

```
- Los certificados duran exactamente 1 año desde issued_at
- expires_at = issued_at + 1 year (calculado en PHP con Carbon)
- Una persona puede renovar (repetir el quiz) si han pasado 30+ días
  desde su último intento para ese tipo de certificado
- La renovación sobreescribe el certificado anterior (mismo registro, updated)
- Los certificados expirados se eliminan de la DB con un Scheduled Command
```

### Scheduled Command de limpieza

```php
// En app/Console/Commands/CleanExpiredCertificates.php
Certificate::where('expires_at', '<', now())->delete();

// Programado en routes/console.php (Laravel 11):
Schedule::command('certificates:clean')->daily();
```

---

## 14. 🔒 Seguridad y Cifrado {#seguridad}

### Datos sensibles

| Dato | Tratamiento |
|------|-------------|
| Número de documento | Hash bcrypt (12 rondas). Nunca se guarda en texto plano |
| Últimos 4 chars del documento | Guardados en `doc_partial` (solo para búsqueda inicial) |
| IP del usuario | Hash SHA-256 + salt antes de guardar |
| Respuesta correcta de cada pregunta | Solo vive en propiedades protegidas del componente Livewire (servidor) |

### Validación de inputs

Toda entrada del usuario pasa por Form Requests de Laravel:
```php
// app/Http/Requests/StartQuizRequest.php
'first_name' => 'required|string|min:2|max:50|regex:/^[\pL\s]+$/u',
'last_name'  => 'required|string|min:2|max:50|regex:/^[\pL\s]+$/u',
'document'   => 'required|string|min:5|max:20|alpha_num',
'country'    => 'required|string|in:' . implode(',', CountryList::all()),
'cert_type'  => 'required|in:sexuality,conduct',
```

### Headers HTTP de seguridad

Configurados en `app/Http/Middleware/SecurityHeaders.php`:
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: (configurado según la app)
```

### Variables de entorno

Todas las claves y configuraciones sensibles van en `.env` (nunca en el código). En Railway se configuran en el dashboard de la app.

---

## 15. ⏱️ Rate Limiting {#rate-limit}

### Regla de negocio

```
Una persona NO puede iniciar un quiz nuevo si:
  - Ya tiene un certificado vigente del mismo tipo Y
  - Han pasado menos de 30 días desde su último intento

Una persona NO puede hacer múltiples intentos el mismo día:
  - Se bloquea si la IP ya hizo un intento en las últimas 24h (protección anti-spam)
```

### Implementación en Laravel

```php
// Middleware: app/Http/Middleware/QuizRateLimit.php
// Verifica tabla rate_limits usando el hash del documento
// Verifica throttle de IP usando el caché de Laravel

// En routes/web.php:
Route::post('/quiz/start', [QuizController::class, 'start'])
    ->middleware(['quiz.ratelimit']);

// Throttle global de Laravel para protección de endpoints sensibles:
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/search', [SearchController::class, 'index']);
    Route::post('/quiz/start', [QuizController::class, 'start']);
});
```

---

## 16. 🌍 Multilenguaje e i18n {#i18n}

### Estrategia

| Capa | Solución |
|------|---------|
| Interfaz (botones, títulos, mensajes) | Archivos de traducción de Laravel (`lang/`) |
| Preguntas del quiz | Tabla `question_translations` en DB (cacheadas) |
| Detección de idioma | Middleware lee `Accept-Language` del navegador |
| Fallback | Inglés (`en`) si el idioma del navegador no está soportado |
| Selector manual | Dropdown en el header que cambia la sesión `locale` |

### Estructura de archivos de traducción

```
lang/
  en/
    app.php       ← títulos, botones, mensajes generales
    quiz.php      ← textos del quiz
    cert.php      ← textos del certificado
    results.php   ← textos de resultados
  es/
    app.php
    quiz.php
    cert.php
    results.php
  pt/  zh/  hi/  ar/  fr/
    (mismos archivos)
```

### Middleware de detección de idioma

```php
// app/Http/Middleware/SetLocale.php
$browserLang = substr($request->header('Accept-Language'), 0, 2);
$supported   = ['en', 'es', 'pt', 'zh', 'hi', 'ar', 'fr'];
$locale      = in_array($browserLang, $supported) ? $browserLang : 'en';

app()->setLocale($locale);
session(['locale' => $locale]);
```

### Soporte RTL para árabe

```html
{{-- En el layout principal --}}
<html lang="{{ app()->getLocale() }}" 
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
```

---

## 17. 🎨 Diseño y UI/UX {#diseno}

### Estilo visual

El diseño tiene apariencia "institucional oficial de broma": estilo de entidad gubernamental seria pero con contenido absurdo. Colores oscuros y dorados, tipografía serif en títulos, diseño de diploma/certificado clásico.

### Componentes principales a crear

- `resources/views/layouts/app.blade.php` — layout principal con header, footer, selector de idioma
- `resources/views/home.blade.php` — página principal con buscador y tarjetas
- `resources/views/livewire/registration-form.blade.php` — formulario de datos
- `resources/views/livewire/quiz.blade.php` — cuestionario interactivo
- `resources/views/results.blade.php` — pantalla de resultado con botones
- `resources/views/cert/show.blade.php` — vista pública del certificado
- `resources/views/pdf/certificate.blade.php` — template del PDF

### Requisitos de diseño responsive

- Mobile-first con Tailwind CSS
- Quiz usable en pantallas de 320px en adelante
- Botones y targets táctiles mínimo 44px de altura
- PDF generado en tamaño A4 landscape para mejor presentación

---

## 18. 📁 Estructura de Carpetas del Proyecto {#estructura}

```
certificados-app/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── CleanExpiredCertificates.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── HomeController.php
│   │   │   ├── CertificateController.php  ← show, pdf, search
│   │   │   └── LocaleController.php       ← cambio de idioma
│   │   ├── Middleware/
│   │   │   ├── SetLocale.php
│   │   │   ├── QuizRateLimit.php
│   │   │   └── SecurityHeaders.php
│   │   └── Requests/
│   │       └── StartQuizRequest.php
│   ├── Livewire/
│   │   ├── RegistrationForm.php           ← formulario de datos del candidato
│   │   ├── Quiz.php                       ← motor del quiz (lógica en servidor)
│   │   └── SearchBar.php                  ← buscador de la página principal
│   ├── Models/
│   │   ├── Certificate.php
│   │   ├── Question.php
│   │   ├── QuestionTranslation.php
│   │   └── RateLimit.php
│   └── Services/
│       ├── QuizService.php                ← selección aleatoria, shuffle
│       ├── CertificateService.php         ← generación serial, guardado
│       ├── TranslationService.php         ← MyMemory API + caché
│       └── PDFService.php                 ← DomPDF wrapper
├── database/
│   ├── migrations/
│   │   ├── xxxx_create_certificates_table.php
│   │   ├── xxxx_create_questions_table.php
│   │   ├── xxxx_create_question_translations_table.php
│   │   └── xxxx_create_rate_limits_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── SexualityQuestionsSeeder.php   ← 60+ preguntas en inglés
│       └── ConductQuestionsSeeder.php     ← 60+ preguntas en inglés
├── lang/
│   ├── en/ es/ pt/ zh/ hi/ ar/ fr/
│   │   ├── app.php
│   │   ├── quiz.php
│   │   ├── cert.php
│   │   └── results.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       ├── home.blade.php
│       ├── results.blade.php
│       ├── cert/
│       │   └── show.blade.php
│       ├── livewire/
│       │   ├── registration-form.blade.php
│       │   ├── quiz.blade.php
│       │   └── search-bar.blade.php
│       └── pdf/
│           └── certificate.blade.php
├── routes/
│   ├── web.php
│   └── console.php                        ← scheduler
├── .env.example
├── .gitignore
├── nixpacks.toml                          ← configuración para Railway
└── composer.json
```

---

## 19. 📦 Dependencias del Proyecto {#dependencias}

### Composer (PHP)

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "livewire/livewire": "^3.0",
    "barryvdh/laravel-dompdf": "^2.2",
    "simplesoftwareio/simple-qrcode": "^4.2",
    "league/iso3166": "^4.3",
    "ext-bcrypt": "*"
  },
  "require-dev": {
    "laravel/pint": "^1.0",
    "fakerphp/faker": "^1.23",
    "laravel/sail": "^1.29"
  }
}
```

| Paquete | Para qué |
|---------|---------|
| `livewire/livewire` | Componentes interactivos (quiz, formulario, buscador) |
| `barryvdh/laravel-dompdf` | Generar certificados en PDF desde HTML/CSS |
| `simplesoftwareio/simple-qrcode` | QR code en el certificado que apunta a la URL pública |
| `league/iso3166` | Lista de países del mundo con códigos estándar |

### NPM (JavaScript/CSS)

```json
{
  "devDependencies": {
    "tailwindcss": "^3.4",
    "autoprefixer": "^10.4",
    "postcss": "^8.4",
    "@alpinejs/intersect": "^3.13"
  },
  "dependencies": {
    "alpinejs": "^3.13"
  }
}
```

---

## 20. 🗺️ Fases de Desarrollo con Pasos Detallados {#fases}

---

### ✅ Fase 0 — Configuración del Entorno (Día 1-2)

**Objetivo:** Tener el proyecto corriendo localmente y en Railway.

1. Instalar PHP 8.2+, Composer, Node.js 20+, Git en tu máquina local
2. Instalar Laravel Installer globalmente: `composer global require laravel/installer`
3. Crear el proyecto: `laravel new certificados-app`
4. Seleccionar: No starter kit, SQLite para desarrollo local
5. Entrar al proyecto: `cd certificados-app`
6. Instalar Livewire: `composer require livewire/livewire`
7. Instalar DomPDF: `composer require barryvdh/laravel-dompdf`
8. Instalar QR Code: `composer require simplesoftwareio/simple-qrcode`
9. Instalar lista de países: `composer require league/iso3166`
10. Instalar dependencias NPM: `npm install`
11. Instalar Tailwind CSS: `npm install -D tailwindcss postcss autoprefixer`
12. Configurar Tailwind: `npx tailwindcss init -p`
13. Configurar `tailwind.config.js` para escanear vistas Blade y Livewire
14. Crear repositorio en GitHub (desde github.com, sin usar CLI)
15. Conectar el proyecto local con GitHub:
    ```bash
    git init
    git remote add origin https://github.com/tu-usuario/certificados-app.git
    git add .
    git commit -m "Initial Laravel setup"
    git push -u origin main
    ```
16. Crear cuenta en [Railway](https://railway.app) con tu cuenta de GitHub
17. En Railway: New Project → Deploy from GitHub repo → seleccionar el repositorio
18. Railway detecta automáticamente que es PHP/Laravel
19. En Railway: Add Plugin → MySQL (crea una base de datos MySQL gratuita)
20. Copiar las variables de conexión MySQL que Railway genera y agregarlas a las variables de entorno de Railway
21. En Railway: configurar las variables de entorno (APP_KEY, APP_ENV=production, APP_URL, DB_*)
22. Generar APP_KEY localmente con `php artisan key:generate --show` y agregarla a Railway
23. Verificar que el deploy automático funciona visitando la URL `.up.railway.app`

---

### ✅ Fase 1 — Base de Datos y Modelos (Día 3-5)

**Objetivo:** Crear todas las tablas y modelos Eloquent.

1. Crear migración de certificates:
   `php artisan make:migration create_certificates_table`
2. Crear migración de questions:
   `php artisan make:migration create_questions_table`
3. Crear migración de question_translations:
   `php artisan make:migration create_question_translations_table`
4. Crear migración de rate_limits:
   `php artisan make:migration create_rate_limits_table`
5. Crear tabla de caché de Laravel:
   `php artisan cache:table`
6. Abrir cada archivo de migración en `database/migrations/` y escribir el schema exacto de la sección 5 de este plan
7. Ejecutar todas las migraciones: `php artisan migrate`
8. Crear modelo Certificate: `php artisan make:model Certificate`
9. Crear modelo Question: `php artisan make:model Question`
10. Crear modelo QuestionTranslation: `php artisan make:model QuestionTranslation`
11. Crear modelo RateLimit: `php artisan make:model RateLimit`
12. En cada modelo, definir:
    - `$fillable` con los campos permitidos para asignación masiva
    - `$casts` para tipos (ej: `'issued_at' => 'datetime'`)
    - Relaciones Eloquent (`hasMany`, `belongsTo`)
    - En `Certificate`: método `isExpired()` que compara `expires_at` con `now()`
    - En `Certificate`: método `canRenew()` que verifica si han pasado 30+ días
13. Crear Seeder de preguntas de sexualidad:
    `php artisan make:seeder SexualityQuestionsSeeder`
14. Crear Seeder de preguntas de conducta:
    `php artisan make:seeder ConductQuestionsSeeder`
15. Escribir mínimo 60 preguntas en inglés dentro de cada Seeder (en el array de `Question::insert([...])`)
16. Registrar ambos seeders en `DatabaseSeeder.php`
17. Ejecutar los seeders: `php artisan db:seed`
18. Verificar en SQLite local (o usar Tinker: `php artisan tinker`) que las preguntas se crearon

---

### ✅ Fase 2 — Servicios Core (Día 6-8)

**Objetivo:** Crear la lógica de negocio en clases Service.

1. Crear carpeta `app/Services/` manualmente
2. Crear `app/Services/QuizService.php` con los métodos:
   - `selectQuestions(string $certType, int $count = 30): array` — Fisher-Yates
   - `shuffleOptions(Question $question): array` — mezcla opciones y retorna mapa
   - `validateAnswer(int $selectedOption, array $shuffleMap, int $correctOption): bool`
3. Crear `app/Services/TranslationService.php` con los métodos:
   - `translateQuestion(Question $question, string $language): array`
     - Verifica si existe en `question_translations`
     - Si no existe: llama MyMemory API via `Http::get()`
     - Guarda resultado en `question_translations`
     - Retorna texto traducido
   - `translateText(string $text, string $targetLang): string` — helper para llamar MyMemory
4. Crear `app/Services/CertificateService.php` con los métodos:
   - `generateSerial(string $certType): string` — genera CERT-AÑO-TIPO-6CHARS
   - `createCertificate(array $data): Certificate` — guarda en DB con hash del documento
   - `findBySerial(string $serial): ?Certificate`
   - `findByDocument(string $docPartial, string $docFull): ?Certificate` — busca por partial y confirma con bcrypt
5. Crear `app/Services/PDFService.php` con el método:
   - `generate(Certificate $cert): \Barryvdh\DomPDF\PDF` — carga vista Blade y genera PDF

---

### ✅ Fase 3 — Middleware y Seguridad (Día 9-10)

**Objetivo:** Crear todos los middleware necesarios.

1. Crear middleware de locale: `php artisan make:middleware SetLocale`
2. Escribir la lógica en `SetLocale.php`:
   - Leer `Accept-Language` del request
   - Si existe sesión `locale`, usarla (el usuario lo cambió manualmente)
   - Si no, detectar del header y validar contra la lista soportada
   - Fallback a `en`
   - Llamar `app()->setLocale($locale)` y guardar en sesión
3. Crear middleware de rate limit: `php artisan make:middleware QuizRateLimit`
4. Escribir la lógica en `QuizRateLimit.php`:
   - Si el usuario está enviando el formulario inicial, verificar:
     - Que la IP no haya hecho un intento en las últimas 24h (usando `Cache::has()`)
     - Que el documento no tenga un certificado del mismo tipo con < 30 días
   - Si se bloquea: retornar error con mensaje traducido
5. Crear middleware de headers de seguridad: `php artisan make:middleware SecurityHeaders`
6. Agregar los headers HTTP de seguridad de la sección 14 de este plan
7. Registrar los tres middleware en `bootstrap/app.php` (Laravel 11 usa este archivo en lugar de `Kernel.php`)
8. Crear `app/Http/Requests/StartQuizRequest.php`:
   `php artisan make:request StartQuizRequest`
9. Escribir las reglas de validación del formulario de la sección 7

---

### ✅ Fase 4 — Rutas y Controladores (Día 11-12)

**Objetivo:** Crear todas las rutas y controladores.

1. Crear HomeController: `php artisan make:controller HomeController`
2. Crear CertificateController: `php artisan make:controller CertificateController`
3. Crear LocaleController: `php artisan make:controller LocaleController`
4. Escribir las rutas en `routes/web.php`:
   ```php
   // Página principal
   Route::get('/', [HomeController::class, 'index'])->name('home');

   // Cambio de idioma
   Route::post('/locale/{lang}', [LocaleController::class, 'set'])->name('locale.set');

   // Búsqueda de certificados
   Route::post('/search', [CertificateController::class, 'search'])->name('cert.search');

   // Vista pública del certificado
   Route::get('/cert/{serial}', [CertificateController::class, 'show'])->name('cert.show');

   // Descarga PDF del certificado
   Route::get('/cert/{serial}/pdf', [CertificateController::class, 'downloadPDF'])->name('cert.pdf');

   // Inicio del quiz (formulario de datos) — Livewire se encarga desde aquí
   Route::get('/quiz/{certType}', [HomeController::class, 'startQuiz'])
       ->where('certType', 'sexuality|conduct')
       ->name('quiz.start')
       ->middleware('quiz.ratelimit');

   // Pantalla de resultado
   Route::get('/result/{serial}', [CertificateController::class, 'result'])->name('cert.result');
   ```
5. Implementar `HomeController::index()` — retorna vista home con las dos tarjetas
6. Implementar `HomeController::startQuiz()` — retorna vista con componente Livewire del formulario
7. Implementar `CertificateController::show()` — busca por serial y retorna vista pública
8. Implementar `CertificateController::downloadPDF()` — usa PDFService y retorna response de descarga
9. Implementar `CertificateController::search()` — usa CertificateService para buscar
10. Implementar `CertificateController::result()` — muestra pantalla de resultado post-quiz
11. Implementar `LocaleController::set()` — guarda el idioma elegido en sesión y redirige

---

### ✅ Fase 5 — Componentes Livewire (Día 13-17)

**Objetivo:** Crear los componentes interactivos del quiz y formulario.

1. Crear componente del formulario de registro:
   `php artisan make:livewire RegistrationForm`
2. En `app/Livewire/RegistrationForm.php` definir:
   - Propiedades públicas: `$firstName`, `$lastName`, `$document`, `$country`, `$certType`
   - Método `mount(string $certType)` para recibir el tipo del quiz
   - Método `submit()` con validación via StartQuizRequest o reglas inline
   - Al validar correctamente: guardar datos en sesión y despachar evento a componente Quiz
3. Crear componente del quiz:
   `php artisan make:livewire Quiz`
4. En `app/Livewire/Quiz.php` definir:
   - Propiedades **protegidas** (no públicas): `$selectedQuestions`, `$shuffleMap`, `$answers`
   - Propiedades públicas: `$currentIndex`, `$totalQuestions`, `$displayQuestion`
   - Método `mount()` que llama a `QuizService::selectQuestions()` y `TranslationService`
   - Método `answer(int $option)` que registra la respuesta y avanza a la siguiente pregunta
   - Método `finish()` que calcula el resultado, guarda en DB via `CertificateService` y redirige a `/result/{serial}`
5. Crear componente del buscador:
   `php artisan make:livewire SearchBar`
6. En `app/Livewire/SearchBar.php` definir:
   - Propiedad pública: `$query`
   - Método `search()` que llama a `CertificateService::findByDocument()` o `findBySerial()`
   - Emite resultado al template para mostrar card de preview o error
7. Escribir las vistas Blade de cada componente en `resources/views/livewire/`:
   - `registration-form.blade.php`: formulario con campos de la sección 7
   - `quiz.blade.php`: pregunta actual, opciones, barra de progreso
   - `search-bar.blade.php`: input de búsqueda y resultado/error

---

### ✅ Fase 6 — Vistas Blade y Layout (Día 18-20)

**Objetivo:** Crear todas las vistas Blade y el layout principal.

1. Crear `resources/views/layouts/app.blade.php`:
   - Header con logo, nombre "Instituto de Certificaciones Dudosas™"
   - Dropdown de selector de idioma (7 opciones)
   - Slot para el contenido principal
   - Footer con disclaimer de broma y política de privacidad
   - Import de Livewire scripts y Tailwind CSS
   - Atributo `dir` dinámico para RTL en árabe
2. Crear `resources/views/home.blade.php`:
   - `@extends('layouts.app')`
   - Componente Livewire del buscador
   - Dos tarjetas de certificados con botones "Obtener certificado"
3. Crear `resources/views/cert/show.blade.php`:
   - Vista pública del certificado con todos los datos
   - Botón "Descargar PDF"
   - Botón "Agregar a LinkedIn" con URL construida según sección 11
   - Metadatos OpenGraph en el `<head>`
4. Crear `resources/views/results.blade.php`:
   - Resultado en grande (Heterosexual / Homosexual / Niña Buena / Zorra)
   - Puntuación obtenida (XX/30)
   - Botón "Descargar PDF"
   - Botón "Agregar a LinkedIn"
   - Botón "Hacer otro certificado"
   - Botón "Compartir enlace"
5. Crear `resources/views/errors/404.blade.php` — "Certificado no encontrado"
6. Crear `resources/views/errors/410.blade.php` — "Certificado expirado"
7. Compilar assets: `npm run build`

---

### ✅ Fase 7 — Template PDF (Día 21-22)

**Objetivo:** Crear el template del certificado PDF.

1. Crear `resources/views/pdf/certificate.blade.php`
2. Diseñar el HTML con estilos inline (DomPDF no soporta Tailwind ni CSS externo):
   - Fondo color marfil o dorado suave
   - Borde doble estilo diploma
   - Logo/escudo en la parte superior (puede ser SVG inline)
   - Tipografía serif para el nombre y resultado
   - Cuadro destacado con el resultado (heterosexual / homosexual / buena / zorra)
   - Serial, fecha de emisión, fecha de expiración
   - Código QR generado con `simplesoftwareio/simple-qrcode` que apunta a `/cert/{serial}`
   - Línea de firma y sello oficial de broma
3. Probar la generación localmente:
   - Crear una ruta temporal: `Route::get('/test-pdf', fn() => app(PDFService::class)->generate($cert)->stream())`
   - Verificar que el QR code es legible
   - Verificar que todos los textos están correctos
   - Verificar que el PDF es A4 (o el tamaño elegido)
4. Eliminar la ruta de prueba

---

### ✅ Fase 8 — Sistema de Traducción de Preguntas (Día 23-25)

**Objetivo:** Hacer que las preguntas se traduzcan y cacheen correctamente.

1. Completar `TranslationService.php`:
   - Método `translateText(string $text, string $targetLang): string`:
     ```php
     $response = Http::get('https://api.mymemory.translated.net/get', [
         'q' => $text,
         'langpair' => "en|{$targetLang}",
     ]);
     return $response->json()['responseData']['translatedText'] ?? $text;
     ```
   - Método `translateQuestion(Question $q, string $lang): array`:
     - Si `$lang === 'en'`: retornar directamente los campos de `$q`
     - Buscar en `QuestionTranslation` donde `question_id = $q->id AND language = $lang`
     - Si existe: retornar como array
     - Si no: traducir los 5 campos (pregunta + 4 opciones) llamando `translateText()` 5 veces
     - Guardar el resultado en `question_translations`
     - Retornar el array traducido
2. Verificar que el fallback a inglés funciona si MyMemory falla (envuelto en try-catch)
3. Agregar rate limiting interno: si MyMemory falla 3 veces seguidas, usar inglés directamente para esa sesión y loguear el error
4. Crear un Artisan Command para pre-traducir todas las preguntas a todos los idiomas:
   `php artisan make:command PreTranslateQuestions`
   - Recorre todos los idiomas soportados
   - Para cada pregunta que no tenga traducción en ese idioma, llama `translateQuestion()`
   - Ejecutar una vez antes del lanzamiento: `php artisan questions:pre-translate`

---

### ✅ Fase 9 — Internacionalización de la Interfaz (Día 26-28)

**Objetivo:** Traducir toda la interfaz a los 7 idiomas.

1. Crear la estructura de archivos en `lang/`:
   - `lang/en/app.php`, `lang/en/quiz.php`, `lang/en/cert.php`, `lang/en/results.php`
   - Repetir para: `es`, `pt`, `zh`, `hi`, `ar`, `fr`
2. Escribir todas las claves de traducción en inglés primero (idioma base)
3. Reemplazar todos los textos hardcodeados en las vistas Blade por `{{ __('app.key') }}`
4. Traducir manualmente los archivos de idioma al español (más importante)
5. Usar un LLM (puedo ayudarte) para traducir los archivos al resto de idiomas y verificar manualmente
6. Verificar que el selector de idioma en el header funciona correctamente cambiando el `locale` de sesión
7. Verificar que el texto en árabe se muestra con `dir="rtl"` correctamente
8. Verificar que el fallback a inglés funciona si se accede con un idioma no soportado

---

### ✅ Fase 10 — Scheduled Command de Limpieza (Día 29)

**Objetivo:** Configurar la tarea automática de limpieza de certificados expirados.

1. Crear el Command: `php artisan make:command CleanExpiredCertificates`
2. En `app/Console/Commands/CleanExpiredCertificates.php`:
   ```php
   protected $signature   = 'certificates:clean';
   protected $description = 'Delete certificates that have been expired for more than 1 year';

   public function handle(): void
   {
       $deleted = Certificate::where('expires_at', '<', now())->delete();
       $this->info("Deleted {$deleted} expired certificates.");
   }
   ```
3. Registrar el command en `routes/console.php`:
   ```php
   Schedule::command('certificates:clean')->daily()->at('03:00');
   ```
4. Configurar Railway para ejecutar el scheduler:
   - En Railway: ir a la configuración del servicio
   - Agregar un "Cron Job" con el comando: `php artisan schedule:run`
   - Configurar la frecuencia: `* * * * *` (cada minuto, Laravel decide qué ejecutar)
5. Verificar localmente ejecutando: `php artisan certificates:clean`

---

### ✅ Fase 11 — Despliegue Final en Railway (Día 30-31)

**Objetivo:** Asegurar que todo funciona en producción.

1. Crear `nixpacks.toml` en la raíz del proyecto para configurar Railway:
   ```toml
   [phases.setup]
   nixPkgs = ["php82", "php82Extensions.pdo", "php82Extensions.pdo_mysql", 
              "php82Extensions.bcmath", "php82Extensions.mbstring", 
              "php82Extensions.xml", "php82Extensions.curl",
              "php82Extensions.gd", "nodejs_20"]

   [phases.build]
   cmds = [
     "composer install --no-dev --optimize-autoloader",
     "npm ci",
     "npm run build",
     "php artisan config:cache",
     "php artisan route:cache",
     "php artisan view:cache"
   ]

   [start]
   cmd = "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT"
   ```
2. Hacer commit y push del `nixpacks.toml`
3. En Railway: verificar que el deploy se completó sin errores en los logs
4. Ejecutar el pre-translate en producción desde la consola de Railway:
   `php artisan questions:pre-translate`
5. Probar en el dominio `.up.railway.app`:
   - Flujo completo: seleccionar certificado → formulario → quiz → resultado → PDF
   - Buscador con serial conocido
   - Buscador con serial desconocido
   - Cambio de idioma manual
   - Vista pública de certificado por serial
   - Descarga de PDF
   - Botón de LinkedIn
6. Probar en móvil (Chrome DevTools → dispositivo móvil o desde un teléfono real)
7. Verificar que el rate limiting funciona (intentar el quiz dos veces el mismo día)
8. Verificar que el scheduler de Railway está activo

---

### ✅ Fase 12 — Polish y Lanzamiento (Día 32-35)

**Objetivo:** Pulir detalles antes del lanzamiento público.

1. Crear la página de empresa en LinkedIn (para el `organizationId` del botón)
2. Copiar el `organizationId` y agregarlo a las variables de entorno de Railway
3. Probar el botón de LinkedIn con un certificado real y verificar que se pre-rellena correctamente
4. Agregar favicon (puede ser el escudo/logo del Instituto)
5. Agregar metadatos `<title>` y `<meta description>` dinámicos por página
6. Verificar que el disclaimer "esto es una broma" es visible en todas las páginas
7. Escribir la política de privacidad mínima (puede ser una página simple en Blade)
8. Probar en Chrome, Firefox y Safari (PC y móvil)
9. Revisar que ninguna respuesta correcta sale del servidor (inspeccionar el network de DevTools)
10. Verificar que los números de documento nunca aparecen en texto plano en la DB
11. Hacer commit final con tag de versión: `git tag v1.0.0 && git push --tags`
12. 🚀 Lanzamiento — compartir la URL

---

## 21. 🔄 Diagrama de Flujo del Usuario {#flujo}

```
┌─────────────────────────────────────────────┐
│  USUARIO LLEGA A LA PÁGINA                  │
│  → Middleware SetLocale detecta idioma      │
│  → Si no está soportado, usa inglés         │
└───────────────────┬─────────────────────────┘
                    │
          ┌─────────▼─────────┐
          │   ¿Qué quiere?    │
          └──┬─────────────┬──┘
             │             │
   ┌─────────▼───────┐  ┌──▼──────────────────────┐
   │  Usar buscador  │  │ Obtener certificado nuevo│
   └─────────┬───────┘  └──┬──────────────────────┘
             │              │
   ┌─────────▼───────┐  ┌──▼──────────────────────┐
   │ Ingresa serial  │  │ Elige tipo:              │
   │ o documento     │  │ Orientación / Conducta   │
   └─────────┬───────┘  └──┬──────────────────────┘
             │              │
   ┌─────────▼───────┐  ┌──▼──────────────────────┐
   │ Buscar en DB    │  │ Formulario:              │
   └─────────┬───────┘  │ nombre, apellido,        │
             │          │ documento, país           │
   ┌─────────▼───────┐  └──┬──────────────────────┘
   │ ¿Encontrado?    │     │
   └──┬──────┬───────┘  ┌──▼──────────────────────┐
   No │      │ Sí       │ ¿Rate limit OK?          │
      │  ┌───▼──────┐   └──┬──────────┬────────────┘
      │  │¿Expirado?│   No │          │ Sí
      │  └──┬────┬──┘  ┌───▼───┐  ┌──▼──────────────────┐
      │  Sí │    │ No  │ Error │  │30 preguntas random   │
      │  ┌──▼─┐ ┌▼──────────┐  │  │ (traducidas al vuelo)│
      │  │Exp │ │Preview    │  └──┘ └──┬─────────────────┘
      │  │msg │ │cert + PDF │          │
      │  └────┘ │+ LinkedIn │  ┌───────▼────────────────┐
      │         └───────────┘  │ Livewire: responde 1x1 │
      │                        │ con barra de progreso  │
   ┌──▼──────┐                 └───────┬────────────────┘
   │ "No     │                         │
   │ found"  │                 ┌───────▼────────────────┐
   └─────────┘                 │ Servidor califica      │
                               │ (lógica en Livewire)   │
                               └───────┬────────────────┘
                                       │
                               ┌───────▼────────────────┐
                               │ Guardar en DB           │
                               │ (hash doc, resultado,  │
                               │  serial, fecha)        │
                               └───────┬────────────────┘
                                       │
                               ┌───────▼─────────────────────┐
                               │   PANTALLA DE RESULTADO     │
                               │  ① Resultado en grande      │
                               │  ② Puntuación (XX/30)       │
                               │  ③ [Descargar PDF]          │
                               │  ④ [Agregar a LinkedIn]     │
                               │  ⑤ [Compartir enlace]      │
                               │  ⑥ [Hacer otro certif.]    │
                               └───────┬─────────────────────┘
                                       │
                       ┌───────────────┴────────────────────┐
                       │                                     │
             ┌─────────▼────────┐               ┌───────────▼──────────┐
             │ Descargar PDF    │               │ /cert/{serial}        │
             │ (DomPDF, al     │               │ Vista pública         │
             │  momento)       │               │ verificable           │
             └──────────────────┘               └──────────────────────┘
```

---

## 22. ✅ Checklist Final {#checklist}

### Funcionalidad Core
- [ ] Página principal con buscador y dos tarjetas de certificados
- [ ] Formulario de datos (nombre, apellido, documento, país)
- [ ] Banco de 60+ preguntas por certificado (en inglés en DB)
- [ ] Selección aleatoria de 30 preguntas (Fisher-Yates)
- [ ] Opciones mezcladas aleatoriamente (mapa en propiedades del servidor Livewire)
- [ ] Verificación de respuestas 100% en servidor (Livewire)
- [ ] Calificación correcta (umbral 1/3)
- [ ] Cualquier persona puede presentar ambos certificados
- [ ] Generación de serial único (CERT-AÑO-TIPO-6CHARS)
- [ ] Guardado con hash bcrypt del documento
- [ ] Pantalla de resultado
- [ ] Descarga de PDF funcional y bien diseñado (DomPDF)
- [ ] Vista pública del certificado por serial (`/cert/{serial}`)
- [ ] Buscador por serial y por documento
- [ ] Rate limiting (1/día por IP + bloqueo por 30 días con mismo documento)
- [ ] Renovación cada 30 días
- [ ] Expiración y limpieza automática a 1 año
- [ ] Scheduled Command de limpieza diaria

### LinkedIn y Compartir
- [ ] Botón "Agregar a LinkedIn" en pantalla de resultado
- [ ] Botón "Agregar a LinkedIn" en vista pública del certificado
- [ ] URL de LinkedIn correctamente construida con todos los parámetros
- [ ] Página de empresa en LinkedIn creada para `organizationId`
- [ ] Metadatos OpenGraph en `/cert/{serial}`

### Idiomas y Detección Automática
- [ ] Detección automática del idioma del navegador (Accept-Language)
- [ ] Inglés como fallback si el idioma no está soportado
- [ ] Selector de idioma manual en el header
- [ ] Interfaz traducida en 7 idiomas (archivos `lang/`)
- [ ] Preguntas traducidas y cacheadas en `question_translations`
- [ ] Pre-translate ejecutado antes del lanzamiento
- [ ] Soporte RTL para árabe (`dir="rtl"`)
- [ ] Certificado PDF generado en el idioma del usuario

### Base de Datos Única de Preguntas
- [ ] Preguntas almacenadas en inglés en la tabla `questions`
- [ ] Tabla `question_translations` con caché de traducciones
- [ ] Integración con MyMemory API
- [ ] Fallback a inglés si la traducción falla
- [ ] Command `questions:pre-translate` ejecutado en producción

### Seguridad
- [ ] HTTPS activo (automático en Railway)
- [ ] Documentos de identidad hasheados (bcrypt, 12 rondas)
- [ ] IPs hasheadas antes de guardar
- [ ] Headers HTTP de seguridad configurados
- [ ] Variables de entorno en Railway (nunca en el código)
- [ ] Respuesta correcta NUNCA viaja al cliente
- [ ] Validación con Form Requests de Laravel
- [ ] `.env` en `.gitignore`

### UX/UI
- [ ] Responsive en móvil y desktop
- [ ] Barra de progreso en el quiz
- [ ] Mensajes de error claros y en el idioma del usuario
- [ ] Diseño profesional/institucional del certificado PDF
- [ ] QR code en el PDF que apunta a la vista pública
- [ ] Disclaimer visible "esto es una broma"
- [ ] Política de privacidad mínima

### Despliegue
- [ ] Repositorio en GitHub con `.gitignore` correcto (sin `.env`)
- [ ] CI/CD automático desde GitHub a Railway
- [ ] Base de datos MySQL configurada en Railway
- [ ] Cron Job de Railway ejecutando `php artisan schedule:run` cada minuto
- [ ] Todas las variables de entorno en Railway
- [ ] `nixpacks.toml` configurado correctamente
- [ ] Caché de config/rutas/vistas activada en producción

---

## 🗒️ Notas Finales

### Variables de entorno requeridas (`.env.example`)

```env
APP_NAME="Instituto de Certificaciones Dudosas"
APP_ENV=production
APP_KEY=                        # Generado con: php artisan key:generate
APP_DEBUG=false
APP_URL=https://tu-app.up.railway.app

DB_CONNECTION=mysql
DB_HOST=                        # Desde Railway MySQL plugin
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=                    # Desde Railway MySQL plugin
DB_PASSWORD=                    # Desde Railway MySQL plugin

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

LINKEDIN_ORG_ID=                # ID de tu página de empresa en LinkedIn
MYMEMORY_EMAIL=                 # Opcional: aumenta el límite diario de MyMemory
```

### Consideraciones legales y éticas
- Incluir disclaimer visible en todas las páginas: *"Este sitio es puramente humorístico. Los certificados no tienen validez legal, médica, psicológica ni oficial de ningún tipo."*
- Política de privacidad explicando: qué datos se recopilan, que los documentos se guardan hasheados, que los datos se eliminan al año
- No recopilar más datos de los necesarios

### ¿Por qué `CACHE_STORE=database` y no Redis?
Railway en su tier gratuito incluye MySQL pero no Redis. El driver de caché de base de datos de Laravel es perfectamente suficiente para este tipo de aplicación. Si el tráfico crece mucho en el futuro, migrar a Redis es un cambio de una sola línea en `.env`.

### Escalabilidad futura (sin costo adicional)
- Agregar más tipos de certificados (solo agregar nuevas preguntas y un nuevo `cert_type`)
- Estadísticas públicas anónimas (% de resultados globales)
- API pública de verificación de seriales
- Modo oscuro

---

*Documento de planificación — Versión 3.0 (Stack: Laravel 11 + Livewire + MySQL + Railway)*
