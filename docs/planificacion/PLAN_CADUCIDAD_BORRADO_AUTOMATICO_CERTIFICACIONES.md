# Plan de acción: caducidad, retención y reglas automáticas de certificaciones

## Objetivo

Implementar un sistema para que cada certificación pueda tener una caducidad definida o indefinida, con reglas automáticas para el tratamiento de datos de usuarios, descarga de certificados y aprobación/desaprobación automática basada en nombre y/o apellido.

## Alcance funcional

### 1. Caducidad por certificación
- Permitir definir una fecha de caducidad o un período de validez para cada certificación desde el panel de administrador.
- Permitir también que la certificación sea indefinida.
- Mantener la lógica por defecto compatible con las certificaciones actuales.

### 2. Retención y eliminación de datos de usuarios
- Cuando el tiempo de caducidad de una certificación termine, eliminar los datos de los usuarios que la realizaron, según la política definida.
- Después de la caducidad, los usuarios no podrán descargar su certificación si ya expiró.
- Si la certificación todavía estaba vigente cuando el usuario la realizó, debe conservarse el derecho de descarga mientras la certificación siga dentro de su período de validez.
- Si la certificación se elimina manualmente, dejará de poder realizarse para nuevos usuarios.
- Si al momento de borrarla todavía está dentro de su período de validez, los usuarios que ya la completaron deben conservar la capacidad de descargar su certificado mientras aplique la retención definida.

### 3. Borrado manual de datos adicionales
- Añadir una capacidad administrativa para eliminar manualmente la base de datos o los registros de usuarios asociados, incluso si la certificación todavía no ha expirado.
- Esta acción debe ser explícita y separada del borrado normal de la certificación.
- Debe quedar claro qué se borra y qué se conserva en cada modo.

### 4. Reglas automáticas de aprobado / desaprobado
- Permitir que el resultado del examen se determine automáticamente usando nombre y/o apellido.
- Definir reglas configurables para aprobar o desaprobar según presencia, formato o coincidencia de nombre/apellido.
- Mantener compatibilidad con el flujo actual de examen y resultados.

### 5. Tipos de preguntas con 2 o 4 opciones
- Permitir preguntas de 2 opciones además de las actuales de 4 opciones.
- Mantener compatibilidad con preguntas existentes.
- Validar que la opción correcta corresponda al rango válido según el tipo de pregunta.

### 6. Peso por pregunta y cálculo de nota
- Incorporar peso configurable por pregunta.
- Por defecto, todas las preguntas tienen peso uniforme.
- Permitir escenarios extremos de peso (por ejemplo, una pregunta con peso dominante y el resto con peso mínimo).
- Recalcular automáticamente el peso relativo de todas las preguntas para que el total sea consistente.
- Asegurar que el cálculo de aprobado/desaprobado use ponderación real y no conteo plano.

### 7. Preguntas de muerte súbita
- Permitir marcar preguntas como muerte súbita.
- Definir dos comportamientos configurables:
- fallo automático si la responde mal.
- aprobación automática si la responde bien.
- La pregunta de muerte súbita debe verse como una pregunta normal para el usuario final; no se debe revelar en UI que tiene esta condición.
- Registrar en el resultado final el motivo por el que se activó la regla.

### 8. Banco de preguntas por idioma y validación previa
- Soportar base de preguntas por idioma para cada certificación.
- Antes de iniciar un examen, validar que existe banco de preguntas para el idioma activo de la página.
- Si no existe, mostrar mensaje previo indicando idiomas disponibles y bloquear inicio del examen.
- Si hay más de dos idiomas disponibles pero ninguno coincide con el idioma del navegador/locale activo, mostrar selección manual para que el usuario elija en qué idioma presentar el examen.
- Permitir agregar bancos de preguntas en nuevos idiomas en cualquier momento.

### 9. Regla de activación de certificación
- Para que una certificación esté activa, debe tener al menos un banco de preguntas válido.
- Si no cumple el requisito, impedir activación o forzar estado inactivo.
- Mantener posibilidad de cargar preguntas posteriormente y activar cuando cumpla la condición.

