# Documentación detallada del proyecto HHGG-source

Última actualización: 2026-04-14

## 1. Resumen funcional

HHGG-source es una aplicación Laravel 11 + Livewire que permite:

- Registrar candidato para un quiz humorístico.
- Ejecutar quiz de N preguntas configurables por certificación, con randomización configurable de preguntas y opciones.
- Calcular resultado con scoring ponderado, reglas automáticas y muerte súbita según configuración.
- Generar certificado con serial único.
- Publicar vista verificable por serial.
- Descargar certificado en PDF.
- Compartir resultado en LinkedIn.
- Buscar certificados por serial o documento (hash de consulta).

## 2. Flujo end-to-end

1. Home: selección de certificado o búsqueda de serial/documento.
2. Registro: datos del candidato por tipo de certificación.
3. Quiz: preguntas aleatorias + traducción por idioma.
4. Resultado: estado final + puntaje + accesos rápidos.
5. Certificado público: validación y datos públicos.
6. PDF: descarga del certificado en formato formal.

## 3. Stack técnico

- Backend: Laravel 11 (PHP 8.4+)
- UI interactiva: Livewire 4
- Frontend build: Vite + Tailwind (npm)
- PDF: barryvdh/laravel-dompdf
- Persistencia: MySQL
- Despliegue objetivo: Railway/Nixpacks

## 4. Estructura principal

- app/Http/Controllers: Home, Quiz, Certificate, Admin.
- app/Livewire: motor QuizRunner.
- app/Http/Middleware: locale, rate limit, headers seguros, auth admin.
- app/Models: Certificate, Question, QuestionTranslation, RateLimit.
- database/migrations: schema operativo del MVP.
- database/seeders: preguntas base + traducciones iniciales.
- resources/views: flujo público + admin + PDF.
- lang/\*/app.php: interfaz multilenguaje.
- routes/web.php: rutas públicas y admin.
- routes/console.php: scheduler diario.

## 5. Modelo de datos (resumen)

### certificates

- serial, certification_id, result_key
- first_name, last_name, country
- country_code, document_type, document_hash, doc_lookup_hash, identity_lookup_hash, doc_partial
- score_correct, score_incorrect, total_questions
- score_numeric, issued_at, completed_at, next_available_at, expires_at, last_attempt_at
- certification_expires_at, download_expires_at
- result_decision_source, result_decision_reason

### questions

- certification_id, prompt
- option_1..option_4, correct_option
- type, weight, sudden_death_mode, active

### question_translations

- question_id, language
- prompt, option_1..option_4

### rate_limits

- identifier_hash, scope, attempted_at

## 6. Seguridad y privacidad

- Documento legal no se guarda en texto plano.
- document_hash con bcrypt.
- doc_lookup_hash (HMAC) para búsqueda.
- identity_lookup_hash para control de intentos por identidad.
- Middleware de headers seguros en grupo web.
- Rate limit por IP/documento y reglas de renovacion.
- Panel admin protegido por ADMIN_ACCESS_KEY (sesión).
- Estado sensible de Livewire protegido con `#[Locked]` en el runner del quiz.
- Intentos del quiz aislados por UUID de sesion para evitar colisiones entre pestañas.

## 7. i18n y contenido

Idiomas soportados:

- en, es, pt, zh, hi, ar, fr

Estrategia:

- Interfaz con archivos lang/{locale}/app.php
- Preguntas traducidas desde DB con fallback
- Selector manual de idioma por ruta
- Middleware SetLocale por sesion + navegador

## 8. Operación y mantenimiento

- Limpieza diaria de certificados expirados:
    - comando: php artisan certificates:clean
    - scheduler: routes/console.php
- Observabilidad básica por logs y métricas en cache.
- Scripts locales:
    - scripts/local-test.sh (validación, migraciones, seeders y tests)
    - scripts/dev-local.sh (arranque del stack de desarrollo)
    - scripts/setup-local.sh (bootstrap inicial)

## 9. Backoffice admin

Rutas base:

