# Menú de administrador seguro

## Objetivo

Diseñar un menú de administrador oculto y protegido para gestionar certificaciones, preguntas y usuarios sin exponer la operación al público ni romper el flujo actual de home, quiz y certificados.

Este documento describe el estado actual del repositorio, las brechas funcionales y una propuesta de implementación alineada con la arquitectura que ya existe.

---

## Estado actual del repositorio

### Lo que ya existe

- Existe un prefijo privado `admin` en `routes/web.php`.
- El acceso administrativo usa `ADMIN_ACCESS_KEY` y sesión con `admin_authenticated`.
- El middleware `admin.auth` protege las rutas privadas.
- Ya existe el modulo de usuarios con listado, alta, edicion, eliminacion y exportacion CSV.

## Cómo usar el menú hoy

### 1. Entrar al panel

1. Abrir `/admin/login`.
2. Ingresar la clave `ADMIN_ACCESS_KEY`.
3. Seras redirigido al dashboard central.

### 2. Usar el dashboard

- Desde el panel central puedes abrir el listado de certificaciones.
- Desde el panel central puedes abrir el listado de preguntas.
- El boton de cierre de sesion invalida la sesion administrativa.

### 3. Gestionar certificaciones

1. Entrar a la seccion de certificaciones.
2. Crear una nueva certificacion con `slug`, `name`, `questions_required`, `pass_score_percentage`, `cooldown_days`, `result_mode`, `pdf_view`, `home_order` y `settings`.
3. Marcarla como activa para que aparezca en home y en el flujo de quiz.
4. Editarla cuando cambie su configuracion funcional.
5. Eliminarla solo si ya no debe formar parte del catalogo.
6. Activarla o desactivarla desde el listado rapido o desde el formulario de edicion.

### 4. Gestionar preguntas

1. Entrar a la seccion de preguntas.
2. Crear o editar preguntas base.
3. Asignar la pregunta a una certificacion activa mediante `cert_type`.
4. Subir CSV cuando necesites carga masiva.
5. Exportar CSV o plantilla cuando quieras replicar estructura o hacer backup operativo.

### 5. Efecto de los cambios

- Activar o desactivar una certificacion cambia su visibilidad en la home.
- Reordenar una certificacion cambia el orden de las tarjetas publicas.
- Cambiar preguntas o traducciones impacta el flujo del quiz en el siguiente render.

### 6. Gestionar usuarios cuando el modulo este disponible

1. Entrar al modulo de usuarios desde el dashboard.
2. Crear o editar usuarios con nombre y contraseña; el correo interno se genera automaticamente.
3. Revisar el listado y aplicar filtros por fecha, pais o estado si existen.
4. Exportar CSV cuando necesites respaldo, auditoria o trabajo externo.
5. Importar CSV solo cuando la regla de negocio lo permita y el archivo cumpla validaciones.
6. Registrar cualquier lote exportado o importado para mantener trazabilidad.

Nota:

- El flujo principal ya esta activo para listado, alta, edicion, eliminacion y exportacion.
- La importacion sigue siendo parte del plan y se puede agregar despues.
- El panel no solicita correo al operador; lo genera internamente para cumplir la base de datos.

## Backlog de implementación inicial

### Sprint 0. Base del dashboard

- Crear `AdminDashboardController` con una vista de entrada para `/admin`.
- Reemplazar la navegación dispersa por un menú lateral único.
- Definir tarjetas de acceso para Certificaciones, Preguntas y Usuarios.
- Mantener el login actual sin cambiar el mecanismo de autenticación.

Criterio de listo:

- el admin entra a `/admin` y ve un panel central con acceso a módulos.

### Sprint 1. Certificaciones CRUD

- Crear `CertificationAdminController`.
- Implementar listado, alta, edición y cambio de estado.
- Exponer `questions_required`, `pass_score_percentage`, `cooldown_days`, `home_order`, `result_mode`, `pdf_view` y `settings`.
- Validar `slug` único y campos numéricos dentro de rango.

Criterio de listo:

- una certificación puede crearse y editarse desde admin sin tocar código.

### Sprint 2. Preguntas dentro del módulo admin

- Mantener el CRUD actual y moverlo a una sección explícita del dashboard.
- Separar vista de listado, formulario y acciones masivas.
- Dejar importación CSV, exportación CSV y plantilla CSV bajo un mismo submenú.
- Mantener soporte de traducciones por idioma.

