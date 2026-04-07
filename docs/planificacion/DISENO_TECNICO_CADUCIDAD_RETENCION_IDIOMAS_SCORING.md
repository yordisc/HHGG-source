# Diseño técnico: caducidad, retención, idiomas, ponderación y muerte súbita

## 1. Objetivo técnico

Implementar una arquitectura que permita:
- Caducidad definida o indefinida por certificación.
- Política de retención y eliminación de datos de usuarios por certificación.
- Conservación de descarga de certificados según reglas de vigencia.
- Activación condicionada a existencia de banco de preguntas.
- Banco de preguntas por idioma y validación previa al inicio del examen.
- Preguntas de 2 o 4 opciones.
- Scoring ponderado por pregunta.
- Reglas de muerte súbita.
- Reglas automáticas de aprobado/desaprobado por nombre y/o apellido.
- Randomización configurable de preguntas y opciones.

## 2. Cambios de datos (migraciones)

### 2.1 Tabla certifications

Agregar columnas:
- `expiry_mode` string: `indefinite` o `fixed`.
- `expiry_days` unsignedInteger nullable: días de vigencia cuando aplique.
- `allow_certificate_download_after_deactivation` boolean default true.
- `manual_user_data_purge_enabled` boolean default true.
- `require_question_bank_for_activation` boolean default true.
- `shuffle_questions` boolean default true.
- `shuffle_options` boolean default true.
- `auto_result_rule_mode` string default `none`.
- `auto_result_rule_config` json nullable.

Reglas:
- Si `expiry_mode = indefinite`, `expiry_days` debe ser null.
- Si `expiry_mode = fixed`, `expiry_days` debe ser mayor a 0.

### 2.2 Tabla questions

Agregar columnas:
- `type` ya existe y se normaliza a valores permitidos: `mcq_2`, `mcq_4`.
- `weight` decimal(7,4) default 1.0000.
- `sudden_death_mode` string default `none`.

Valores válidos de `sudden_death_mode`:
- `none`
- `fail_if_wrong`
- `pass_if_correct`

Restricciones de consistencia:
- Si `type = mcq_2`, `correct_option` solo puede ser 1 o 2.
- Si `type = mcq_4`, `correct_option` puede ser 1, 2, 3 o 4.

### 2.3 Tabla question_translations

Ajustar columnas para soportar `mcq_2`:
- `option_3` nullable.
- `option_4` nullable.

### 2.4 Tabla certificates

Agregar columnas:
- `certification_expires_at` datetime nullable.
- `download_expires_at` datetime nullable.
- `result_decision_source` string default `scoring`.
- `result_decision_reason` text nullable.

## 3. Servicios y componentes

### 3.1 Nuevo servicio: CertificationExpirationService

Responsabilidades:
- Calcular vigencia de certificación para intento y descarga.
- Resolver `certification_expires_at` y `download_expires_at` al generar certificado.
- Determinar si una certificación desactivada permite descarga histórica.

### 3.2 Nuevo servicio: CertificationDataRetentionService

Responsabilidades:
- Ejecutar purga automática al vencer vigencia.
- Ejecutar purga manual aunque la vigencia no haya vencido.
- Purga de certificados, intentos, imágenes y trazas según política.

### 3.3 Nuevo servicio: QuestionBankAvailabilityService

Responsabilidades:
- Verificar disponibilidad de banco para `locale` activo.
- Resolver idiomas disponibles por certificación.
- Entregar motivo de bloqueo cuando no hay banco en el idioma actual.
- Resolver si corresponde selector manual cuando no hay coincidencia con idioma del navegador y existen más de dos idiomas disponibles.

### 3.4 Nuevo servicio: WeightedScoringService

Responsabilidades:
- Calcular score ponderado: suma de pesos correctos / suma de pesos totales.
- Normalizar pesos para visualización y validación.
- Soportar pesos extremos.

### 3.5 Nuevo servicio: SuddenDeathRuleService

Responsabilidades:
- Evaluar preguntas con `sudden_death_mode`.
- Interrumpir evaluación por aprobación/desaprobación automática.
- Generar razón de decisión para auditoría.

### 3.6 Nuevo servicio: AutoResultRuleService

Responsabilidades:
- Evaluar reglas por nombre y apellido del candidato.
- Retornar decisión automática (`pass`, `fail`, `none`) y razón.

## 4. Cambios en flujo de examen

Archivo impactado principal: [app/Livewire/QuizRunner.php](app/Livewire/QuizRunner.php).

### 4.1 Antes de construir intento
- Validar que certificación exista y esté activa.
- Validar banco de preguntas para `app()->getLocale()`.
- Si no hay banco para locale activo, bloquear y mostrar idiomas disponibles.
- Si no hay coincidencia con idioma del navegador/locale activo y hay más de dos idiomas disponibles, mostrar selector previo y usar el idioma elegido por el usuario para construir intento.

### 4.2 Construcción de intento
- Obtener preguntas del idioma actual y fallback.
- Respetar `shuffle_questions` y `shuffle_options`.
- Respetar tipo `mcq_2` o `mcq_4` al construir opciones visibles.
- Incluir en sesión de intento: `weight`, `sudden_death_mode`, `question_type`.

### 4.3 Respuesta por pregunta
- Validar rango de opción según tipo de pregunta.
- Registrar acierto/error.
- Evaluar muerte súbita inmediatamente.
- No exponer en la interfaz de examen ni en payload público que una pregunta es de muerte súbita.

### 4.4 Cierre de intento
Aplicar orden de precedencia:
1. Muerte súbita.
2. Regla automática nombre/apellido.
3. Scoring ponderado + umbral de aprobación.