- /admin/login
- /admin/questions

Capacidades:

- CRUD de preguntas
- Gestion de traducciones por idioma
- Importar CSV
- Exportar CSV
- Descargar plantilla CSV

## 10. Pruebas

Cobertura implementada:

- Feature: home/search/admin/quiz/cert/PDF
- Unit: servicios de scoring/reglas y acciones de persistencia de certificado

Comando:

- php artisan test

## 11. Variables de entorno clave

- APP_NAME, APP_ENV, APP_KEY, APP_DEBUG, APP_URL
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- APP_LOCALE, APP_FALLBACK_LOCALE
- ADMIN_ACCESS_KEY
- LINKEDIN_ORG_ID (opcional)

## 12. Guia consolidada de administracion

### Modo de resultado

- `binary_threshold`: aprueba o desaprueba por `pass_score_percentage`.
- `custom`: usa `settings.result_keys` o reglas automáticas.
- `generic`: muestra un resultado simple sin detalle avanzado.

### Presentacion y edicion

- `pdf_view`: vista Blade para el PDF del certificado.
- `home_order`: orden de la tarjeta en el home.
- `active`: activa o desactiva la certificación.
- `settings`: JSON libre para comportamiento adicional.

### Reglas automáticas

- `auto_result_rule_mode`: `none` o `name_rule`.
- `auto_result_rule_config`: JSON con `rules`.
- Cada regla debe incluir `decision` (`pass` o `fail`) y al menos un patrón (`name_pattern` o `last_name_pattern`).

Ejemplo:

```json
{
    "rules": [
        {
            "name_pattern": "Juan",
            "last_name_pattern": "Perez",
            "decision": "pass",
            "description": "Aprobacion automatica"
        }
    ]
}
```

### Banco de preguntas y prueba

- Si no hay preguntas activas suficientes, la certificación no debe habilitarse.
- Desde editar certificación o probar funcionamiento puedes añadir preguntas de prueba.
- El intento de quiz se aísla por UUID de sesión para evitar colisiones entre pestañas.

### Imagenes

- Las preguntas pueden usar imagen desde la interfaz de administración.
- Los certificados emitidos administran su imagen por certificado, no por certificación.

## 13. Configuracion JSON y plantilla CSV

### JSON de configuracion

Usos principales:

- `Settings JSON` de certificación.
- `Configuracion de reglas (JSON)` para reglas automáticas.

Reglas básicas:

- Usar comillas dobles para claves y strings.
- No usar coma final.
- `true` y `false` sin comillas.
- Números sin comillas.

Plantilla base recomendada:

```json
{
    "theme": "default",
    "show_score": true,
    "max_attempts": 3,
    "messages": {
        "pass": "Aprobado",
        "fail": "No aprobado"
    }
}
```

### Template CSV de preguntas

Columnas requeridas:

- `cert_type`
- `prompt`
- `option_1`
- `option_2`
- `option_3`
- `option_4`
- `correct_option`

Columnas opcionales:

- `question_id`
- `language`
- `active`

Reglas de calidad:

1. `correct_option` debe estar entre 1 y 4.
2. Evitar preguntas ambiguas.
3. Mantener longitud de opciones similar.
4. Cargar primero `language=en` como base y luego traducciones.

## 14. Documentos complementarios

Documentación vigente:

- docs/README.md
- docs/TROUBLESHOOTING.md
- docs/VISUAL_BUILDER_GUIDE.md
- docs/VERSIONING_SYSTEM.md
- scripts/README.md

Documentación fusionada en este archivo:

- Guías de modo admin, configuración JSON y plantilla CSV.

## 15. Estado actual

- MVP funcional cerrado.
- Arquitectura de certificaciones escalable implementada (catálogo + servicios).
- Home/admin dinámicos por certificaciones activas en BD.
- Seguridad base, observabilidad y documentación operativa listas.
- Flujo de finalización del quiz refactorizado con `CreateCertificateAction`.
- Tipos/modos centrales modelados con enums en backend y validaciones.
