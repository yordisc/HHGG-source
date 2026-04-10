# Analisis Legal-Tecnico del Editor de Certificados

Fecha: 2026-04-10
Estado: Diagnostico con plan de implementacion

## Objetivo
Evaluar si el editor actual permite emitir certificados con base suficiente para uso formal/legal y definir un plan de cierre de brechas.

## Alcance Evaluado
- Edicion de plantillas en admin.
- Generacion y descarga de PDF.
- Verificacion publica por serial.
- Integridad y seguridad del documento emitido.
- Trazabilidad y controles administrativos.

## Resumen Ejecutivo
Conclusion: **No apto aun para un certificado completamente legal**.

El sistema tiene una buena base tecnica (serial unico, pagina de verificacion, auditoria de plantillas), pero presenta brechas bloqueantes en integridad, gobernanza y evidencia juridica del documento.

## Hallazgos por Prioridad

### Prioridad Alta (Bloqueantes)
1. **La plantilla editable no controla el PDF final**
- Impacto: lo que edita el admin no necesariamente es lo que se emite oficialmente en PDF.
- Riesgo: inconsistencia documental y dificultad probatoria.
- Estado actual: el PDF se renderiza con una vista fija.

2. **Mensaje explicito de no validez legal**
- Impacto: invalida cualquier pretension de uso legal formal.
- Riesgo: contradiccion directa con objetivo de certificado legal.

3. **Ruta publica para modificar imagen del certificado por serial**
- Impacto: alteracion visual potencial del certificado sin flujo admin robusto.
- Riesgo: manipulacion de presentacion y cuestionamiento de autenticidad.

4. **Control de acceso admin insuficiente para entorno regulado**
- Impacto: autenticacion por clave compartida de entorno, sin cuentas nominativas.
- Riesgo: no hay no repudio operativo (quien hizo que cambio, con identidad fuerte).

### Prioridad Media
1. **Campos juridicos incompletos en plantilla y modelo de datos**
- Faltan metadatos tipicos: razon social emisora, identificacion fiscal, autoridad firmante, cargo, base normativa, jurisdiccion, acreditacion, version de programa, modalidad.

2. **Sin firma digital criptografica del PDF**
- Impacto: no hay garantia criptografica de integridad/autoria del archivo emitido.

3. **Sin codigo de verificacion robusto (QR + token firmado)**
- Impacto: verificar depende de serial visible; falta token anti-manipulacion.

4. **Sin politica de revocacion y estado legal del certificado**
- Faltan estados: vigente, revocado, suspendido, reemplazado, expirado legalmente.

### Prioridad Baja (Mejoras Recomendadas)
1. Watermark de version y hash del contenido emitido.
2. Registro de evidencia de emision (IP, user-agent, hora exacta, actor).
3. Plantilla legal preaprobada con campos obligatorios bloqueados.
4. Export de constancia de verificacion para terceros.

## Checklist Minimo de Cumplimiento (MVP Legal)
Marcar como completado antes de declarar "certificado legal".

- [ ] El PDF final usa la plantilla oficial de la certificacion (no vista fija hardcoded).
- [ ] Se elimina cualquier texto de "sin validez legal" en el flujo oficial.
- [ ] Emision protegida contra alteraciones publicas no autenticadas.
- [ ] Firma digital del PDF (certificado X.509 institucional) o sello equivalente.
- [ ] QR con URL de verificacion + token firmado y expiracion controlada.
- [ ] Campos juridicos obligatorios (emisor, firmante, base legal, jurisdiccion).
- [ ] Estados de vigencia/revocacion visibles en pagina publica de verificacion.
- [ ] Auditoria completa de cambios y emisiones con identidad del operador.
- [ ] Politica documentada de retencion y revocacion.
- [ ] Pruebas automatizadas de contrato de emision/verificacion.

## Plan de Implementacion Recomendado

### Fase 1 - Cierre de Riesgo Critico (3 a 5 dias)
1. Conectar render de PDF al template por certificacion/default.
2. Bloquear rutas publicas de modificacion de imagen (middleware admin y/o token firmado de un solo uso).
3. Retirar disclaimer de no validez del modo oficial (mantenerlo solo en modo demo si aplica).
4. Definir plantilla legal minima con campos obligatorios no eliminables.

Entregable: PDF coherente con editor + integridad basica.

### Fase 2 - Integridad Criptografica y Verificacion (5 a 8 dias)
1. Implementar firma digital de PDF.
2. Agregar QR con endpoint de verificacion firmado.
3. Generar hash canonico del certificado emitido y almacenarlo.
4. Exponer estado de vigencia/revocacion en pagina publica.

Entregable: certificado verificable y resistente a alteracion.

### Fase 3 - Gobernanza y Cumplimiento Operativo (4 a 7 dias)
1. Sustituir clave admin compartida por usuarios admin con roles/permisos.
2. Trazabilidad avanzada: actor, razon de cambio, diff y aprobacion.
3. Flujo de doble control para cambios de plantilla oficial.
4. Politicas y documentacion de cumplimiento.

Entregable: operacion auditable y defendible.

## Estimacion Global
- Esfuerzo total estimado: **12 a 20 dias habiles**.
- Riesgo principal: complejidad de firma digital PDF y gestion de certificados institucionales.

## Criterios de Aceptacion
1. Un tercero puede verificar autenticidad e integridad solo con QR/URL publica.
2. Cualquier alteracion del PDF invalida la firma o el hash.
3. Todo cambio de plantilla/emision queda asociado a un usuario admin identificable.
4. El contenido del PDF emitido coincide con la plantilla oficial aprobada.

## Nota
Este documento es tecnico y no reemplaza asesoria legal local. Para uso regulado, validar el modelo con asesoria juridica segun pais/sector.
