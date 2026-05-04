## Análisis del problema

El test `admin can view template preview` en `AdminCertificateTemplateTest` fallaba en la línea 91 porque la vista previa no reemplazaba de forma consistente los placeholders del template. La respuesta era `200 OK`, pero el HTML renderizado no contenía `Juan Pérez`, `CC 12345678`, `Colombia`, `Competencia Ejemplar` ni `Benjamin Netanyahu`.

## Causa real

La causa no estaba en Faker, en un factory ni en el estado “sucio” del frontend. El problema estaba en [resources/views/admin/certificates/templates/preview.blade.php](../resources/views/admin/certificates/templates/preview.blade.php): la lógica de sustitución solo contemplaba una forma de placeholder y no cubría de manera correcta la variante sin espacios, por ejemplo `{{nombre_completo}}`, que es la que usa el test.

El controlador sí estaba enviando datos de ejemplo correctos desde [app/Http/Controllers/Admin/CertificateTemplateController.php](../app/Http/Controllers/Admin/CertificateTemplateController.php). En particular, `preview()` inyecta valores estáticos y calculados en tiempo de ejecución para poblar la plantilla.

## Comportamiento del frontend

El modal de “cambios sin guardar” en [resources/views/admin/certifications/\_unsaved-warning.blade.php](../resources/views/admin/certifications/_unsaved-warning.blade.php) no era la causa del bug. Ese modal solo intercepta navegación y decide si el usuario continúa editando o abandona la página. La opción de continuar únicamente cierra el modal; no recalcula el preview ni fuerza un nuevo render desde el servidor.

Eso explica la sensación de estado “fantasma”: el usuario permanece en la misma vista con el DOM actual, pero el fallo de fondo sigue siendo de sustitución de strings en el backend.

## Solución aplicada

Se actualizó la sustitución en la vista de preview para usar una expresión regular por clave, tolerante a espacios dentro de los delimitadores:

- Patrón aplicado por clave: `/\{\{\s*KEY\s*\}\}/`

Implementación actual en `preview.blade.php`:

- Construcción del patrón con `preg_quote($key, '/')`
- Reemplazo con `preg_replace($pattern, $value, $html)`

Con ese enfoque, el reemplazo funciona para ambas variantes de placeholder sin depender de dos literales frágiles:

- `{{key}}`
- `{{ key }}`

Con eso, el preview ya sustituye los valores de ejemplo esperados:

- `nombre_completo` → `Juan Pérez`
- `documento_identidad` → `CC 12345678`
- `pais_origen` → `Colombia`
- `nombre_certificacion` → `Competencia Ejemplar`
- `firma_director_nombre` → `Benjamin Netanyahu`

## Verificación

Resultado observado tras la corrección:

- `php artisan test --filter=test_admin_can_view_template_preview` dejó de fallar.
- `php artisan test` terminó en verde (`Exit Code: 0`).

El test de preview se mantiene como contrato de salida: si la sustitución vuelve a romperse para cualquiera de los formatos soportados, el HTML final volverá a exponer el problema de inmediato.

## Prevención de regresiones

- Mantener al menos dos pruebas de preview: una con `{{key}}` y otra con `{{ key }}`.
- Evitar volver a reemplazos literales duplicados; usar una sola estrategia tolerante a espacios (`preg_replace` con patrón por clave).
- Si se modifica el bloque de sustitución en la vista de preview, ejecutar como mínimo:
    - `php artisan test --filter=test_admin_can_view_template_preview`
    - `php artisan test --filter=test_admin_can_view_template_preview_with_spaced_placeholders`
