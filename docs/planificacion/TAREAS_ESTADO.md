# Tareas del proyecto: completadas y pendientes

Ultima actualizacion: 2026-04-01

## Completadas

- [x] Definicion de concepto humoristico e inclusivo.
- [x] Disclaimer visible de no-certificacion real.
- [x] Base de proyecto Laravel 11 con npm/Vite.
- [x] Soporte multi idioma (en, es, pt, zh, hi, ar, fr).
- [x] Middleware de locale por sesion y navegador.
- [x] Selector manual de idioma por ruta.
- [x] Home con flujo de inicio.
- [x] Registro de candidato para quiz.
- [x] Motor de quiz Livewire (30 preguntas aleatorias, opciones remezcladas).
- [x] Logica de resultado por umbral de errores.
- [x] Emision de certificado con serial unico.
- [x] Vista publica de certificado por serial.
- [x] Descarga de certificado en PDF (DomPDF).
- [x] Busqueda de certificado por serial o hash de documento.
- [x] Seeders iniciales de preguntas para 2 certificaciones tematicas.
- [x] Limite de intento diario por IP y documento.
- [x] Regla de renovacion por vigencia de certificado.
- [x] Limpieza diaria de certificados vencidos (scheduler).
- [x] Integracion de boton Add to LinkedIn.
- [x] Login admin por clave de entorno para proteger panel de preguntas.
- [x] CRUD completo de preguntas (crear, editar, eliminar) con traducciones.
- [x] Importacion y exportacion CSV para preguntas, opciones y traducciones.
- [x] Pruebas feature iniciales para home, busqueda y admin CSV.
- [x] Middleware de headers de seguridad aplicado al grupo web.
- [x] Pruebas feature del flujo quiz y endpoints de certificado/PDF.
- [x] Pruebas unitarias del modelo Certificate.

## En curso

- [x] Uso real de question_translations en el quiz con fallback por idioma.
- [x] Completar traducciones iniciales de preguntas por idioma en base de datos.

## Pendientes recomendadas

- [x] Panel admin basico para editar preguntas, traducciones y activacion.
- [x] Exportacion CSV para banco de preguntas.
- [x] Tests automaticos base (feature + unit) para busqueda, admin, quiz y certificados.
- [x] Validaciones de seguridad base (headers, hardening).
- [x] Ajustes de UX responsive y microcopy por idioma.
- [x] Observabilidad basica (logs de eventos clave y metricas de intentos).
- [x] Politica de retencion y borrado de datos documentada.
- [x] Flujo de despliegue documentado (staging/produccion).

## Criterios de cierre MVP

- [x] Flujo end-to-end funcionando: home -> registro -> quiz -> resultado -> certificado -> PDF.
- [x] i18n de interfaz completa.
- [x] Restricciones anti abuso minimas operativas.
- [x] Cobertura de pruebas minima acordada.
- [x] Backoffice para editar preguntas sin tocar codigo.
