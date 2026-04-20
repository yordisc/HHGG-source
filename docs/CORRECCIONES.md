# Plan de Acción: Mejoras del Sistema de Certificaciones

## 1. Módulo de Plantillas y Generación de Certificados
- [ ] **Soporte Avanzado para Imágenes y Fondos:**
  - [ ] Implementar la capacidad de insertar la imagen en cualquier parte de la plantilla HTML o configurarla como imagen de fondo (`background-image`).
  - [ ] Añadir una opción (toggle/checkbox) para habilitar o deshabilitar el efecto sepia sobre la imagen seleccionada.
  - [ ] Permitir que el origen de esta imagen sea seleccionable: desde la carpeta `public/` (ej. el edificio de Harvard) o utilizando la **imagen destacada** de la certificación actual.
- [ ] **Unificación del Editor de Plantillas:**
  - [ ] Modificar la vista de edición para tener un solo bloque de texto que combine HTML y CSS (usando la etiqueta `<style>`), facilitando el copiado y pegado de diseños externos.
- [ ] **Gestor de Recursos (Media/Firmas):**
  - [ ] Crear un panel en el editor que liste las imágenes y firmas disponibles en `public/Certificates` y `public/Signature` para copiar su ruta fácilmente.
- [ ] **Expansión de Variables Dinámicas:**
  - [ ] Crear variable `{{nombre_completo}}` que fusione nombre y apellido.
  - [ ] Añadir nuevas variables útiles: `{{documento_identidad}}`, `{{horas_cursadas}}`, `{{pais_origen}}`, `{{mencion_honorifica}}`, `{{nombre_certificacion}}`.

## 2. Corrección de Errores Críticos y Estética (Prioridad Alta)
- [ ] **Bug en la Vista de Preguntas:**
  - [ ] Solucionar el error `Internal Server Error: Array to string conversion` al presionar el botón "Preguntas", formateando correctamente la salida de los arreglos en la vista.
- [ ] **Imagen Destacada de la Certificación:**
  - [ ] Añadir un campo de subida de archivo en "Editar Certificación" para definir la imagen de portada.
  - [ ] Mostrar esta imagen en el menú principal de certificaciones.

## 3. Módulo de Pruebas y Quizzes
- [ ] **Bug de Registro en el Quiz:**
  - [ ] Reparar el flujo donde el candidato se registra pero el examen no comienza (revisar redirección y variables de sesión de Livewire).
- [ ] **Modo de Prueba (Preview) Realista:**
  - [ ] Modificar la opción de "prueba de funcionamiento" en la administración para que muestre el examen exactamente como lo ve el estudiante (interactivo, una pregunta a la vez, permitiendo selección) en lugar de listar todas las preguntas de golpe.
- [ ] **Auto-formato CSS:**
  - [ ] Corregir las reglas de estilo de las cajas de preguntas/respuestas para que tomen el formato y tamaño automáticamente según su contenido.

## 4. Módulo de Gestión de Usuarios y Roles
- [ ] **Separación Visual en el Dashboard:**
  - [ ] Mostrar estadísticas y botones separados para "Administradores" y "Usuarios" en el menú principal.
- [ ] **Separación en el CRUD de Usuarios:**
  - [ ] Dividir la vista de "Usuarios" en dos espacios/pestañas distintas (Administradores vs Usuarios regulares) para evitar confusiones al ver información o importar/exportar datos.
- [ ] **Flexibilidad de Roles (Admin como Usuario):**
  - [ ] Asegurar que un usuario administrador (`is_admin = true`) pueda realizar exámenes y solicitar sus propios certificados sin problemas de permisos.


¡Excelente mentalidad! Planificar el impacto en las pruebas (tests) antes de tocar el código es fundamental para no romper el sistema a ciegas. 

Dado que el proyecto tiene una suite de pruebas bastante robusta (basándome en la estructura de la carpeta `tests/`), los cambios del plan de acción van a requerir actualizar varias aserciones y agregar nuevos escenarios.

Aquí tienes el desglose de las repercusiones y modificaciones necesarias en los tests, organizados por módulo:

### 1. Módulo de Plantillas y Generación de Certificados
Al cambiar cómo se guardan y compilan las plantillas, varias pruebas de feature y unitarias fallarán si no se actualizan.

* **`tests/Feature/AdminCertificateTemplateTest.php`**: 
    * **El quiebre:** Actualmente, este test seguramente envía variables separadas para `html_template` y `css_template` al guardar. Si unificamos el editor, el request cambiará (tal vez solo se envíe `content` o se siga dividiendo en el backend pero en el frontend esté unificado). 
    * **El cambio:** Actualizar el payload de las pruebas de creación y actualización para coincidir con la nueva estructura del formulario. Añadir pruebas para la validación del checkbox/opción de "Efecto Sepia" y "Fondo Personalizado".
