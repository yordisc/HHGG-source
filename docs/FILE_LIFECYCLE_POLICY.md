# Politica de Ciclo de Vida de Archivos

Fecha: 2026-04-14
Objetivo: reducir archivos huerfanos, evitar enlaces rotos y mantener el repo limpio.

## 1) Criterios para crear archivos

- Crear un archivo nuevo solo si no existe uno vigente donde extender la informacion.
- Todo archivo nuevo debe quedar referenciado desde un indice:
    - `README.md` (nivel proyecto), o
    - `docs/README.md` (nivel documentacion), o
    - `scripts/README.md` (nivel scripts).

## 2) Criterios para marcar como deprecado

- Si un archivo ya no es fuente oficial, marcarlo como "Deprecado" al inicio.
- Agregar referencia al archivo reemplazo (ruta exacta).
- Definir fecha de retiro recomendada.

## 3) Criterios para archivar

- Si se necesita conservar historial, mover a `docs/archive/`.
- Mantener una sola copia (evitar duplicados activos + archive).
- Registrar el movimiento en `docs/README.md`.

## 4) Criterios para eliminar

- Eliminar cuando se cumpla todo:
    1. No tiene uso en runtime/build/tests.
    2. No es referencia oficial en README/docs/scripts.
    3. Existe reemplazo o ya no aporta valor operativo.
- Antes de eliminar, buscar referencias globales y limpiar enlaces rotos.

## 5) Checklist de limpieza segura

1. Buscar referencias globales por nombre de archivo.
2. Actualizar README/docs/scripts para evitar enlaces rotos.
3. Confirmar que el archivo no se ejecuta por CI/comandos npm/artisan.
4. Eliminar archivo.
5. Verificar nuevamente referencias y errores basicos de docs.

## 6) Responsabilidad operativa

- Cambios de ciclo de vida se hacen en PR con titulo claro, por ejemplo:
    - `docs: deprecate obsolete legal analysis`
    - `chore: remove orphan scripts and dead docs`
- Incluir siempre resumen de archivos removidos/movidos.
