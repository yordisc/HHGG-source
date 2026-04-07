# Fase 6: Limpieza Técnica y Deprecación de cert_type

**Estado:** 🚧 En ejecución controlada  
**Prerequisito:** Fase 5 ✅ (seeders genéricos completados)  
**Cronograma sugerido:** 1-2 releases después de estabilizar Fase 5

---

## Objetivo

Remover completamente el campo `cert_type` de la arquitectura de base de datos y código, dejando solo `certification_id` como fuente de verdad. Esto ocurre después de garantizar que todo nuevo código use `certification_id` de forma exclusiva.

## Resultado de la auditoría real

La auditoría actual muestra dos clases de referencias a `cert_type`:

- Referencias legacy de base de datos y migraciones históricas.
- Referencias de contrato de entrada/salida en HTTP, CSV y tests, que siguen siendo intencionales en el flujo actual.

Conclusión:

- La limpieza de base de datos puede considerarse avanzada.
- La renombrada completa de contratos públicos no pertenece a esta Fase 6 y, si se desea, debe tratarse como una fase posterior separada.

---

## Auditoría Pre-Limpieza (Semana 1)

### 1. Escaneo de referencias legacy a `cert_type`

**Ejecutar búsqueda en codebase:**

```bash
# Buscar todos los usos de cert_type
grep -r "cert_type" app/ database/ resources/ --include="*.php" --include="*.blade.php" | grep -v "migrations/"
```

**Documentar hallazgos reales actuales:**

- ✅ **Migrations antiguas:** siguen conteniendo `cert_type` por historial y no deben interpretarse como uso activo.
- ✅ **HTTP / views / tests:** siguen usando `cert_type` como nombre de campo o parámetro de formulario.
- ✅ **Admin de preguntas / quiz / rate limit:** el nombre `cert_type` sigue siendo parte del contrato público actual.

**Hallazgos inesperados (si los hay):**

- ❌ Queries que filtren por `cert_type` en controllers como si fuera columna de BD → MIGRAR a `certification_id`
- ❌ Reports/analytics usando `cert_type` como persistencia → ACTUALIZAR
- ❌ APIs externas consumiendo `cert_type` como identificador de almacenamiento → deprecar con aviso de versionado

**Nota de alcance:**

- Si `cert_type` aparece en requests, formularios, query params o tests, no es necesariamente un error.
- Solo es un problema si el código lo usa como columna persistida o como filtro directo sobre el esquema viejo.

---

### 2. Monitoreo de Logs de Error

**Pre-deploy monitoring (una semana antes del despliegue):**

```bash
# Buscar en logs de aplicación referencias a cert_type
tail -f storage/logs/laravel.log | grep -i "cert_type"

# En Sentry/error tracking:
query: attribute:cert_type OR message:cert_type
timeframe: last 7 days
```

**Acciones si hay errores:**

- Si hay queries fallando: PAUSAR, investigar, actualizar QueryBuilder
- Si hay acceso a propiedad desaparecida: Verificar migraciones tomaron efecto

---

## Plan de Deprecación (Fase 6a: Warnings)

### 1. Agregar Deprecation Notices

**En `app/Models/Certificate.php`:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasAttributes;

class Certificate extends Model
{
    // ... otros código

    /**
     * El atributo cert_type está deprecado. Usar en su lugar certification_id.
     * 
     * @deprecated v2.1 Use certification_id instead
     * @removed v3.0
     */
    protected function getCertTypeAttribute(): ?string
    {
        \Log::warning('Access to deprecated attribute: Certificate.cert_type', [
            'certificate_id' => $this->id,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
        ]);

        // Fallback para compatibilidad
        return $this->certification?->slug;
    }
}
```

**En `app/Models/Question.php`:**

Aplicar el mismo patrón de deprecación.

### 2. Deprecation Log Monitor

```php
// In Kernel or Observer
// Monitor deprecation warnings
if (config('app.env') === 'production') {
    config(['logging.channels.deprecation' => [
        'driver' => 'single',
        'path' => storage_path('logs/deprecation.log'),
    ]]);
}
```

**Alertar al equipo ante deprecation spikes.**

---

## Limpieza de Código (Fase 6b: Removal)

### 1. Remover de Modelos

```php
// ANTES
class Certificate extends Model
{
    protected $fillable = ['cert_type', 'certification_id', ...];
    
