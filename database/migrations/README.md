# Convencion y Organizacion de Migrations

## Objetivo

Mantener migrations trazables, declarativas y seguras para rollback.

## Convencion de nombres

Formato recomendado:

- `YYYY_MM_DD_HHMMSS_<accion>_<objeto>_<detalle>.php`

Ejemplos:

- `2026_04_14_000100_change_certification_foreign_keys_to_restrict_on_delete.php`
- `2026_04_14_000200_convert_float_metrics_columns_to_decimal.php`

## Orden por dominio (referencia)

1. Base framework: users, cache, jobs.
2. Core quiz: certificates, questions, translations, rate_limits.
3. Evolucion de modelo: certifications, relaciones `certification_id`, remocion de `cert_type`.
4. Admin/operacion: drafts, templates, logs, versions, statistics.
5. Hardening: integridad referencial, normalizacion de tipos, seguridad de rollback.

## Reglas practicas

- No renombrar migrations ya aplicadas en entornos compartidos.
- Preferir nuevas migrations para cambios de esquema antes que editar historicas, salvo hardening puntual justificado.
- Si una migration requiere SQL por motor, documentar comportamiento por `mysql`/`pgsql`/`sqlite`.
- En `down()`, evitar operaciones con riesgo de perdida silenciosa (agregar guard clauses cuando aplique).

## Mapa de hardening actual (2026-04-14)

- FK policy: `certification_id` en `questions` y `certificates` con `restrictOnDelete`.
- Tipos numericos: conversion de columnas metricas `float` a `decimal`.
- Rollback seguro: guard clause para rollback de `certificates.serial` de 50 a 30.
