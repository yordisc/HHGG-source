# Documentacion del Sistema de Certificaciones

## Estado actual

- Suite de pruebas en verde (`php artisan test`).
- Implementaciones de expiracion, retencion, reglas automaticas y scoring ponderado activas.
- Flujo de quiz y panel admin validados por pruebas unitarias y feature.

## Guia rapida

| Necesitas | Documento |
|--|--|
| Arquitectura y alcance funcional | [PROYECTO_DETALLADO.md](./PROYECTO_DETALLADO.md) |
| Uso del editor visual | [VISUAL_BUILDER_GUIDE.md](./VISUAL_BUILDER_GUIDE.md) |
| Versionado de certificaciones | [VERSIONING_SYSTEM.md](./VERSIONING_SYSTEM.md) |
| Diagnostico de problemas | [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) |

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