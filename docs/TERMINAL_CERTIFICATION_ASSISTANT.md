# Asistente de Certificaciones por Terminal

Script: `scripts/create-certification.sh`

## 1. Modos disponibles

- Interactivo: ejecutar sin argumentos.
- No interactivo: ejecutar con argumentos.

## 2. Orden recomendado de argumentos

1. `--slug`
2. `--name`
3. `--description` (opcional)
4. `--questions-required`
5. `--pass-score`
6. `--cooldown-days`
7. `--result-mode`
8. `--pdf-view` (opcional)
9. `--home-order` (opcional)
10. `--settings-json` (opcional)
11. `--active` o `--inactive`
12. `--with-test-questions` (opcional)
13. `--yes` (opcional, evita confirmacion)

## 3. Ejemplo completo

```bash
./scripts/create-certification.sh \
  --slug marketing-2026 \
  --name "Marketing 2026" \
  --description "Certificacion de marketing" \
  --questions-required 20 \
  --pass-score 70 \
  --cooldown-days 30 \
  --result-mode binary_threshold \
  --pdf-view pdf.certificate \
  --home-order 120 \
  --settings-json '{"theme":"default","show_score":true}' \
  --active \
  --with-test-questions 5 \
  --yes
```

## 4. Validaciones

- `slug`: 3-60, minusculas, numeros, guion o guion bajo.
- `questions-required`: 1-255.
- `pass-score`: 0-100.
- `cooldown-days`: 0-365.
- `result-mode`: `binary_threshold`, `custom`, `generic`.
- `with-test-questions`: 0-20.

## 5. Notas de consistencia

- Los valores de `result-mode` coinciden con `ResultMode` en backend.
- Si usas `result-mode custom`, define `result_keys.pass` y `result_keys.fail` dentro de `--settings-json`.
- El script crea configuracion de certificacion; el runtime del quiz aplica ademas scoring ponderado, auto-reglas y muerte subita si estan configuradas.

Ejemplo de `settings-json` para modo custom:

```json
{
    "result_keys": {
        "pass": "custom_pass_key",
        "fail": "custom_fail_key"
    }
}
```
