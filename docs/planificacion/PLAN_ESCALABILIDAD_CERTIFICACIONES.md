# Plan de Escalabilidad para Nuevas Certificaciones

## Objetivo

Permitir agregar nuevas certificaciones sin tocar múltiples archivos del core (rutas, controladores, Livewire, vistas, seeders), moviendo la configuración a datos y servicios reutilizables.

## Situación Actual (Resumen)

Actualmente el sistema está acoplado a dos certificaciones fijas (`hetero`, `good_girl`) en varios puntos:

- Validaciones hardcodeadas en controladores y middleware.
- Mapeo de resultado por `match` en flujo de quiz.
- Tarjetas fijas en Home.
- Filtros fijos en Admin de preguntas.
- Seeders por certificación.
- Reglas de cantidad de preguntas y aprobación globales, no por certificación.

Esto hace que agregar una certificación implique cambios en muchas capas y eleva el riesgo de regresiones.

---

## Arquitectura Objetivo

### 1) Catálogo de certificaciones (data-driven)

Crear la entidad `certifications` como fuente única de verdad para:

- `slug` (unico, usado en URLs)
- `name`
- `description`
- `active`
- `questions_required`
- `pass_score_percentage`
- `cooldown_days`
- `result_mode` (estrategia de calculo)
- `pdf_view` (vista blade a usar)
- `home_order`
- `theme` (opcional)

### 2) Relacionar preguntas con certificación por ID

Cambiar `questions.cert_type` por `questions.certification_id` con FK.

- Evitar strings sueltos.
- Facilitar filtros, indexacion y consistencia referencial.

### 3) Motor de reglas por certificación

Introducir servicios:

- `CertificationResolverService` (carga certificación por slug)
- `CertificationScoringService` (calcula score y estado)
- `CertificationResultResolverService` (asigna `result_key`)
- `CertificationEligibilityService` (cooldown por certificación)
- `CertificationPresentationService` (título, etiquetas, textos y PDF por certificación)

### 4) UI dinámica

- Home lista certificaciones activas desde BD.
- Registro/quiz reciben `certification_slug` y resuelven metadata desde catálogo.
- Admin usa catálogo para filtros y formularios.

---

## Plan de Implementación por Fases

## Fase 0 - Preparación y respaldo

1. Crear rama de trabajo dedicada.
2. Respaldar base de datos (staging/prod).
3. Definir feature flag opcional (`CERTIFICATIONS_V2=true`) para rollout progresivo.
4. Acordar ventana de despliegue para migraciones de esquema.

Criterio de salida:

- Entorno de staging listo para pruebas de migración.

## Fase 1 - Esquema de datos

### 1.1 Crear tabla `certifications`

Columnas recomendadas:

- `id` (PK)
- `slug` (unique)
- `name`
- `description` nullable
- `active` boolean default true
- `questions_required` tinyInteger default 30
- `pass_score_percentage` decimal(5,2) default 66.67
- `cooldown_days` smallInteger default 30
- `result_mode` string default `binary_threshold`
- `pdf_view` string default `pdf.certificate`
- `home_order` smallInteger default 100
- `settings` json nullable
- timestamps

Índices:

- unique(`slug`)
- index(`active`, `home_order`)

### 1.2 Extender `questions`

Agregar:

- `certification_id` nullable al inicio
- FK a `certifications(id)`
- index(`certification_id`, `active`)

### 1.3 Backfill de preguntas

- Crear registros en `certifications` para slugs actuales (`hetero`, `good_girl`).
- Mapear `questions.cert_type -> questions.certification_id`.

### 1.4 Endurecer esquema

Cuando el backfill este validado:

- `questions.certification_id` NOT NULL
- Mantener `cert_type` temporalmente como compatibilidad (1 release)

Criterio de salida:

- Todas las preguntas referencian una certificación válida por FK.

## Fase 2 - Dominio y modelos

### 2.1 Nuevo modelo `Certification`

Relaciones:

- `Certification hasMany Question`
- `Certification hasMany Certificate`

Scopes utiles:

- `active()`
- `ordered()`

### 2.2 Adaptar modelo `Question`

