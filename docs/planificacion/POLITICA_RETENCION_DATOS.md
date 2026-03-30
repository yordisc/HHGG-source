# Politica de retencion y borrado de datos

Ultima actualizacion: 2026-03-30

## Objetivo

Definir que datos se almacenan, por cuanto tiempo, y como se eliminan en el proyecto CertificacionHHGG.

## Principios

- Minimizacion: almacenar solo datos necesarios para operar el flujo.
- Seudonimizacion: no guardar documento plano en tablas persistentes.
- Retencion limitada: borrar datos cuando dejan de ser necesarios.
- Trazabilidad: registrar eventos operativos clave sin exponer datos sensibles.

## Datos almacenados

1. Tabla certificates
- serial
- cert_type
- result_key
- first_name
- last_name
- country
- document_hash (bcrypt)
- doc_lookup_hash (HMAC)
- doc_partial (ultimos 4)
- score_correct
- score_incorrect
- total_questions
- issued_at
- expires_at
- last_attempt_at

2. Tabla rate_limits
- identifier_hash
- scope
- attempted_at

3. Tabla questions y question_translations
- contenido del banco de preguntas y traducciones (sin datos personales)

4. Sesion de quiz (transitoria)
- datos del candidato mientras responde el quiz
- se elimina al finalizar intento o expirar sesion

## Retencion por tipo de dato

1. Certificados
- Vigencia funcional: hasta expires_at.
- Borrado automatico: diario por comando certificates:clean.
- Implementacion actual:
  - comando: app/Console/Commands/CleanExpiredCertificates.php
  - scheduler: routes/console.php

2. Rate limits
- Retencion recomendada: 35 dias.
- Uso: antiabuso y control de intentos.
- Estado actual: la tabla crece si no se limpia.
- Accion recomendada: agregar comando de limpieza programada para rate_limits (pendiente tecnica futura).

3. Logs
- Retencion recomendada: 30-90 dias segun volumen y entorno.
- No incluir documento plano, hashes completos ni secretos.
- Estado actual: se registran eventos operativos con datos acotados.

## Borrado y purga

Flujo implementado para certificados:

1. Scheduler ejecuta diariamente:
- php artisan schedule:run

2. Se dispara:
- php artisan certificates:clean

3. El comando elimina registros donde expires_at < now().

## Solicitudes manuales de borrado

Proceso recomendado para operacion:

1. Identificar certificado por serial o doc_lookup_hash.
2. Validar autorizacion de quien solicita.
3. Ejecutar borrado por consola o panel admin interno (si aplica).
4. Registrar evento operativo de borrado sin exponer datos sensibles.

## Seguridad y privacidad aplicadas

- Documento legal no se persiste en texto plano.
- Hash de consulta (doc_lookup_hash) para busqueda.
- Hash bcrypt (document_hash) para almacenamiento irreversible.
- Cabeceras de seguridad activas en middleware web.

## Pendientes de mejora

- Agregar purga programada de rate_limits.
- Definir periodo formal de retencion para logs por entorno.
- Incorporar runbook de respuesta ante incidentes de datos.