    // Deprecado
    protected function getCertTypeAttribute() { ... }
}

// DESPUÉS
class Certificate extends Model
{
    protected $fillable = ['certification_id', ...];
    
    // REMOVIDO: getCertTypeAttribute deprecation
}

// Similar en Question.php
```

### 2. Remover de Seeders

```php
// ANTES
$rows[] = [
    'cert_type' => 'hetero',           // ← LEGACY
    'certification_id' => $certId,
    'prompt' => '...',
    ...
];

// DESPUÉS
$rows[] = [
    // cert_type REMOVIDO
    'certification_id' => $certId,
    'prompt' => '...',
    ...
];
```

**Archivos a actualizar:**

- `database/seeders/SocialEnergyQuestionsSeeder.php`
- `database/seeders/LifeStyleQuestionsSeeder.php`
- Cualquier otro seeder que use cert_type

### 3. Remover de Factories (Testing)

```php
// ANTES
public function definition()
{
    return [
        'cert_type' => 'hetero',
        'certification_id' => Certification::factory(),
        ...
    ];
}

// DESPUÉS
public function definition()
{
    return [
        // cert_type REMOVIDO
        'certification_id' => Certification::factory(),
        ...
    ];
}
```

---

## Limpieza de Base de Datos (Fase 6c: Migration)

### 1. Crear Migration de Deprecación

**Archivo:** `database/migrations/2026_05_XX_000100_deprecate_cert_type_column.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FASE 6a: Deprecation soft warning
     * - Agrega CHECK constraint que valida coherencia
     * - No remueve columna aún
     */
    public function up(): void
    {
        // Verificar que todos los rows con cert_type tienen certification_id valido
        Schema::table('questions', function (Blueprint $table) {
            // Agregar índice si no existe (para queries futuras sin cert_type)
            $table->index(['certification_id']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->index(['certification_id']);
        });

        // Log deprecation para monitoreo
        \Log::info('Deprecation Migration 6a applied: cert_type columns marked for removal in v3.0');
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['certification_id']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropIndex(['certification_id']);
        });
    }
};
```

---

### 2. Crear migración de eliminación

**Archivo:** `database/migrations/2026_06_XX_000100_remove_cert_type_column.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
    * FASE 6b/c: Remove cert_type columns permanently
     * 
     * PREREQUISITOS:
     * - Audit completado (no hay referencias activas a cert_type)
     * - Deprecation warnings monitoreados (< 5 en última semana)
     * - Seeders y factories actualizados
     * - Tests pasando sin cert_type
     */
    public function up(): void
    {
        if (!config('features.enable_cert_type_removal', false)) {
            throw new \Exception(
                'El feature flag features.enable_cert_type_removal debe estar activo. ' .
                'Completar auditoría antes de ejecutar.'
            );
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('cert_type');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('cert_type');
        });

        \Log::info('cert_type columns successfully removed');
    }

    public function down(): void
    {
        // No soportamos reversión de eliminación
        throw new \Exception('Cannot rollback cert_type removal. Restore from backup.');
    }
};
```

---

### 3. Feature flag para limpieza

**En `config/features.php` (nuevo archivo):**

```php
<?php

