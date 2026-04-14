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

## Guia rapida

| Necesitas                         | Documento                                                    |
| --------------------------------- | ------------------------------------------------------------ |
| Arquitectura y alcance funcional  | [PROYECTO_DETALLADO.md](./PROYECTO_DETALLADO.md)             |
| Uso del editor visual             | [VISUAL_BUILDER_GUIDE.md](./VISUAL_BUILDER_GUIDE.md)         |
| Versionado de certificaciones     | [VERSIONING_SYSTEM.md](./VERSIONING_SYSTEM.md)               |
| Diagnostico de problemas          | [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)                   |
| Despliegue en Render + Neon/Aiven | [DEPLOY_RENDER_NEON_AIVEN.md](./DEPLOY_RENDER_NEON_AIVEN.md) |

## Pruebas

Comandos recomendados:

```bash
php artisan test
php artisan test tests/Unit
php artisan test tests/Feature
```

Nota: en PHPUnit 11 ya no existe `--no-header`.

## Convenciones de mantenimiento

- Mantener esta carpeta solo con documentacion vigente.
- Evitar reportes historicos por fase en la rama principal.
- Preferir documentos operativos y de referencia tecnica actual.
- Cuando se implemente una mejora tecnica relevante, reflejarla aqui y en `PROYECTO_DETALLADO.md`.