Criterio de listo:

- preguntas y traducciones se administran desde una ruta y una interfaz coherentes.

### Sprint 3. Traducciones de certificaciones

- Decidir tabla dedicada o JSON estructurado.
- Crear esquema de persistencia para `name` y `description` por locale.
- Agregar selector de idioma en el formulario de certificaciones.
- Definir fallback al idioma base cuando falte traducción.

Criterio de listo:

- la metadata visible de cada certificación puede administrarse en los idiomas soportados.

### Sprint 4. Usuarios y exportaciones

- Crear `UserAdminController` con listado y filtros.
- Exponer exportación CSV con columnas permitidas.
- Definir importación solo si hay caso de uso real y reglas de validación.
- Registrar lotes importados o exportados con trazabilidad básica.

Criterio de listo:

- el admin puede revisar y exportar usuarios con reglas claras y controladas.

### Sprint 5. Integración con home y endurecimiento

- Verificar que la home sigue leyendo desde `Certification::active()->ordered()`.
- Invalidar cachés si se agregan en el futuro.
- Agregar pruebas de contrato para CRUD e import/export.
- Añadir rate limit al login admin si aún no está presente.

Criterio de listo:

- los cambios del panel se reflejan en home y el flujo queda cubierto por tests.
- Las preguntas ya se administran desde el panel actual con:
  - alta
  - edición
  - baja
  - importación CSV
  - exportación CSV
  - plantilla CSV
- Las preguntas ya soportan traducciones por idioma.
- La home ya se construye desde `Certification::query()->active()->ordered()->get()`.
- El modelo `Certification` ya concentra la configuración funcional principal de cada certificación.
- Ya existe un dashboard admin central con acceso a certificaciones y preguntas.
- Ya existe el CRUD base de certificaciones en el panel.

### Lo que falta

- Un dashboard administrativo real, no solo el CRUD de preguntas.
- CRUD completo de certificaciones.
- Gestión de usuarios desde admin.
- Importación y exportación de usuarios.
- Soporte de traducción para la metadata de certificaciones.
- Navegación interna de admin con secciones claras.
- Auditoría operativa de cambios sobre certificaciones, preguntas y usuarios.

---

## Principio de diseño

El menú debe ser:

- oculto por URL, no por seguridad visual;
- protegido por autenticación de sesión;
- limitado por una clave de acceso administrativa;
- auditable;
- extensible por módulos;
- consistente con el modelo de certificaciones basado en datos.

La seguridad no depende de esconder enlaces en la interfaz. Depende de middleware, validación de sesión y control de entrada.

---

## Arquitectura propuesta

### 1. Capa de acceso

Mantener el patrón actual:

- login en `/admin/login`;
- validación contra `ADMIN_ACCESS_KEY`;
- regeneración de sesión al autenticar;
- protección de todo el árbol `/admin/*` con middleware `admin.auth`.

Recomendación:

- conservar el login actual como puerta única;
- mover el acceso a un `AdminDashboardController` o `AdminHomeController`;
- no exponer ningún enlace admin en la navegación pública;
- añadir rate limiting al login si todavía no existe.

### 2. Capa de navegación interna

Crear un dashboard con módulos separados:

- Certificaciones
- Preguntas
- Usuarios
- Importaciones
- Exportaciones
- Configuración operativa
- Auditoría básica

Cada módulo debe entrar desde un índice central del admin, no desde rutas sueltas.

### 3. Capa de dominio

Separar la lógica por agregado:

- `Certification` como catálogo funcional.
- `Question` como contenido asociado a una certificación.
- `User` como identidad operativa y, si aplica, sujeto a exportación/importación.
- `Certificate` como evidencia de resultados y trazabilidad.

Esto evita que el panel termine siendo una colección de formularios aislados.

---

## Módulos funcionales

### 1. Certificaciones

Debe permitir:

- crear certificación;
- editar certificación;
- activar o desactivar certificación;
- cambiar el orden de aparición en home;
- ajustar el número de preguntas requeridas;
- ajustar el porcentaje de aprobación;
- definir días de cooldown;
- elegir modo de resultado;
- seleccionar vista PDF si aplica;
- guardar configuración adicional.

Campos recomendados a exponer en formulario:

- `slug`
- `name`
- `description`
- `active`
- `questions_required`
- `pass_score_percentage`
- `cooldown_days`
- `result_mode`
- `pdf_view`
- `home_order`
- `settings`