return [
    // Fase 6c: Remove cert_type from DB
    'enable_cert_type_removal' => env('FEATURE_CERT_TYPE_REMOVAL', false),
    
    // Fase 7+: Remove legacy QuizEligibilityService wrapper
    'enable_legacy_eligibility_removal' => env('FEATURE_LEGACY_ELIGIBILITY_REMOVAL', false),
];
```

**En `.env`:**

```env
# Activar solo después de auditoría completada
FEATURE_CERT_TYPE_REMOVAL=false
```

---

## Cronograma Sugerido

### Semana 1: Auditoría (Antes de iniciar Phase 6)

- [ ] Scan codebase por referencias cert_type
- [ ] Revisar logs históricos en Sentry/DataDog
- [ ] Verificar seeders y factories usan solo certification_id

**Criterio de paso:** 0 referencias activas, < 5 deprecation warnings en últimas 2 semanas

### Semana 2-3: Feature flag + deprecación (Fase 6a)

- [ ] Agregar deprecation warnings en modelos (logs)
- [ ] Crear migration "soft deprecation"
- [ ] Deploy a producción CON feature flag OFF
- [ ] Monitorear logs 1-2 semanas

**Criterio de paso:** Warnings de deprecación estables (< 1 por día), tests 100+ passing

### Semana 4: Limpieza de Código (Phase 6b)

- [x] Remover de Models (getCertTypeAttribute deprecations)
- [x] Remover de Seeders y Factories
- [x] Actualizar tests (quitar campos cert_type de fixtures)
- [x] Código review de cambios

**Criterio de paso:** Todos los tests passing, CI verde

### Semana 5: Limpieza de BD (Phase 6c)

- [x] Crear migration de removal
- [ ] Backup de BD (pre-migration safety)
- [ ] Activar feature flag: `FEATURE_CERT_TYPE_REMOVAL=true`
- [x] Ejecutar migration en staging local
- [x] Verificar selects/joins sin cert_type en entorno local
- [ ] Deploy a producción

**Criterio de paso:** Alarms cero, queries ejecutan < 5ms, no hay 500 errors

### Semana 6: Monitoreo + plan de rollback

- [ ] Monitorear error rates 24/7 por primeros 3 días
- [ ] Tener backup DB listo para restore si es necesario
- [ ] Si todo OK después de 1 semana → cerrar Fase 6
- [ ] Documentar lecciones aprendidas

### Criterio de cierre realista

- [ ] No quedan columnas `cert_type` en `questions` ni `certificates`.
- [ ] No quedan queries del core que dependan de `cert_type` como persistencia.
- [ ] Los contratos HTTP/CSV que aún usen `cert_type` están documentados y, si hace falta, tienen una fase posterior de renombrado.
- [ ] Los tests de migración, quiz y admin siguen pasando.

---

## Plan de rollback (si es necesario)

### Escenario: 500 errors después de remover cert_type

```bash
# 1. Revertir migration (último recurso)
php artisan migrate:rollback --step=1

# 2. O: Restore desde backup
# aws s3 cp s3://backups/db_pre_removal.sql ./
# mysql < db_pre_removal.sql

# 3. Investigar source of error
tail -f storage/logs/laravel.log | head -20

# 4. Fix en código
# Ejemplo: Query que aún usa cert_type finder scope
# Before:   Question::whereCertType('hetero')->get()
# After:    Question::whereHasCertification(fn($q) => $q->where('slug', 'hetero'))->get()

# 5. Re-deploy con fix
```

---

## Próximas fases post-6

### Fase 7: Remover QuizEligibilityService Wrapper (1 release después)

- Eliminar completamente QuizEligibilityService
- Verificar no hay referencias externas
- Limpieza histórica de código deprecado

### Fase 8: Menú de administrador seguro (2+ releases después)

- Crear dashboard oculto bajo `/admin` con navegación centralizada
- Mantener auth por `ADMIN_ACCESS_KEY` + sesión + middleware `admin.auth`
- Separar módulos de Certificaciones, Preguntas y Usuarios
- Exponer CRUD completo de certifications desde el panel
- Permitir activar y desactivar cualquier certificación desde el listado o el formulario
- Integrar importación/exportación de preguntas y usuarios
- El modulo de usuarios ya debe cubrir listado, alta, edicion, eliminacion y exportacion
- Soportar metadata multilenguaje para certifications
- Hacer que la home refleje cambios de admin sin trabajo manual adicional

Referencia técnica detallada: [docs/planificacion/MENU_ADMIN_SEGURO.md](docs/planificacion/MENU_ADMIN_SEGURO.md)

---

## Matriz de Cobertura de Pruebas (Fase 6)

### Cobertura actual validada

- Flujo base de quiz y navegación principal (home, registro, quiz, resultado, certificado, PDF).
- Reglas de scoring, elegibilidad y resolución de resultados por certificación (unit tests).
- Validaciones de inicio de quiz por país/tipo de documento/certificación activa.
- Endpoint de elegibilidad (`/exam/eligibility-check`) con casos válidos y bloqueos por cooldown.
- Rate limit de inicio de quiz en ventana corta (bloqueo en segundo intento inmediato).
- Contratos CSV de admin: export, import válido, import inválido y template.
- Contratos de presentación: enlace LinkedIn y cabeceras/firma de PDF.

### Comandos usados para validación

- `composer test:feature`
- `composer test:unit`
- `composer test:all`

### Riesgos residuales de testing

- Falta una suite dedicada de regresión E2E de UI en navegador real.
- No hay tests de carga/concurrencia para picos de intentos en rate limit.
- Falta cobertura de scheduler/operación para runbooks de despliegue y retención.

---

## Cierre Operativo (Ready to Close)

### 1) Checklist corto de despliegue final

- [ ] Confirmar backup de base de datos en staging.
- [ ] Ejecutar migraciones en staging y validar que no existe columna `cert_type`.
- [ ] Ejecutar suite completa (`bash scripts/local-test.sh`) y smoke test manual en staging.
- [ ] Revisar logs de aplicación por 24h (errores 5xx, SQL exceptions, consultas de certificación).
- [ ] Confirmar backup de base de datos en producción.
- [ ] Ejecutar migraciones en producción en ventana controlada.
- [ ] Verificar endpoints críticos (home, quiz, resultado, certificado, PDF, admin CSV).
- [ ] Monitorear 72h y cerrar Fase 6 en documentación.

### 2) Comandos concretos (staging/producción)

```bash
# 0. En servidor destino
php -v
php artisan --version

