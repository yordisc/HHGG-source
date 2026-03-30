# Documentacion detallada del proyecto CertificacionHHGG

Ultima actualizacion: 2026-03-30

## 1. Resumen funcional

CertificacionHHGG es una aplicacion Laravel 11 + Livewire que permite:

- Registrar candidato para un quiz humoristico.
- Ejecutar quiz de 30 preguntas aleatorias por tipo de certificado.
- Calcular resultado por umbral de aciertos/errores.
- Generar certificado con serial unico.
- Publicar vista verificable por serial.
- Descargar certificado en PDF.
- Compartir resultado en LinkedIn.
- Buscar certificados por serial o documento (hash de consulta).

## 2. Flujo end-to-end

1. Home: seleccion de certificado o busqueda de serial/documento.
2. Registro: datos del candidato por tipo de certificacion.
3. Quiz: preguntas random + traduccion por idioma.
4. Resultado: estado final + score + accesos rapidos.
5. Certificado publico: validacion y datos publicos.
6. PDF: descarga del certificado en formato formal.

## 3. Stack tecnico

- Backend: Laravel 11 (PHP 8.2+)
- UI interactiva: Livewire 4
- Frontend build: Vite + Tailwind (npm)
- PDF: barryvdh/laravel-dompdf
- Persistencia: MySQL/MariaDB o SQLite segun entorno
- Deploy objetivo: Railway/Nixpacks

## 4. Estructura principal

- app/Http/Controllers: Home, Quiz, Certificate, Admin.
- app/Livewire: motor QuizRunner.
- app/Http/Middleware: locale, rate limit, headers seguros, auth admin.
- app/Models: Certificate, Question, QuestionTranslation, RateLimit.
- database/migrations: schema operativo del MVP.
- database/seeders: preguntas base + traducciones iniciales.
- resources/views: flujo publico + admin + PDF.
- lang/*/app.php: interfaz multilenguaje.
- routes/web.php: rutas publicas y admin.
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
- doc_lookup_hash (HMAC) para busqueda.
- Middleware de headers seguros en grupo web.
- Rate limit por IP/documento y reglas de renovacion.
- Panel admin protegido por ADMIN_ACCESS_KEY (sesion).

## 7. i18n y contenido

Idiomas soportados:
- en, es, pt, zh, hi, ar, fr

Estrategia:
- Interfaz con archivos lang/{locale}/app.php
- Preguntas traducidas desde DB con fallback
- Selector manual de idioma por ruta
- Middleware SetLocale por sesion + navegador

## 8. Operacion y mantenimiento

- Limpieza diaria de certificados expirados:
  - comando: php artisan certificates:clean
  - scheduler: routes/console.php
- Observabilidad basica por logs y metricas en cache.
- Scripts locales:
  - scripts/setup-local.sh (Codespaces/Linux Mint)

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

Documentacion de planificacion movida a:
- docs/planificacion/ARRANQUE_MVP.md
- docs/planificacion/IDEA.md
- docs/planificacion/plan_certificados_laravel.md
- docs/planificacion/TAREAS_ESTADO.md
- docs/planificacion/POLITICA_RETENCION_DATOS.md
- docs/planificacion/DESPLIEGUE_STAGING_PRODUCCION.md
- docs/planificacion/DISCLAIMERS_I18N.md
- docs/planificacion/INTEGRACION_DISCLAIMER_BLADE.md
- docs/planificacion/SIGUIENTE_PASO_TECNICO.md

## 13. Estado actual

- MVP funcional cerrado.
- UX formalizada visualmente.
- Microcopy formal multilenguaje.
- Seguridad base, observabilidad y docs operativas listas.

