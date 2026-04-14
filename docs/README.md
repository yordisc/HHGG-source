# Documentacion del Sistema de Certificaciones

## Estado actual

- Suite de pruebas en verde (`php artisan test`).
- Implementaciones de expiracion, retencion, reglas automaticas y scoring ponderado activas.
- Flujo de quiz y panel admin validados por pruebas unitarias y feature.
- Estado del quiz endurecido para produccion:
    - Creacion de certificado centralizada en `CreateCertificateAction`.
    - Intentos de quiz aislados por UUID de sesion (`quiz_attempt_<uuid>`).
    - Propiedades sensibles de Livewire protegidas con `#[Locked]`.
    - Manejo amigable cuando el banco de preguntas es insuficiente.
- Cadenas magicas reducidas con enums en runtime y validaciones:
    - `QuestionType`, `ResultMode`, `SuddenDeathMode`, `AutoResultRuleMode`.
- Guia de despliegue y operacion actualizada para entorno productivo y local:
    - Nginx + PHP-FPM en produccion.
    - Proxies confiables para Render u otros reverse proxies.
    - Scheduler externo via webhook protegido.
    - Flujo local conservado con `scripts/local-test.sh` y `scripts/dev-local.sh`.

## Avance de arquitectura

- Fase 1 completada: blueprint de Render simplificado a un solo servicio web y `QUEUE_CONNECTION=sync`.
- Fase 1.1 completada: estado efimero movido a Redis (`SESSION_DRIVER=redis`, `CACHE_STORE=redis`) para no saturar la base externa.
- Fase 1.2 completada: migraciones retiradas del arranque del contenedor y movidas a ejecucion independiente.
- Fase 2 completada: scheduler mantiene trigger HTTP y tareas pesadas encoladas.
- Fase 3 completada: acceso admin migrado a autenticacion por usuario con rol `is_admin`.
- Fase 4 en progreso: baseline de latencia y memoria con `scripts/profile-serving.sh`.

Decision de serving:

- Mantener stack Nginx + PHP-FPM mientras la metrica P95 y la RAM sean estables.
- Evaluar migracion de serving solo con evidencia de presion sostenida de recursos.

## Registro de cambios

### 2026-04-14

- Fase 1 completada: despliegue free de Render sin worker dedicado y cola en modo `sync`.
- Estado de sesion/cache en produccion ajustado a Redis (Upstash) para reducir presion en Aiven/Neon.
- Arranque del contenedor endurecido: se elimina `php artisan migrate --force` del startup script.
- Dockerfile actualizado con perfil de OPCache de produccion (`opcache.validate_timestamps=0`, `opcache.memory_consumption=256`).
- Fase 2 completada: comandos de limpieza/purga migrados a ejecucion asíncrona por Jobs.
- Fase 3 completada: autenticacion admin migrada a usuario con rol `is_admin` y pruebas adaptadas.
- Fase 4 iniciada: baseline de latencia/memoria con `scripts/profile-serving.sh`.
- Documentacion consolidada en `docs/README.md` y `docs/DEPLOY_RENDER_NEON_AIVEN.md`.

## Guia rapida

| Necesitas                             | Documento                                                    |
| ------------------------------------- | ------------------------------------------------------------ |
| Arquitectura y alcance funcional      | [PROYECTO_DETALLADO.md](./PROYECTO_DETALLADO.md)             |
| Uso del editor visual                 | [VISUAL_BUILDER_GUIDE.md](./VISUAL_BUILDER_GUIDE.md)         |
| Versionado de certificaciones         | [VERSIONING_SYSTEM.md](./VERSIONING_SYSTEM.md)               |
| Diagnostico de problemas              | [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)                   |
| Despliegue en Render + Neon/Aiven     | [DEPLOY_RENDER_NEON_AIVEN.md](./DEPLOY_RENDER_NEON_AIVEN.md) |
| Auditoria y hardening de seeders      | [SEEDER_AUDIT_FINDINGS.md](./SEEDER_AUDIT_FINDINGS.md)       |
| Politica de ciclo de vida de archivos | [FILE_LIFECYCLE_POLICY.md](./FILE_LIFECYCLE_POLICY.md)       |

## Pruebas

Comandos recomendados:

```bash
php artisan test
php artisan test --filter=SeederRegressionTest
php artisan test tests/Unit
php artisan test tests/Feature
```

Nota: en PHPUnit 11 ya no existe `--no-header`.

## Convenciones de mantenimiento

- Mantener esta carpeta solo con documentacion vigente.
- Evitar reportes historicos por fase en la rama principal.
- Preferir documentos operativos y de referencia tecnica actual.
- Cuando se implemente una mejora tecnica relevante, reflejarla aqui y en `PROYECTO_DETALLADO.md`.

Nota sobre plantilla CSV: la referencia vigente para carga base de preguntas es `database/templates/questions-example.csv`.