# 1. Backup (adaptar al motor de BD)
# MySQL/MariaDB:
mysqldump -u <user> -p <database> > backup_pre_fase6.sql

# 2. Despliegue
git pull origin main
composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build

# 3. Migraciones
php artisan migrate --force

# 4. Verificación rápida de esquema (sin cert_type)
php artisan tinker --execute="dump(\Illuminate\Support\Facades\Schema::hasColumn('questions', 'cert_type')); dump(\Illuminate\Support\Facades\Schema::hasColumn('certificates', 'cert_type'));"

# 5. Cachés y optimización
php artisan optimize:clear
php artisan optimize

# 6. Pruebas y smoke
bash scripts/local-test.sh
```

### 3) Criterio formal para marcar Fase 6 como completada

- [ ] `local-test.sh` en verde.
- [ ] Migración de eliminación aplicada en staging y producción.
- [ ] `Schema::hasColumn(..., 'cert_type')` retorna `false` para `questions` y `certificates`.
- [ ] 0 errores críticos en logs durante la ventana de monitoreo.
- [ ] Documentación actualizada en [docs/planificacion/FASE_6_LIMPIEZA_TECNICA.md](docs/planificacion/FASE_6_LIMPIEZA_TECNICA.md) y [docs/planificacion/PLAN_ESCALABILIDAD_CERTIFICACIONES.md](docs/planificacion/PLAN_ESCALABILIDAD_CERTIFICACIONES.md).

---

## Checklist Final de Limpieza

### Antes de deploy a producción:

- [x] Tests: `phpunit` ✅ 100% en entorno local (`bash scripts/local-test.sh`)
- [ ] Lints: `pint` ✅ no errors
- [ ] Static analysis: `phpstan` ✅ level 5+
- [ ] DB audit: `php artisan tinker` → verify certification_id foreign keys
- [ ] Feature flag: `FEATURE_CERT_TYPE_REMOVAL=false` initially, then true
- [ ] Rollback plan documented y tested en staging
- [ ] Monitoring alerts configurados (error rate, query performance)
- [ ] Changelog updated con breaking changes (v2.0 → v3.0)

---

## Referencias

- Phase 5 Completion: [docs/AGREGAR_NUEVA_CERTIFICACION.md](AGREGAR_NUEVA_CERTIFICACION.md)
- Service Layer: [app/Support/Certification*Service.php](../app/Support)
- Certification Model: [app/Models/Certification.php](../app/Models/Certification.php)
- Migration Strategy: [Laravel Migrations](https://laravel.com/docs/11.x/migrations)

---

**Última actualización:** 2026-04-02  
**Versión:** Fase 6 - planificación con cobertura de pruebas actualizada  
**Responsable:** Backend Lead  
**Estado:** 🚧 En ejecución controlada (pendiente cierre operativo y despliegue)
