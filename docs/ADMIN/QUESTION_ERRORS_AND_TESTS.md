# Error de la Vista de Preguntas y Cobertura de Tests

## Resumen

El error capturado en `docs/error.html` corresponde a la ruta `/admin/questions` y se manifiesta como `Array to string conversion` al renderizar la vista de preguntas.

## Error principal

- Archivo afectado: `resources/views/admin/questions/index.blade.php`
- Línea problemática original: la paginación usaba `{{ $questions->render() }}`.
- Síntoma: al entrar al listado de preguntas, Laravel intenta convertir un valor en arreglo a texto durante el renderizado de la paginación.
- Causa probable: la vista estaba imprimiendo la paginación con una API que no preserva bien los parámetros de consulta cuando llegan filtros como arreglos.

## Corrección aplicada

- Cambiar el render de paginación a `links()`.
- Preservar los filtros activos con `appends(request()->except('page'))`.

## Impacto en tests

### Test que ya cubre el problema

- `tests/Feature/AdminQuestionsTest.php`
- Caso relevante: `test_admin_questions_index_handles_array_query_filters_without_crashing()`
- Peso en tests: alto, porque reproduce exactamente la ruta que rompía el render del admin.

### Qué valida este test

- Que `/admin/questions` responde con `200 OK`.
- Que la vista no se rompe con filtros en formato arreglo, por ejemplo `cert_type[]=hetero` o `active[]=1`.
- Que el listado sigue mostrando contenido correcto.

### Si hay que modificar algún test

- En principio no hace falta agregar un test nuevo para el bug base, porque ya existe cobertura funcional.
- Opcionalmente, se puede reforzar el caso actual para comprobar que la paginación conserva los filtros en la URL después del cambio a `links()`.

## Estado actual

- Error principal: corregido.
- Riesgo residual: bajo, salvo que se agreguen nuevos filtros en formato arreglo en la vista sin pasarlos por `appends()`.