### 10. Randomización configurable
- Permitir activar o desactivar randomización de:
- orden de preguntas.
- orden de opciones de respuesta.
- Permitir combinaciones independientes:
- randomizar solo preguntas.
- randomizar solo opciones.
- randomizar ambas.
- no randomizar ninguna.

## Propuesta técnica

### Modelo de datos
- Agregar campos en certificaciones para:
- tipo de caducidad: `indefinida` o `definida`
- duración o fecha límite
- política de eliminación de datos asociada
- política de conservación de descargas
- reglas automáticas de resultado
- posible modo manual de borrado adicional
- configuración de randomización (preguntas/opciones)
- regla de activación mínima por banco de idioma
- Agregar campos en preguntas para:
- tipo de pregunta (2 o 4 opciones)
- peso de la pregunta
- indicador y modo de muerte súbita
- Agregar soporte explícito de banco por idioma en la estructura de preguntas/traducciones para selección de examen por locale.

### Administración
- Añadir controles en el formulario de edición de certificaciones.
- Incluir validación en el request y normalización en el controlador.
- Mostrar claramente el impacto de cada configuración en la UI del administrador.
- Añadir controles para configurar:
- caducidad definida/indefinida.
- randomización de preguntas y opciones.
- regla de activación por existencia de banco de idioma.
- reglas automáticas de aprobado/desaprobado por nombre/apellido.
- Añadir controles por pregunta para:
- tipo 2 o 4 opciones.
- peso.
- muerte súbita y su modo.

### Proceso de expiración
- Evaluar si la caducidad se aplica por:
- fecha fija
- número de días desde la creación
- número de días desde la aprobación del usuario
- Implementar una tarea programada para limpiar datos vencidos.
- Evitar borrar certificados descargables antes de tiempo.

### Proceso de borrado manual
- Separar el borrado de certificación del borrado de datos de usuarios.
- Reutilizar cascadas donde ya existan.
- Eliminar explícitamente:
- resultados asociados
- certificados generados
- imágenes de certificados
- logs o trazas de usuario que deban desaparecer según la política

### Reglas automáticas de resultado
- Definir una capa de validación o servicio que evalúe nombre y apellido.
- Permitir reglas como:
- contiene nombre
- contiene apellido
- nombre obligatorio
- apellido obligatorio
- nombre y apellido obligatorios
- nombre o apellido obligatorio
- Registrar el motivo de aprobación o desaprobación.

### Motor de scoring y decisiones especiales
- Implementar scoring ponderado por pregunta.
- Calcular porcentaje final con base en suma de pesos correctos sobre suma total de pesos.
- Resolver reglas en este orden recomendado:
- muerte súbita de aprobación automática.
- muerte súbita de desaprobación automática.
- reglas automáticas por nombre/apellido.
- scoring ponderado normal.
- Registrar trazabilidad del resultado final y de la regla aplicada.

### Disponibilidad por idioma
- Detectar locale activo de la página y seleccionar banco de preguntas correspondiente.
- Si no existe banco en ese idioma, bloquear inicio y mostrar idiomas disponibles.
- Si no hay coincidencia con idioma del navegador y existen múltiples bancos (más de dos), habilitar selector de idioma antes de iniciar el examen.
- Añadir endpoint/servicio para consultar disponibilidad por idioma antes de iniciar examen.

## Riesgos y decisiones pendientes

- Falta definir con precisión si la caducidad se calcula desde la creación de la certificación, desde la fecha de aprobación del usuario o desde otra referencia.
- Hay que aclarar qué significa exactamente "eliminar la base de datos de los usuarios registrados manualmente" para no confundirlo con un borrado total de la certificación.
- Hay que definir si la descarga de certificados debe sobrevivir tras una eliminación administrativa cuando la certificación aún estaba vigente.
- Las reglas automáticas de aprobado/desaprobado necesitan criterios exactos para evitar falsos positivos.
- Hay que definir límites de peso mínimo/máximo por pregunta para evitar configuraciones inválidas o abusivas.
- Hay que definir precedencia exacta entre muerte súbita y reglas por nombre/apellido cuando ambas aplican.
- Hay que decidir si una certificación activa con banco en un idioma debe poder rendirse en otro idioma sin banco.