- Agregar `belongsTo Certification`
- Mantener accessor temporal para `cert_type` derivado de `certification.slug` (compatibilidad)

### 2.3 Adaptar modelo `Certificate`

- Agregar `certification_id` FK (si hoy solo hay `cert_type`)
- Backfill desde `cert_type`
- Mover reglas de cooldown a `Certification`

Criterio de salida:

- El dominio ya no depende de strings de certificación en nuevas operaciones.

## Fase 3 - Casos de uso (backend) ✅ COMPLETADO

### 3.1 Refactor `QuizController` ✅

- ✅ Resolver certificación por slug desde BD.
- ✅ Eliminar `in_array(['hetero','good_girl'])`.
- ✅ Usar parametros de `Certification` para:
  - cantidad de preguntas minima
  - nombre visible
  - reglas de elegibilidad

**Estado:** Implementado con método `resolveActiveCertification()` centralizado.

### 3.2 Refactor `QuizRunner` ✅

- ✅ Reemplazar `match ($this->certType)` por `CertificationResultResolverService`.
- ✅ Usar `questions_required` y `pass_score_percentage` por certificación.
- ✅ Usar `CertificationScoringService` para calculo de score/fail.

**Estado:** Limpio, sin lógica de scoring hardcodeada. Servicios bajo `app\Support\`.

### 3.3 Refactor `CertificationEligibilityService` ✅

- ✅ Cooldown por certificación (`cooldown_days`). 
- ✅ Mantener validaciones anti-bypass en backend.
- ✅ Recibe objeto `Certification` en lugar de string, eliminando duplicidad.

**Estado:** `CertificationEligibilityService` nuevo y activo. `QuizEligibilityService` deprecado, convertido en wrapper compatible.

### 3.4 Refactor `QuestionAdminController` ✅

- ✅ Filtros por `certification_id` / `slug` dinamicos.
- ✅ Validaciones de import/export CSV basadas en catálogo.

Criterio de salida: ✅

- ✅ Flujo completo de quiz funciona sin condicionantes hardcodeadas por slug fijo.
- ✅ Servicios `CertificationScoringService`, `CertificationEligibilityService`, `CertificationResultResolverService` implementados.
- ✅ Validaciones de análisis estático: sin errores.

### Notas de migración

1. **QuizEligibilityService deprecado:** El servicio original de elegibilidad se convierte en wrapper que delega a `CertificationEligibilityService`. Mantiene compatibilidad si hay consumidores externos.

2. **Certificación como modelo activo:** El flujo ahora consulta `Certification::active()` en lugar de validar slugs hardcodeados. Las nuevas certificaciones se agregan vía BD sin cambios en código.

## Fase 4 - Presentación (frontend/views) ✅ COMPLETADO

### 4.1 Home dinámica ✅

- ✅ Reemplazar tarjetas fijas por loop de certificaciones activas.
- ✅ Mostrar `name`, `description`, CTA hacia `quiz.register`.

**Estado:** `HomeController` inyecta `certifications` activas; `home.blade.php` las renderiza dinámicamente.

### 4.2 Registro dinámico ✅

- ✅ Mostrar metadata de certificación actual.
- ✅ Mantener validaciones de identidad/documento ya implementadas.

**Estado:** `quiz.register` carga certificación y datos de país/documento.

### 4.3 Resultado y certificado

- ✅ Resolver textos por `result_key` + configuración de certificación.
- Permitir vista PDF por certificación (`pdf_view`) sin editar controlador.

Criterio de salida: ✅

- ✅ Crear certificación en BD y verla en Home sin cambios de código en vistas fijas.
- ✅ Resultado asignado vía `CertificationResultResolverService`.

## Fase 5 - Seeders y contenidos ✅ COMPLETADO

### 5.1 Seeder de catálogo ✅

**Estado:** ✅ `CertificationSeeder` existe con certificaciones base (`hetero`, `good_girl`).

### 5.2 Seeders de preguntas parametrizados ✅

**Estado:** ✅ Completado
- ✅ Creado `QuestionSeederHelper` clase reutilizable en `database/seeders/`
- ✅ Refactorizado `SocialEnergyQuestionsSeeder` para usar el helper
- ✅ Refactorizado `LifeStyleQuestionsSeeder` para usar el helper
- ✅ Método `seedQuestionsForCertification()` acepta slug + array de preguntas + opciones personalizables

**Código base para nuevas certificaciones:**

```php
// database/seeders/MyNewCertSeeder.php
class MyNewCertSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            ['prompt' => 'Question text', 'correct' => 1],
            // ... 29 más
        ];
        
        QuestionSeederHelper::seedQuestionsForCertification('my_new_slug', $questions);
    }
}
```

### 5.3 Documentación de procedimiento ✅

**Estado:** ✅ `docs/AGREGAR_NUEVA_CERTIFICACION.md` completo
- ✅ Guía paso a paso para agregar certificación vía BD
- ✅ Ejemplos SQL para insert directo
- ✅ Ejemplos seeder parametrizados
- ✅ Procedimiento CSV import
- ✅ Traducción multiidioma
- ✅ Vista PDF personalizada
- ✅ Troubleshooting común
- ✅ Tabla de referencia de modos de resultado
- ✅ Plan futuro: Admin CLI commands y UI

### 5.4 Carga por CSV

**Estado:** Soportado en `QuestionAdminController`
- Campo `certification_slug` reconocido en validación
- Import genera rows con `certification_id` correcto

Criterio de salida: ✅

- ✅ Nueva certificación + su banco de preguntas se carga por datos, no por código nuevo.
- ✅ Procedimiento documentado y accesible para el equipo
- ✅ Seeders reutilizables sin duplicación de lógica

## Fase 6 - Compatibilidad y limpieza técnica 🚧 EN EJECUCION

**Estado:** 🚧 En ejecución controlada (limpieza aplicada en código + cobertura ampliada; pendiente cierre operativo)

**Prerequisitos verificados:**
- ✅ Servicios nuevos (Scoring, Eligibility, ResultResolver) activos sin referencias a cert_type
- ✅ Seeders refactorizados a usar solo certification_id
- ✅ HomeController, QuizController, QuizRunner limpios
- ✅ Plan de deprecación documentado en [docs/planificacion/FASE_6_LIMPIEZA_TECNICA.md](FASE_6_LIMPIEZA_TECNICA.md)

### 6.1 Fase Deprecation (6a)

- Agregar deprecation warnings en acceso a `cert_type` (logs)
- Crear índices en `certification_id` para consultas post-removal
- Deploy a producción con monitoreo 1-2 semanas
- Recopilar metrics de acceso legacy

**Feature flag:** `FEATURE_CERT_TYPE_REMOVAL=false` (default)

### 6.2 Limpieza de código (6b)

- Remover `getCertTypeAttribute()` deprecation del modelo
- Remover `cert_type` de seeders y factories
- Actualizar tests para no usar cert_type
- Code review antes de merge

### 6.3 Limpieza de BD (6c)

- Crear migración remover columna `cert_type` de questions y certificates
- Backup pre-migration en staging
- **Feature flag requerido:** `FEATURE_CERT_TYPE_REMOVAL=true` en .env
- Deploy con logs 24/7 de monitoreo

**Plan de rollback:** Restaurar desde backup si hay errores críticos en los primeros 3 días

### 6.4 Cierre y documentación

- Changelog con breaking changes (v2.0 → v3.0)
- Versión estable de AGREGAR_NUEVA_CERTIFICACION.md sin referencias cert_type
- Lecciones aprendidas documentadas

**Criterio de salida:**
- ✅ sin columna `cert_type` en BD
- ✅ código limpio sin campos legacy
- ✅ 0 referencias a `cert_type` en codebase activo
- ✅ Documentación actualizada

### 6.5 Próximas fases post-6

**Fase 7 (Release +2):** Remover `QuizEligibilityService` wrapper completamente
- Validar 0 referencias externas
- Cleanup histórico

**Fase 8 (Release +3+):** Panel admin para certificaciones
- CRUD UI para certifications
- Validación JSON settings en tiempo real
- CSV import integrado en admin

Referencia completa: [docs/planificacion/FASE_6_LIMPIEZA_TECNICA.md](FASE_6_LIMPIEZA_TECNICA.md)

### 6.6 Cobertura de pruebas actualizada

- Validaciones de inicio de quiz por país, tipo de documento y certificación activa.
- Endpoint de elegibilidad con casos válidos, payload inválido y cooldown activo.
- Rate limit de inicio de quiz con bloqueo en segundo intento inmediato.
- Contratos CSV de admin (export/import/template) con verificación de formato.
- Contratos de presentación de certificado (LinkedIn y cabeceras/firma PDF).
- Cobertura Livewire de `QuizRunner` para redirecciones y creación de certificado.

---

## Pruebas requeridas

## Unitarias

- Resolver certificación por slug activo/inactivo.
- Scoring por certificación.
- Elegibilidad por cooldown y tipo de certificación.

## Feature

- Home lista certificaciones activas.
- Registro y quiz para certificación nueva creada en BD.
- Bloqueo por cooldown con fecha/hora correcta.
- Admin crea/edita preguntas para cualquier certificación activa.

## Integracion

- Import CSV con `certification_slug`.
- Generacion de PDF segun `pdf_view`.
- LinkedIn URL con metadata correcta.

## Regresion

- Certificaciones existentes (`hetero`, `good_girl`) mantienen comportamiento actual.

---

## Riesgos y mitigaciones

1. Riesgo: ruptura de datos al migrar `cert_type`.
- Mitigación: backfill en staging + scripts idempotentes + rollback.

2. Riesgo: resultados distintos por cambio de scoring.
- Mitigación: conservar `result_mode` actual como default y migrar gradualmente.

3. Riesgo: vistas con traducciones faltantes para certificación nueva.
- Mitigación: validación en admin y fallback por locale.

4. Riesgo: consultas mas complejas.
- Mitigación: índices (`certification_id`, `active`) y eager loading.

---

## Checklist técnico (ejecutable) — Estado actual

### Completados ✅

1. ✅ Crear migracion `create_certifications_table` (2026-04-02)
2. ✅ Crear migracion `add_certification_id_to_questions` (2026-04-02)
3. ✅ Backfill `certifications` y `questions` (2026-04-02)
4. ✅ Crear modelo `Certification` + relaciones (2026-04-02)
5. ✅ Refactor `QuizController` a resolver por `Certification` (resolveActiveCertification)
6. ✅ Refactor `QuizRunner` para scoring/resultados dinámicos (usando servicios)
7. ✅ Servicios nuevos: CertificationScoringService, CertificationEligibilityService, CertificationResultResolverService
8. ✅ Refactor admin preguntas (filtros, forms por certification_id)
9. ✅ Home dinámica por certificaciones activas
10. ✅ Crear `CertificationSeeder` + refactorizar seeders vía `QuestionSeederHelper`
11. ✅ Crear tests unit para servicios nuevos (3 archivos test)
12. ✅ Documentación equipo: [AGREGAR_NUEVA_CERTIFICACION.md](../AGREGAR_NUEVA_CERTIFICACION.md)

### En progreso 🚧

13. ✅ Batch de feature tests ampliado (validaciones, elegibilidad, rate-limit, CSV, Livewire, contratos de presentación)
14. 🚧 Desplegar a staging local y validar flujos

### Pendientes ⏳

15. ⏳ Deploy producción con monitoreo (tras validar staging)
16. 🚧 Fase 6 limpieza técnica en ejecución: [FASE_6_LIMPIEZA_TECNICA.md](FASE_6_LIMPIEZA_TECNICA.md)
17. ⏳ Remover campos legacy en release posterior

**Última actualización:** 2026-04-02 con avance de Fase 6 y cobertura de pruebas ampliada

---

## Definition of Done

Se considera completado cuando:

- Agregar una certificación nueva requiere solo datos (BD + preguntas), no cambios en controladores principales.
- Home, registro, quiz, resultado, certificado y admin la reconocen automáticamente.
- Las pruebas pasan y no hay regresiones en certificaciones existentes.
- Se documenta el procedimiento de alta de certificaciones para el equipo.
