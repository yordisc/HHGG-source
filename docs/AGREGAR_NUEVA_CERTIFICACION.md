# Guía: Agregar Nueva Certificación

Esta guía explica cómo agregar una nueva certificación al sistema de manera completamente genérica, sin tocar código de aplicación.

## Requerimientos

- Acceso a base de datos (CLI o admin panel futura)
- Archivo CSV con preguntas (opcional, para import masivo)
- Conocimiento del slug de la certificación (ej: `compatibility`, `finance`)

---

## Proceso Paso a Paso

### 1️⃣ Crear la Certificación en BD

**Opción A: CLI Ad-hoc (Una sola certificación)**

```sql
INSERT INTO certifications (
    slug,
    name,
    description,
    cooldown_days,
    pass_score_percentage,
    questions_required,
    result_mode,
    home_order,
    settings,
    active,
    created_at,
    updated_at
) VALUES (
    'financial_health',                           -- slug: único, URL-safe
    'Financial Health Certification',             -- nombre visible
    'Descubre tu relación con el dinero',        -- descripción breve
    30,                                           -- espera entre intentos
    70,                                           -- % mínimo para pasar (0-100)
    30,                                           -- número de preguntas del quiz
    'binary_threshold',                           -- modo resultado (binary_threshold | custom | generic)
    3,                                            -- orden en home (1=primera, 2=segunda, etc)
    JSON_OBJECT(
        'pdf_view', 'financial_health_pdf.blade.php',
        'result_keys', JSON_OBJECT(
            'passed', 'financially_aware',
            'failed', 'financially_at_risk'
        )
    ),
    1,                                            -- active=1
    NOW(),
    NOW()
);
```

**Opción B: Crear Seeder específico (Recomendado para CI/CD)**

Crear archivo `database/seeders/FinancialHealthQuestionsSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FinancialHealthQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            ['prompt' => '¿Con qué frecuencia revisas tu presupuesto?', 'correct' => 1],
            ['prompt' => '¿Tienes fondo de emergencia?', 'correct' => 2],
            // ... 28 preguntas más (30 total)
        ];

        QuestionSeederHelper::seedQuestionsForCertification(
            'financial_health',
            $questions,
            minCount: 30
        );
    }
}
```

Luego agregar a `database/seeders/DatabaseSeeder.php`:

```php
$this->call([
    CertificationSeeder::class,
    SocialEnergyQuestionsSeeder::class,
    LifeStyleQuestionsSeeder::class,
    FinancialHealthQuestionsSeeder::class,  // ← Nueva
    QuestionTranslationsSeeder::class,
    LocalizedQuestionTranslationsSeeder::class,
]);
```

Ejecutar: `php artisan db:seed`

---

### 2️⃣ Agregar Preguntas (Alternativa: CSV Import)

**Crear CSV `questions_financial_health.csv`:**

```csv
prompt,correct,option_1,option_2,option_3,option_4,certification_slug
"¿Con qué frecuencia revisas tu presupuesto?",1,Siempre,A veces,Raramente,Nunca,financial_health
"¿Tienes fondo de emergencia?",2,Siempre,A veces,Raramente,Nunca,financial_health
"¿Utilizas herramientas de ahorro?",3,Siempre,A veces,Raramente,Nunca,financial_health
```

**Usar admin panel futura o CLI:**

```bash
# Una vez implementado el endpoint CSV en admin
POST /admin/questions/import
Content-Type: multipart/form-data
file: questions_financial_health.csv
```

---

### 3️⃣ Traducir Preguntas (Multiidioma)

El sistema de traducción es automático vía `QuestionTranslationsSeeder` y `LocalizedQuestionTranslationsSeeder`.

**Manualmente (si es necesario):**

```sql
INSERT INTO question_translations (
    question_id,
    locale,
    prompt,
    created_at,
    updated_at
) VALUES (
    (SELECT id FROM questions WHERE prompt = '¿Con qué frecuencia revisas tu presupuesto?' LIMIT 1),
    'en',
    'How often do you review your budget?',
    NOW(),
    NOW()
);
```

---

### 4️⃣ Crear Vista PDF de Resultado (Opcional)

Si `settings['pdf_view'] = 'financial_health_pdf.blade.php'`, crear:

`resources/views/certificates/financial_health_pdf.blade.php`:

