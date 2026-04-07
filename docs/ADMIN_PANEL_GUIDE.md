# Guía de Administración - Panel de Control de Certificaciones

## Contenido
1. [Introducción](#introducción)
2. [Acceso al Panel](#acceso-al-panel)
3. [Dashboard](#dashboard)
4. [Gestión de Certificaciones](#gestión-de-certificaciones)
5. [Gestión de Preguntas](#gestión-de-preguntas)
6. [Gestión de Usuarios](#gestión-de-usuarios)
7. [Plantillas de Certificados](#plantillas-de-certificados)
8. [Pruebas de Certificaciones](#pruebas-de-certificaciones)
9. [Registro de Auditoría](#registro-de-auditoría)
10. [Solución de Problemas](#solución-de-problemas)

---

## Introducción

El panel de administración permite gestionar todas los aspectos del sistema de certificaciones:
- **Certificaciones**: Crear, editar y eliminar certificaciones
- **Preguntas**: Gestionar preguntas de exámenes
- **Usuarios**: Crear y administrar usuarios evaluadores
- **Plantillas**: Personalizar la apariencia de certificados
- **Auditoría**: Revisar el historial de cambios

### Requisitos
- Clave de acceso administrativa (`ADMIN_ACCESS_KEY`)
- Navegador web actualizado
- JavaScript habilitado

---

## Acceso al Panel

### Iniciando sesión

1. Navega a `/admin/login`
2. Ingresa la clave de acceso administrativa
3. Haz clic en "Iniciar Sesión"

### Cerrar sesión

- Haz clic en "Cerrar Sesión" en la esquina superior derecha del panel

### Cambio de Idioma

Usa el selector de idioma en la esquina superior derecha para cambiar entre idiomas soportados:
- Español
- Inglés
- Francés
- Portugués
- Hindi
- Árabe
- Chino

---

## Dashboard

El dashboard muestra:
- **Estadísticas General**: Recuento de certificaciones, preguntas y usuarios
- **Certificaciones Activas**: Lista de certificaciones disponibles
- **Acceso Rápido**: Enlaces a funciones principales

### Funciones Principales

| Función | Descripción |
|---------|------------|
| **Certificaciones** | CRUD de certificaciones y gestión de preguntas |
| **Preguntas** | Importar/exportar preguntas en CSV |
| **Usuarios** | Crear/actualizar usuarios, import/export CSV |
| **Plantillas** | Personalizar apariencia de certificados |
| **Auditoría** | Revisar historial de cambios |

---

## Gestión de Certificaciones

### Crear una Nueva Certificación (Wizard)

El proceso de creación guiado simplifica la creación de certificaciones:

#### Paso 1: Información Básica
- **Nombre**: Nombre único de la certificación (ej: "HHGG Básico")
- **Descripción**: Descripción contextual de la certificación
- **Slug**: Identificador único en URL (generado automáticamente)
- **Activa**: Marcar para activar inmediatamente

#### Paso 2: Configuración del Examen
- **Tiempo Límite**: Minutos máximos para completar el examen
- **Preguntas Aleatorias**: Barajar orden de preguntas
- **Respuestas Aleatorias**: Barajar orden de respuestas
- **Mostrar Resultado Inmediato**: Mostrar puntuación al completar

#### Paso 3: Criterios de Aprobación
- **Puntuación Mínima**: Porcentaje mínimo para aprobar (0-100%)
- **Puntuación Máxima**: Puntuación total del examen

#### Paso 4: Configuración Avanzada
- **Requiere Verificación de Email**: Validar email antes de examen
- **Máximo de Intentos**: Intentos permitidos por usuario (0 = ilimitado)
- **Días de Validez**: Días antes de expirar el certificado (0 = sin expiración)

#### Paso 5: Revisión y Confirmación
- Revisar toda la información ingresada
- Hacer clic en "Crear Certificación"

### Editar una Certificación

1. Desde "Certificaciones", haz clic en el botón "Editar"
2. Modifica los campos deseados
3. Haz clic en "Actualizar"

### Eliminar una Certificación

1. Desde la lista de certificaciones, selecciona la certificación
2. Haz clic en "Eliminar"
3. Confirma la eliminación

**⚠️ Advertencia**: Eliminar una certificación también elimina todos sus certificados y resultados.

### Reordenar Certificaciones

1. En la lista de certificaciones, arrastra cada fila para reordenar
2. El orden se guarda automáticamente

### Gestionar Preguntas de una Certificación

1. Desde "Certificaciones", haz clic en "Editar" en la certificación deseada
2. Ve a la Pestaña "Preguntas"
3. Aquí puedes:
   - Ver todas las preguntas de la certificación
   - Agregar nuevas preguntas
   - Editar preguntas existentes
   - Eliminar preguntas

---

## Gestión de Preguntas

### Importar Preguntas (CSV)

**Formato esperado:**
```
Texto de Pregunta,Tipo,Respuesta Correcta,Opciones (separadas por |),Puntuación
¿Cuál es la capital?,multiple,Paris,Londres|Paris|Madrid,1
```

**Pasos:**
1. Ve a "Preguntas"
2. Haz clic en "Importar CSV"
3. Descarga la plantilla si es necesario
4. Completa el archivo CSV
5. Sube el archivo
6. Revisa los resultados

### Exportar Preguntas (CSV)

1. Ve a "Preguntas"
2. Haz clic en "Exportar CSV"
3. El navegador descargará todas las preguntas en formato CSV

### Crear una Pregunta Manualmente

1. Ve a "Preguntas"
2. Haz clic en "Crear Pregunta"
3. Completa:
   - **Texto**: La pregunta
   - **Tipo**: Opción múltiple, verdadero/falso, etc.
   - **Respuesta Correcta**: Selecciona la respuesta correcta
   - **Puntuación**: Puntos que vale la pregunta
   - **Certificación**: Asocia a una certificación

4. Haz clic en "Crear"

### Editar una Pregunta

1. En la lista de preguntas, haz clic en "Editar"
2. Modifica los campos deseados
3. Haz clic en "Actualizar"

---

## Gestión de Usuarios

### Crear un Usuario Manualmente

1. Ve a "Usuarios"
2. Haz clic en "Crear Usuario"
3. Completa:
   - **Nombre**: Nombre completo del usuario
   - **Contraseña**: Contraseña temporal (opcional, se genera automáticamente)
   - **Email Verificado**: Marcar si el email está verificado

4. Haz clic en "Crear"

El sistema genera automáticamente un email interno basado en el nombre del usuario.

### Importar Usuarios (CSV)

**Formato esperado:**
```
ID,Nombre,Email,Contraseña
1,Juan Pérez,juan@example.com,micontraseña
2,María García,,generada_automaticamente
3,Pedro López
```

**Campos:**
- **ID**: Número único (obligatorio)
- **Nombre**: Nombre completo (obligatorio)
- **Email**: Email único (opcional, se genera si está vacío)
- **Contraseña**: Contraseña en texto plano (opcional, se genera aleatoria)

**Pasos:**
1. Ve a "Usuarios"
2. Haz clic en "Importar CSV"
3. Descarga la plantilla actual si es necesario
4. Completa el archivo CSV
5. Sube el archivo
6. El sistema creará usuarios nuevos y actualizará los existentes

### Exportar Usuarios (CSV)

1. Ve a "Usuarios"
2. Haz clic en "Exportar CSV"
3. El navegador descargará la lista completa de usuarios

### Editar un Usuario

1. Desde la lista de usuarios, haz clic en "Editar"
2. Modifica:
   - **Nombre**: Nombre del usuario
   - **Email Verificado**: Estado de verificación
   - **Contraseña**: Nueva contraseña (dejar vacío para no cambiar)

3. Haz clic en "Actualizar"

### Eliminar un Usuario

1. Desde la lista de usuarios, haz clic en "Eliminar"
2. Confirma la eliminación

---

## Plantillas de Certificados

### Plantillas Predeterminadas

Las plantillas predeterminadas se aplican a todas las certificaciones que no tienen una plantilla personalizada.

#### Crear Plantilla Predeterminada

1. Ve a "Plantillas de Certificados"
2. Haz clic en "Crear Plantilla"
3. Completa:
   - **Slug**: Identificador único (ej: "profesional")
   - **Nombre**: Nombre descriptivo
   - **Plantilla HTML**: HTML del certificado
   - **CSS**: Estilos personalizados (opcional)
   - **Usar como Predeterminada**: Marcar para usarla por defecto

4. Haz clic en "Crear"

#### Editar Plantilla Predeterminada

1. Ve a "Plantillas de Certificados"
2. Haz clic en "Editar" en la plantilla deseada
3. Modifica el contenido
4. Haz clic en "Actualizar"

#### Variables Disponibles en Plantillas

En el HTML y CSS puedes usar estas variables que se reemplazarán automáticamente:

```
{{nombre}}              - Nombre del certificado
{{serial}}              - Número de serie único
{{fecha}}               - Fecha de emisión (formato: d/m/Y)
{{competencia}}         - Nombre de la certificación evaluada
{{nota}}                - Resultado (Aprobado/Desaprobado)
{{puntuacion}}          - Puntuación numérica
{{puntuacion_maxima}}   - Puntuación máxima posible
```

Ejemplo de uso en HTML:
```html
<h1>Certificado de {{competencia}}</h1>
<p>Otorgado a: {{nombre}}</p>
<p>Serial: {{serial}}</p>
<p>Resultado: {{nota}} ({{puntuacion}}/{{puntuacion_maxima}})</p>
```

### Plantillas por Certificación

Cada certificación puede tener su propia plantilla personalizada que anula la plantilla predeterminada.

#### Personalizar Plantilla de una Certificación

1. Desde "Certificaciones", edita la certificación deseada
2. Ve a la pestaña "Plantilla de Certificado"
3. Marca "Usar plantilla personalizada"
4. Edita el HTML y CSS
5. Haz clic en "Guardar Plantilla"

---

## Pruebas de Certificaciones

### Generar Preguntas de Prueba

Para probar una certificación sin datos reales:

1. Desde "Certificaciones", edita la certificación
2. Ve a la pestaña "Prueba"
3. Haz clic en "Generar Preguntas de Prueba"
4. El sistema creará preguntas de prueba marcadas dentro del sistema

### Acceder a la Prueba

1. Ve a `/admin/certifications/{certification-id}/test`
2. Completa el examen de prueba
3. Revisa los resultados

### Limpiar Preguntas de Prueba

1. En la misma pestaña de "Prueba"
2. Haz clic en "Limpiar Preguntas de Prueba"
3. Se eliminarán todas las preguntas marcadas como de prueba

---

## Registro de Auditoría

El registro de auditoría registra todas las acciones administrativas para fines de compliance y auditoría.

### Acceder al Registro

1. Ve a "Auditoría" en el panel de control
2. Verás una tabla con todas las acciones registradas

### Filtrar por Acción

1. Usa el dropdown "Acción" para filtrar por:
   - **Crear**: Nuevas entidades creadas
   - **Actualizar**: Entidades modificadas
   - **Eliminar**: Entidades eliminadas
   - **Importar**: Importaciones de CSV
   - **Exportar**: Exportaciones de CSV

### Filtrar por Tipo de Entidad

1. Usa el dropdown "Tipo" para filtrar por:
   - **Certification**: Acciones sobre certificaciones
   - **Question**: Acciones sobre preguntas
   - **User**: Acciones sobre usuarios
   - **CertificateTemplate**: Acciones sobre plantillas

### Ver Detalles de Cambios

1. Haz clic en "Detalles" en una fila
2. Se expandirá mostrando:
   - Cambios específicos realizados
   - Valores antiguos vs nuevos
   - IP del administrador
   - User Agent del navegador
   - Marca de tiempo exacta

### Información Registrada

Cada entrada contiene:
- **Acción**: Tipo de operación (create, update, delete, import, export)
- **Tipo**: Entidad afectada (Certification, Question, User, etc.)
- **Nombre**: Identificador/nombre de la entidad
- **IP**: Dirección IP del administrador
- **Fecha**: Marca de tiempo de la acción
- **Cambios**: Detalles JSON de lo que cambió

---

## Solución de Problemas

### No puedo iniciar sesión

**Problema**: La clave de acceso no funciona

**Solución**:
1. Verifica que `ADMIN_ACCESS_KEY` esté configurada en `.env`
2. Asegúrate de que no hay espacios al principio o final
3. Verifica la capitalización (es sensible a mayúsculas)

### Los certificados no se generan

**Problema**: Error al descargar PDF

**Solución**:
1. Verifica que la plantilla de certificado sea válida
2. Comprueba que no haya caracteres especiales sin escapar en el HTML
3. Intenta con la plantilla predeterminada primero

### Las preguntas no se importan

**Problema**: Error en la importación de CSV

**Solución**:
1. Verifica el formato del CSV:
   - Codificación UTF-8
   - Separador correcto (comas)
   - Comillas si hay comas dentro de campos
2. El encabezado debe estar en la primera fila
3. No dejes filas vacías al final del archivo

### Los usuarios importados tienen email duplicado

**Problema**: El CSV tiene usuarios con el mismo email

**Solución**:
1. Revisa el CSV para emails duplicados
2. Combine emails deben ser únicos
3. Si es intencional actualizar un usuario, asegúrate de usar el mismo email

### El registro de auditoría está vacío

**Problema**: No aparecen acciones registradas

**Solución**:
1. Verifica que la migración de auditoría se ejecutó: `php artisan migrate`
2. Las acciones solo se registran después de que se ejecute la migración
3. Realiza algunas acciones nuevas para ver si se registran

### El certificado está en el idioma equivocado

**Problema**: El PDF tiene ciertas veces en otro idioma

**Solución**:
1. Los certificados siempre se generan en inglés por diseño
2. La interfaz del usuario adopta el idioma seleccionado
3. Para cambiar el idioma de los certificados, edita la plantilla

---

## Mejores Prácticas

### Administración de Certificaciones

✅ **Hacer:**
- Usar nombres descriptivos en certificaciones
- Probar exámenes antes de activar
- Mantener un registro de cambios en el registro de auditoría
- Hacer backup regular de preguntas (exportar CSV)

❌ **No hacer:**
- Cambiar criterios de puntuación después de que usuarios empiecen a examen
- Eliminar certificaciones con datos de usuarios vivos
- Dejar contraseñas temporales sin cambiar

### Gestión de Usuarios

✅ **Hacer:**
- Verificar emails antes de enviar credenciales
- Usar contraseñas fuertes
- Revisar el registro de auditoría para cambios sospechosos
- Exportar backups periódicos de usuarios

❌ **No hacer:**
- Compartir claves de acceso del admin
- Usar contraseñas predeterminadas
- Dejar sesiones activas sin vigilancia

### Plantillas

✅ **Hacer:**
- Probar plantillas en vista previa antes de guardar
- Usar variables correctamente ({{variable}})
- Mantener CSS limpio y válido
- Crear versiones de plantillas para diferentes casos de uso

❌ **No hacer:**
- Usar JavaScript en plantillas HTML
- Referencias CSS externas (todo debe ser inline)
- HTML muy complejo que ralentice la generación

---

## Soporte Técnico

Si encuentras problemas:

1. Consulta esta documentación primero
2. Revisa el registro de auditoría para cambios recientes
3. Comprueba los logs de la aplicación (storage/logs/)
4. Contacta al equipo técnico con:
   - Descripción del problema
   - Pasos para reproducir
   - Capturas de pantalla si es relevante
   - Fecha y hora del problema

---

**Última actualización**: {{fecha_doc}}
**Versión del sistema**: 1.0
