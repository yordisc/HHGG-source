# Plan de Arranque MVP (10 dias)

## Dia 1: Bootstrap del proyecto
- Crear app Laravel 11.
- Instalar Livewire, DomPDF, QRCode.
- Configurar .env local y DB.
- Verificar que levanta con `php artisan serve`.

Entrega:
- App corriendo en local.

## Dia 2: Modelo de datos
- Migraciones: certificates, questions, question_translations, rate_limits.
- Modelos Eloquent y relaciones.
- Indices para serial, expiracion y busquedas.

Entrega:
- `php artisan migrate` sin errores.

## Dia 3: Seeders y banco de preguntas
- Cargar minimo 60 preguntas por tipo.
- Guardar en ingles como base unica.

Entrega:
- `php artisan db:seed` con datos validos.

## Dia 4: Servicios core
- QuizService: seleccion aleatoria + shuffle de opciones.
- CertificateService: serial, vigencia, renovacion.
- TranslationService: cache DB + fallback ingles.
- PDFService: render de certificado.

Entrega:
- Tests basicos de servicio pasando.

## Dia 5: Flujo web principal
- Rutas: home, start quiz, result, cert show, cert pdf, search.
- Controladores iniciales.

Entrega:
- Se puede completar flujo basico de extremo a extremo.

## Dia 6: Componentes Livewire
- RegistrationForm.
- Quiz (30 preguntas, progreso, submit final).
- SearchBar.

Entrega:
- Quiz funcional con resultado persistido.

## Dia 7: UI responsive + i18n
- Layout principal y pagina home.
- Selector manual de idioma.
- Middleware de locale por navegador con fallback en.
- Banner visible de disclaimer humoristico e inclusivo.

Entrega:
- Web usable en movil y escritorio.
- Disclaimer visible en home y footer.

## Dia 8: Seguridad y limites
- Middleware SecurityHeaders.
- Middleware QuizRateLimit (1 intento diario + renovacion 30 dias).
- Hash de documento e IP.

Entrega:
- Protecciones activas y verificadas.

## Dia 9: PDF + vista publica + LinkedIn
- PDF on-demand por serial.
- Vista publica de certificado.
- Boton de agregar a LinkedIn.
- Incluir disclaimer en PDF y vista publica del certificado.

Entrega:
- Certificado compartible y descargable.

## Dia 10: limpieza y despliegue
- Comando `certificates:clean` diario.
- Railway deploy + variables de entorno.
- Smoke test en produccion.

Entrega:
- MVP publicado.

## Criterios de aceptacion del MVP
- Crear certificado toma menos de 3 minutos de flujo usuario.
- Busqueda por serial devuelve vista publica y PDF.
- Certificado expira a 1 ano exacto.
- Reintento permitido despues de 30 dias.
- Limite diario bloquea abuso.
- Interfaz disponible en al menos 5 idiomas.
- Disclaimer legal visible en 3 puntos: home, resultado y PDF.

## Backlog post-MVP
- Panel admin para CRUD de preguntas.
- Dashboard de metricas de intentos y aprobacion.
- Cola de trabajos para traduccion masiva.
- Firma visual avanzada del PDF.