## Plan de implementación por fases

### Fase 1: diseño de datos y UI
- Añadir campos de caducidad y retención en el modelo de certificación.
- Exponerlos en el panel de administrador.
- Validar la configuración en requests y controlador.
- Incorporar configuración de randomización.
- Incorporar configuración de activación condicionada por banco de idioma.
- Preparar campos de tipo/peso/muerte súbita en preguntas.

### Fase 2: motor de caducidad y conservación
- Implementar el cálculo de expiración.
- Definir cuándo se bloquea la descarga del certificado.
- Crear la limpieza automática de datos vencidos.

### Fase 3: borrado manual y limpieza profunda
- Añadir una acción administrativa para borrar datos de usuarios manualmente.
- Integrar eliminación de imágenes, logs y relaciones asociadas.
- Mantener la certificación inaccesible cuando corresponda.

### Fase 4: reglas automáticas de resultado
- Crear el servicio de evaluación de nombre y apellido.
- Conectar la regla al flujo de examen y resultado.
- Registrar y probar los estados aprobado / desaprobado.

### Fase 5: scoring ponderado, muerte súbita y locale
- Implementar scoring por pesos.
- Implementar reglas de muerte súbita y precedencia.
- Implementar selección de banco por idioma y bloqueo preventivo si no hay preguntas para locale activo.
- Integrar randomización configurable de preguntas y opciones.

### Fase 6: pruebas automáticas
- Agregar pruebas de modelo, controlador y jobs/tareas.
- Cubrir:
- certificación con caducidad definida
- certificación indefinida
- descarga permitida y bloqueada por expiración
- borrado manual con conservación o eliminación de datos
- reglas automáticas por nombre y apellido
- preguntas de 2 y 4 opciones
- cálculo de score ponderado con distribución desigual de pesos
- muerte súbita por acierto y por error
- bloqueo por idioma sin banco y mensaje de idiomas disponibles
- selector manual de idioma cuando el navegador no coincide y hay más de dos idiomas disponibles
- activación permitida solo cuando existe al menos un banco válido
- randomización activada/desactivada para preguntas y opciones

### Fase 7: cierre técnico y actualización transversal
- Actualizar documentación funcional y técnica después de implementar.
- Actualizar y ejecutar tests impactados por los cambios.
- Actualizar scripts administrativos/operativos que dependan del modelo de certificación o preguntas.

## Criterios de aceptación

- El administrador puede definir caducidad definida o indefinida por certificación.
- Al expirar la certificación, se aplican las reglas de eliminación de datos configuradas.
- Los usuarios no pueden descargar certificados fuera de la vigencia permitida.
- Si se borra una certificación, deja de poder rendirse.
- Si corresponde por vigencia, quienes ya aprobaron conservan descarga mientras la política lo permita.
- Existe una forma manual de eliminar datos de usuarios aunque la certificación siga vigente.
- Las reglas automáticas de aprobado/desaprobado por nombre y/o apellido funcionan y están cubiertas por tests.
- El sistema permite preguntas de 2 o 4 opciones y valida correctamente sus respuestas.
- El cálculo de aprobación utiliza pesos por pregunta y soporta casos de ponderación extrema.
- Las preguntas de muerte súbita aplican aprobación/desaprobación automática según configuración.
- El usuario final no puede identificar en interfaz qué pregunta es de muerte súbita.
- Si no hay preguntas en el idioma activo, el examen no inicia y se informan idiomas disponibles.
- Si no coincide el idioma del navegador y existen más de dos idiomas disponibles, el usuario puede elegir idioma antes de iniciar.
- Una certificación solo puede activarse si tiene al menos un banco de preguntas válido.
- La randomización de preguntas y opciones puede configurarse de forma independiente.
- Al finalizar implementación se actualizan documentación, tests y scripts relacionados.

## Próximo paso

Revisar y cerrar las decisiones pendientes antes de empezar a implementar migraciones, servicios y pruebas.
