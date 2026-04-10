# Guia de Configuracion JSON

## 1. Donde se usa
- Campo `Settings JSON` de certificacion.
- Campo `Configuracion de reglas (JSON)` para reglas automaticas.

## 2. Reglas basicas de JSON
- Usar comillas dobles para claves y strings.
- No usar coma final.
- `true`/`false` sin comillas.
- Numeros sin comillas.

## 3. Plantilla base recomendada
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

## 4. Reglas automaticas por nombre
```json
{
  "rules": [
    {
      "name_pattern": "Juan",
      "last_name_pattern": "Perez",
      "decision": "pass",
      "description": "Caso permitido"
    },
    {
      "name_pattern": "Test",
      "last_name_pattern": ".*",
      "decision": "fail",
      "description": "Bloqueo de pruebas"
    }
  ]
}
```

## 5. Checklist rapido
1. Validar JSON en un validador.
2. Guardar certificacion.
3. Ir a Probar funcionamiento.
4. Revisar advertencias de diagnostico.