Persistir en `certificates`:
- score final.
- fuente de decisión (`sudden_death`, `auto_name_rule`, `scoring`).
- motivo de decisión.
- fechas de expiración para certificación y descarga.

## 5. Cambios en panel administrador

Archivos de referencia:
- [resources/views/admin/certifications/_form.blade.php](resources/views/admin/certifications/_form.blade.php)
- [app/Http/Requests/UpdateCertificationRequest.php](app/Http/Requests/UpdateCertificationRequest.php)
- [app/Http/Controllers/Admin/CertificationAdminController.php](app/Http/Controllers/Admin/CertificationAdminController.php)

Agregar controles para:
- Caducidad definida/indefinida y días.
- Permitir descarga tras desactivación.
- Randomización de preguntas/opciones.
- Requerir banco para activación.
- Modo de reglas automáticas nombre/apellido.

Agregar validaciones:
- Consistencia de caducidad.
- Consistencia de activación con banco mínimo.

## 6. Activación de certificación y banco mínimo

Regla de negocio:
- Si `require_question_bank_for_activation = true`, no permitir activar certificación sin al menos un banco de preguntas válido.

Definición de banco válido:
- Existe al menos una pregunta activa con texto y opciones coherentes para un idioma.

Implementación recomendada:
- Check al guardar certificación activa en request/controller.
- Check redundante en servicio de dominio para evitar bypass.

## 7. Eliminación y retención de datos

### 7.1 Eliminación de certificación
- Al eliminar certificación, bloquear nuevos intentos.
- Si configuración permite descargas históricas, conservar datos de certificados hasta su `download_expires_at`.

### 7.2 Purga automática
- Job diario programado que evalúa certificaciones vencidas.
- Purga según política configurada.

### 7.3 Purga manual
- Acción admin explícita separada de eliminar certificación.
- Permite borrar datos de usuarios aunque no haya expirado.
- Registrar auditoría de acción y volumen de registros eliminados.

## 8. Randomización

Configuraciones:
- `shuffle_questions = true|false`
- `shuffle_options = true|false`

Comportamiento:
- Preguntas en orden de BD si `shuffle_questions = false`.
- Opciones en orden original si `shuffle_options = false`.

## 9. Reglas automáticas por nombre/apellido

Modos recomendados:
- `none`
- `require_name`
- `require_last_name`
- `require_both`
- `require_any`
- `custom_regex` (opcional, posterior)

Cada evaluación devuelve:
- decisión: `pass`, `fail`, `none`
- motivo textual

## 10. Observabilidad y auditoría

Registrar eventos:
- Bloqueo por falta de idioma.
- Resultado por muerte súbita.
- Resultado por regla nombre/apellido.
- Purga automática/manual ejecutada.

Guardar en `audit_logs`:
- acción
- entidad
- cantidad afectada
- criterio aplicado

Nota de privacidad de regla:
- El motivo de muerte súbita se registra solo en backend/auditoría; no se muestra al usuario durante el examen.

## 11. Matriz de pruebas

### 11.1 Feature tests de administrador
- Configurar certificación con caducidad definida.
- Configurar certificación indefinida.
- Impedir activación sin banco de preguntas.
- Permitir activación con banco válido.
- Configurar randomización por separado (preguntas/opciones).

### 11.2 Feature tests de examen
- Preguntas `mcq_2` responden correctamente en UI y backend.
- Preguntas `mcq_4` siguen funcionando.
- Score ponderado uniforme y no uniforme.
- Caso extremo: una pregunta dominante en peso.
- Muerte súbita `fail_if_wrong` desaprueba de inmediato.
- Muerte súbita `pass_if_correct` aprueba de inmediato.
- Verificar que la UI no delate que una pregunta es de muerte súbita.
- Regla automática por nombre/apellido que fuerza pass.
- Regla automática por nombre/apellido que fuerza fail.
- Falta de banco por idioma muestra idiomas disponibles y bloquea examen.
- Si no coincide el idioma del navegador y existen más de dos idiomas, se muestra selector y el examen inicia en idioma elegido.

### 11.3 Tests de retención
- Certificación vencida bloquea descarga.
- Certificación eliminada mantiene descarga histórica si la política lo permite.
- Purga automática elimina datos vencidos.
- Purga manual elimina datos aun con vigencia activa.

### 11.4 Unit tests de servicios
- `CertificationExpirationService`.
- `QuestionBankAvailabilityService`.
- `WeightedScoringService`.
- `SuddenDeathRuleService`.
- `AutoResultRuleService`.
- `CertificationDataRetentionService`.

## 12. Secuencia de implementación recomendada

1. Migraciones de datos y casts en modelos.
2. Servicios puros (unit tests primero).
3. Integración en flujo de examen.
4. Integración en panel admin.
5. Jobs/commands de purga y programación.
6. Feature tests de regresión completa.

## 13. Compatibilidad y migración de datos existente

- Certificaciones actuales migran a:
- `expiry_mode = indefinite`
- `require_question_bank_for_activation = true`
- `shuffle_questions = true`
- `shuffle_options = true`
- `auto_result_rule_mode = none`
- Preguntas actuales migran a:
- `type = mcq_4` cuando aplique
- `weight = 1.0000`
- `sudden_death_mode = none`

## 14. Entregables

- Migraciones nuevas con rollback.
- Actualización de modelos y requests.
- Nuevos servicios de dominio.
- Ajustes en [app/Livewire/QuizRunner.php](app/Livewire/QuizRunner.php).
- Ajustes en UI admin de certificación y preguntas.
- Job de purga automática y comando manual.
- Suite de tests unitarios y feature.
- Documentación actualizada (funcional, técnica y operativa).
- Scripts operativos actualizados si cambian contratos de datos o flujos administrativos.
