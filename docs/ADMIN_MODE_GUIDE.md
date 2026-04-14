# Guia de Modo Admin

## 1. Flujo recomendado

1. Crear certificacion desde el asistente: Admin -> Certificaciones -> Nueva certificacion.
2. Completar 5 pasos del asistente.
3. Abrir Editar certificacion para ajustes finos.
4. Cargar preguntas (manual, builder o CSV).
5. Probar funcionamiento.
6. Activar certificacion.

## 2. Modos de resultado

- `binary_threshold`: aprueba/desaprueba por porcentaje (`pass_score_percentage`).
- `custom`: usa llaves de resultado personalizadas (`settings.result_keys`) o reglas automaticas.
- `generic`: muestra resultado generico sin detalle avanzado de score.

Nota operativa:

- Los valores validos se centralizan en `ResultMode` (enum backend).
- En quiz runtime, el resultado final tambien puede depender de scoring ponderado, auto-reglas y muerte subita.

## 3. Seccion Presentacion

En el asistente, la seccion Presentacion controla:

- `pdf_view`: vista Blade usada para renderizar certificado PDF.
- `home_order`: orden de la tarjeta en home.
- `active`: si la certificacion queda activa al finalizar.
- `settings`: JSON libre para comportamiento adicional.

## 4. Reglas automaticas (name_rule)

Campos:

- `auto_result_rule_mode`: `none` o `name_rule`.
- `auto_result_rule_config`: JSON con reglas.

Recomendacion:

- Mantener `auto_result_rule_mode` en `none` si no hay reglas cargadas.
- Validar que cada regla tenga `decision` (`pass`/`fail`) y al menos un patron (`name_pattern` o `last_name_pattern`).

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

## 5. Banco de preguntas y prueba

- Si no hay preguntas activas suficientes, la prueba completa no se habilita.
- Desde Editar certificacion o Probar funcionamiento puedes agregar 5 preguntas de prueba.
- Si se intenta iniciar quiz sin banco suficiente, el usuario es redirigido al registro con mensaje flash amigable.
- El intento de quiz se aísla por UUID de sesion para evitar colisiones entre pestañas.

## 6. Imagen/foto

- Preguntas: en Crear/Editar pregunta ya puedes cargar imagen y eliminarla en editar.
- Certificados emitidos: la imagen del certificado se administra por certificado (no por certificacion).

## 7. Errores comunes

- "404 Guardar orden": verificar sesion admin activa y recargar pagina de listado.
- JSON invalido: validar sintaxis con comillas dobles y llaves balanceadas.
- Plantilla no guarda: confirmar checkbox "Crear plantilla personalizada" activo.