Reglas:

- `slug` debe ser único y estable.
- `home_order` debe determinar el orden visible.
- `active` controla visibilidad pública y elegibilidad.
- cambiar una certificación debe impactar de inmediato en home y en quiz.

### 2. Preguntas

El módulo actual ya cubre la operación básica. El siguiente paso es formalizarlo dentro del menú con vistas propias.

Debe permitir:

- listar preguntas por certificación;
- crear y editar preguntas;
- activar/desactivar preguntas;
- importar CSV;
- exportar CSV;
- descargar plantilla CSV;
- editar traducciones por idioma;
- filtrar por certificación y por estado.

Reglas:

- cada pregunta debe pertenecer a una sola certificación;
- el import CSV debe resolver certificación por `slug`;
- la exportación debe incluir la certificación asociada;
- las traducciones no deben romper la pregunta base.

### 3. Usuarios

Este módulo no existe todavía como panel dedicado y conviene diseñarlo aparte.

Debe permitir:

- listar usuarios;
- filtrar por fecha, país, estado o certificación relacionada;
- editar datos permitidos por operación;
- exportar usuarios a CSV;
- importar usuarios desde CSV si el caso de uso lo justifica;
- revisar actividad o intentos asociados, si el negocio lo necesita.

Reglas sugeridas:

- la importación debe validar duplicados;
- la exportación debe ser explícita por permiso operativo;
- si se almacenan datos sensibles, limitar columnas exportables;
- registrar quién exportó o importó cada lote.

### 4. Traducciones

Hoy las preguntas ya tienen traducción. Para certificaciones, falta la misma capacidad si la home y el admin deben reflejar todos los idiomas administrados.

Propuesta:

- introducir una tabla `certification_translations`, o
- usar un campo `settings` estructurado si el alcance es menor.

Recomendación técnica:

- si la certificación tiene `name` y `description` visibles por idioma, usar una tabla de traducciones igual que preguntas;
- si solo se necesita contenido de presentación básico, JSON puede ser suficiente, pero escala peor para edición.

Campos mínimos por traducción:

- `certification_id`
- `locale`
- `name`
- `description`
- `cta_text` opcional
- `seo_title` opcional
- `seo_description` opcional

---

## Propagación automática a home

La home ya lee desde la base de datos. Eso es la base correcta.

Para que un cambio en admin aparezca sin trabajo adicional:

- la home debe consultar `Certification::active()->ordered()`;
- el panel de admin debe escribir en la misma tabla `certifications`;
- si más adelante hay caché, invalidarla al guardar o eliminar una certificación;
- las vistas públicas deben usar un resolver de presentación, no valores duplicados.

Resultado esperado:

- crear una certificación la hace visible en home si queda activa;
- desactivarla la oculta de inmediato;
- reordenarla cambia el orden visible;
- modificar el texto se refleja en el siguiente render.

---

## Seguridad

### Riesgos que este diseño evita

- acceso accidental al panel desde navegación pública;
- exposición de CRUD sin autenticación;
- manipulación de contenido por usuarios no autorizados;
- rutas de import/export sin control;
- fuga de usuarios o preguntas por endpoints públicos.

### Controles mínimos

- middleware `admin.auth` para todas las rutas admin;
- login con `ADMIN_ACCESS_KEY`;
- regeneración de sesión al iniciar sesión;
- logout que invalide sesión administrativa;
- mensajes de estado genéricos, sin revelar información sensible;
- validación estricta de archivos CSV;
- uso de transacciones en importaciones.

### Controles recomendados

- rate limit en `/admin/login`;
- logging de altas, cambios, bajas e importaciones;
- confirmación explícita antes de eliminar registros;
- protección adicional para exportaciones masivas;
- políticas de autorización si luego se agregan roles reales.

---

## Propuesta técnica de rutas

### Estructura base

- `/admin/login`
- `/admin/logout`
- `/admin`
- `/admin/certifications`
- `/admin/certifications/create`
- `/admin/certifications/{certification}/edit`
- `/admin/questions`
- `/admin/questions/import-csv`
- `/admin/questions/export-csv`
- `/admin/questions/template-csv`
- `/admin/users`
- `/admin/users/import-csv`
- `/admin/users/export-csv`

### Recomendación de implementación

