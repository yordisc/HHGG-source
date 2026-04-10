# Plan de pasos - Mejoras modo admin (2026-04-10)

## Objetivo
Consolidar correcciones funcionales, UX y documentacion del modo admin.

## Paso 1: Corregir accesos y errores visibles
1. Dejar un solo campo de contraseña en login admin.
2. Corregir guardado de orden en certificaciones y respuesta del backend para AJAX.
3. Ajustar panel "Estado de Preguntas" para evitar deformacion visual.

## Paso 2: Alinear asistente con editor de certificacion
1. Hacer que el asistente recuerde datos entre pasos (draft persistente).
2. Exponer opciones avanzadas en el asistente (caducidad, randomizacion, reglas, activacion).
3. Mejorar explicaciones de "Modo de resultado" y "Presentacion".

## Paso 3: Plantilla y pruebas
1. Asegurar guardado consistente de plantilla personalizada.
2. Mejorar pantalla "Probar funcionamiento" con acciones directas.
3. Permitir agregar 5 preguntas de prueba cuando no hay banco suficiente.

## Paso 4: Imagenes
1. Agregar carga de imagen en crear pregunta.
2. Agregar reemplazo y eliminacion de imagen en editar pregunta.

## Paso 5: Automatizacion por terminal
1. Extender script `create-certification.sh` para modo por argumentos.
2. Permitir crear certificacion y preguntas de prueba desde CLI.
3. Documentar orden y formato de argumentos.

## Paso 6: Documentacion funcional
1. Guia completa de modo admin.
2. Guia de configuraciones JSON.
3. Template para banco de preguntas.
4. Guia del asistente por terminal.

## Criterios de cierre
- Sin errores de validacion en archivos modificados.
- Flujo de asistente y edicion consistente.
- Documentacion disponible y clara para operacion diaria.