* **`tests/Unit/CreateCertificateActionTest.php`** y **`tests/Feature/CertificatePdfLanguageTest.php`**:
    * **El quiebre:** La inyección de variables en el PDF va a cambiar.
    * **El cambio:** Añadir aserciones (assertions) para asegurar que las nuevas variables (`{{nombre_completo}}`, `{{imagen_fondo}}`, `{{documento_identidad}}`) se están reemplazando correctamente en el HTML resultante antes de generar el PDF.

### 2. Corrección de Errores Críticos y Estética
Al añadir campos a la base de datos y corregir vistas, hay que garantizar que la nueva información fluya correctamente.

* **`tests/Feature/AdminQuestionsTest.php`**:
    * **El cambio:** Para asegurar que el bug de `Array to string conversion` no vuelva a ocurrir, se debe añadir una prueba que renderice la vista `index` de preguntas (haciendo un `$response->assertOk()`) cuando la base de datos contenga preguntas con múltiples opciones y traducciones complejas.
* **`tests/Feature/AdminCertificationEditTest.php`** y **`tests/Unit/CertificationModelTest.php`**:
    * **El cambio:** Actualizar las reglas de validación en el Request para permitir subir archivos (imágenes). El test debe simular la subida de un archivo (usando `UploadedFile::fake()->image('portada.jpg')`) y afirmar (`assert`) que la ruta de la imagen destacada se guarda en la base de datos.
* **`tests/Feature/HomeAndSearchTest.php`**:
    * **El cambio:** Asegurar que si una certificación tiene una imagen destacada configurada, la vista principal (`home.blade.php`) la muestra en la tarjeta correspondiente.

### 3. Módulo de Pruebas y Quizzes
Este es el punto más delicado porque involucra el flujo crítico del candidato.

* **`tests/Feature/QuizFlowTest.php`** y **`tests/Feature/QuizRunnerLivewireTest.php`**:
    * **El quiebre:** Resolver el bug de "registro pero no inicio" probablemente alterará el controlador o el montaje del componente Livewire. 
    * **El cambio:** Escribir un test de integración completo que haga un HTTP POST al registro del examen, siga la redirección (`assertRedirect`) y verifique que la sesión inicie el componente `Livewire::test(QuizRunner::class)` correctamente, mostrando la primera pregunta.
* **`tests/Feature/AdminCertificationWizardAndTestToolsTest.php`**:
    * **El quiebre:** Actualmente la "Prueba de funcionamiento" lista las preguntas en bloque. Al cambiarlo a una previsualización real (una pregunta a la vez), los tests que busquen todas las preguntas renderizadas simultáneamente en la respuesta van a fallar.
    * **El cambio:** Modificar el test para asegurar que el modo "Test" del admin carga el componente Livewire interactivo y simular clics en las respuestas.

### 4. Módulo de Gestión de Usuarios y Roles
Las separaciones lógicas afectarán los conteos y la visibilidad de los registros.

* **`tests/Feature/AdminDashboardAndCertificationsTest.php`**:
    * **El quiebre:** Si el dashboard mostraba "Usuarios Totales: 10" y ahora debe mostrar "Administradores: 2, Usuarios: 8", el test que verifica el número renderizado va a fallar.
    * **El cambio:** Modificar la prueba creando 2 admins y 3 usuarios normales con los Factories, y verificar que la vista muestre los conteos separados correctamente.
* **`tests/Feature/AdminUsersTest.php`**:
    * **El cambio:** Añadir pruebas para la nueva separación en el CRUD. Verificar que al aplicar el filtro/pestaña "Administradores", solo se rendericen en pantalla los usuarios con `is_admin = true`, y viceversa para la pestaña "Usuarios".
* **`tests/Feature/QuizEligibilityEndpointTest.php`** (y creación de nuevo test):
    * **El cambio:** Crear un bloque de prueba específico: `test_admin_user_can_take_a_quiz_and_get_certified()`. Esto es vital para asegurar que la lógica de "doble rol" no bloquee al administrador por middlewares mal configurados al intentar hacer una prueba como alumno.

**Estrategia recomendada:**
Para no ahogarse en errores rojos en la terminal, lo mejor es **escribir o modificar el test correspondiente justo antes de corregir el error en el código** (Desarrollo Guiado por Pruebas o TDD). 