- usar controladores separados por dominio;
- evitar que `QuestionAdminController` siga creciendo sin límite;
- mantener un controlador para auth administrativa;
- agregar un controlador de dashboard;
- considerar Livewire solo donde la interacción lo justifique.

---

## Estructura de archivos sugerida

- `app/Http/Controllers/Admin/AdminDashboardController.php`
- `app/Http/Controllers/Admin/CertificationAdminController.php`
- `app/Http/Controllers/Admin/QuestionAdminController.php`
- `app/Http/Controllers/Admin/UserAdminController.php`
- `app/Http/Controllers/Admin/AdminAuthController.php`
- `app/Http/Middleware/EnsureAdminAuthenticated.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/certifications/*.blade.php`
- `resources/views/admin/questions/*.blade.php`
- `resources/views/admin/users/*.blade.php`

---

## Flujo operativo esperado

### Alta de certificación

1. El admin entra a `/admin/login`.
2. Autentica con la clave administrativa.
3. Va al módulo de certificaciones.
4. Crea una nueva certificación con metadata completa.
5. Define si estará activa y su orden en home.
6. Guarda.
7. La home la muestra automáticamente si está activa.

### Edición de preguntas

1. El admin entra al módulo de preguntas.
2. Filtra por certificación.
3. Edita o importa CSV.
4. Agrega traducciones si corresponde.
5. El quiz público usa el contenido actualizado.

### Gestión de usuarios

1. El admin entra al módulo de usuarios.
2. Revisa listados o exporta datos.
3. Importa si existe un flujo permitido de carga masiva.
4. Registra el lote y el responsable.

---

## Reglas de idioma

El sistema ya trabaja con locales soportados desde `config('app.supported_locales')`.

Para que el menú sea consistente:

- el admin debe mostrar selector de idioma cuando edite contenido multilenguaje;
- el idioma activo debe respetar el contexto de sesión;
- la traducción de preguntas debe seguir usando `QuestionTranslation`;
- la metadata de certificaciones debe tener su propio mecanismo de traducción;
- el home debe elegir el contenido visible según locale.

Si no existe traducción para un locale, se debe caer a idioma base.

---

## Validaciones recomendadas

### Certificaciones

- `slug` requerido, único y seguro para URL.
- `name` requerido.
- `questions_required` entero mayor que cero.
- `pass_score_percentage` entre 0 y 100.
- `cooldown_days` entero no negativo.
- `home_order` entero no negativo.
- `result_mode` dentro de los valores permitidos.

### Preguntas

- certificación obligatoria.
- opciones completas.
- `correct_option` entre 1 y 4.
- traducción opcional pero coherente.

### Usuarios

- columnas importadas controladas.
- formato CSV validado.
- reglas de duplicado y normalización definidas antes de activar importación masiva.

---

## Riesgos y decisiones

### Riesgos

- crecer el panel sin separación por módulos;
- introducir traducciones de certificación sin esquema claro;
- habilitar importación de usuarios sin validación fuerte;
- usar CSV como contrato débil sin tests de estructura;
- mezclar lógica de dominio con lógica de UI.

### Decisiones recomendadas

- usar el modelo `Certification` como fuente única de verdad;
- separar controladores por módulo;
- introducir traducciones de certificaciones con tabla dedicada si el alcance multilenguaje es real;
- mantener pruebas de contrato para import/export;
- no duplicar estado de home en configuraciones manuales.

---

## Fases de implementación

### Fase 1. Dashboard mínimo

- login administrativo
- home del admin
- menú lateral
- acceso a preguntas existente

### Fase 2. Certificaciones CRUD

- alta
- edición
- activación/desactivación
- orden de home
- parámetros funcionales

### Fase 3. Traducciones de certificaciones

- tabla de traducciones
- selector de idioma en admin
- fallback a idioma base

### Fase 4. Usuarios

- listado
- exportación
- importación controlada
- auditoría

### Fase 5. Endurecimiento

- rate limit
- logs
- pruebas de contrato
- validaciones de permisos si se incorporan roles

---

## Criterio de cierre

Este diseño se considera terminado cuando:

- el admin entra por una URL privada y autenticada;
- el dashboard organiza certificaciones, preguntas y usuarios;
- las certificaciones se crean y editan sin tocar código;
- la home refleja inmediatamente los cambios;
- las preguntas mantienen import/export y traducciones;
- los usuarios pueden exportarse o importarse con reglas claras;
- el sistema soporta contenido administrable en todos los idiomas activos.
