# Documentación detallada del proyecto CertificacionHHGG

Última actualización: 2026-04-02

## 1. Resumen funcional

CertificacionHHGG es una aplicación Laravel 11 + Livewire que permite:

- Registrar candidato para un quiz humorístico.
- Ejecutar quiz de 30 preguntas aleatorias por tipo de certificado, con opciones remezcladas en cada intento.
- Calcular resultado por umbral de errores y mostrarlo al final en el certificado.
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

- serial, cert_type, result_key
- first_name, last_name, country
- document_hash, doc_lookup_hash, doc_partial
- score_correct, score_incorrect, total_questions
- issued_at, expires_at, last_attempt_at

### questions

- cert_type, prompt
- option_1..option_4, correct_option
- active

### question_translations

- question_id, language
- prompt, option_1..option_4

### rate_limits

- identifier_hash, scope, attempted_at

## 6. Seguridad y privacidad

- Documento legal no se guarda en texto plano.
- document_hash con bcrypt.
- doc_lookup_hash (HMAC) para búsqueda.
- Middleware de headers seguros en grupo web.
- Rate limit por IP/documento y reglas de renovacion.
- Panel admin protegido por ADMIN_ACCESS_KEY (sesión).

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
- Unit: comportamiento del modelo Certificate

Comando:

- php artisan test

## 11. Variables de entorno clave

- APP_NAME, APP_ENV, APP_KEY, APP_DEBUG, APP_URL
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- APP_LOCALE, APP_FALLBACK_LOCALE
- ADMIN_ACCESS_KEY
- LINKEDIN_ORG_ID (opcional)

## 12. Documentos complementarios

Documentación vigente:

- docs/README.md
- docs/TROUBLESHOOTING.md
- docs/VISUAL_BUILDER_GUIDE.md
- docs/VERSIONING_SYSTEM.md
- scripts/README.md

## 13. Estado actual

- MVP funcional cerrado.
- Arquitectura de certificaciones escalable implementada (catálogo + servicios).
- Home/admin dinámicos por certificaciones activas en BD.
- Seguridad base, observabilidad y documentación operativa listas.