```blade
<div class="pdf-container">
    <h1>{{ $certificate->certification->name }}</h1>
    <p>Resultado: <strong>{{ $certificate->result_key }}</strong></p>
    <p>Puntuación: {{ $certificate->score_numeric }}%</p>
    <p>Fecha: {{ $certificate->completed_at->format('d/m/Y') }}</p>
</div>
```

---

### 5️⃣ Verificar Funcionamiento

**Comprobar que aparece en home:**

1. Ir a `http://localhost/` (o URL de producción)
2. Debería aparecer tarjeta con nombre de certificación
3. Clickear → debe llevar a `/quiz/{slug}`

**Comprobar que el quiz funciona:**

1. Clickear en certificación del home
2. Verificar que el registro valide país/documento
3. Tomar quiz completo
4. Verificar resultado correcto (basado en score vs `pass_score_percentage`)
5. Descargar PDF del certificado

---

## Tabla de Referencia: Modos de Resultado

| Modo | Comportamiento | Ejemplo | Cuándo usar |
|------|---|---|---|
| `binary_threshold` | Pasar/Fallar basado en % de score | passed_60+ / failed_<60 | Mayoría de casos |
| `custom` | Usar `settings['result_keys']` | Cualquiera que definas | Lógica personalizada |
| `generic` | Fallback: passed_generic / failed_generic | (cualquier entidad) | Plantilla provisional |

---

## Configuración de Campos

### Certifications Table

| Campo | Tipo | Descripción |
|-------|------|---|
| `slug` | VARCHAR(255) | Identificador único (URL-safe) |
| `name` | VARCHAR(255) | Nombre visible en UI |
| `description` | TEXT | Descripción breve en cards home |
| `cooldown_days` | INT | Días antes de permitir reintento (0 = sin límite) |
| `pass_score_percentage` | INT (0-100) | Umbral mínimo de % para "pasar" |
| `questions_required` | INT | Número de preguntas del quiz |
| `result_mode` | ENUM | binary_threshold \| custom \| generic |
| `pdf_view` | VARCHAR(255) \| NULL | Path blade relativo para PDF |
| `home_order` | INT | Orden en listado home (ASC) |
| `settings` | JSON | Config adicional (result_keys, etc) |
| `active` | BOOLEAN | Mostrar en home y permitir quizzes |

---

## Ejemplos Completos

### Ejemplo 1: Certificación Minimalista (Generic)

```sql
INSERT INTO certifications (...) VALUES (
    'quick_check',
    'Quick Check',
    'Una verificación rápida',
    7,
    60,
    15,
    'generic',
    4,
    '{}',
    1,
    NOW(),
    NOW()
);
```

Resultado: Automáticamente `passed_generic` o `failed_generic`

### Ejemplo 2: Certificación con Lógica Personalizada

```sql
INSERT INTO certifications (...) VALUES (
    'personality',
    'Personality Profile',
    'Descubre tu tipo de personalidad',
    0,
    50,
    40,
    'custom',
    2,
    JSON_OBJECT(
        'result_keys', JSON_OBJECT(
            'analyst', 'Eres analítico/a',
            'creative', 'Eres creativo/a',
            'leader', 'Eres líder',
            'supporter', 'Eres de apoyo'
        )
    ),
    1,
    NOW(),
    NOW()
);
```

---

## Troubleshooting

| Problema | Causa | Solución |
|----------|------|----------|
| No aparece en home | `active=0` o `home_order` muy alto | Verificar `SELECT * FROM certifications WHERE slug='...'` |
| Quiz no abre | `certification_id` FK mismatch | Verificar que `questions.certification_id` apunta a row correcto |
| Score incorrecto | `pass_score_percentage` mal configurado | Revisar lógica en CertificationScoringService |
| PDF no genera | Vista blade no existe | Verificar path en `settings['pdf_view']` |
| Cooldown no funciona | Valor muy bajo o identidad_lookup_hash vacío | Ver config/quiz.php y CertificateRepository |

---

## Próximos Pasos: Automatizar Agregación

**Fase 6+ — Panel admin (planeado):**

- Crear endpoint POST `/admin/certifications` 
- Validar slug únicos
- UI para crear certificación + subir CSV de preguntas
- Generar plantilla PDF automáticamente

**Fase 6+ — CLI Command:**

```bash
php artisan cert:make financial_health "Financial Health" \
  --cooldown=30 \
  --pass-score=70 \
  --questions=30 \
  --csv=questions.csv
```

---

**Última actualización:** 2026-04-02  
**Versión:** Fase 5 - completada  
**Estado:** ✅ Seeders genéricos y documentación listos
