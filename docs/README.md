# Documentación del Proyecto CertificacionHHGG

## 📚 Índice por Contexto

### 🎯 Para Administradores (Panel de Control)
**Usuario objetivo:** Administradores sin conocimientos técnicos

- **[ADMIN_PANEL_GUIDE.md](ADMIN_PANEL_GUIDE.md)** - Guía completa del panel administrativo
  - Cómo iniciar sesión
  - Crear/editar certificaciones (5-step wizard)
  - Gestionar preguntas con CSV
  - Importar/exportar usuarios
  - Personalizar plantillas de certificados
  - Revisar registro de auditoría
  - Solución de problemas comunes

### 🏗️ Para Desarrolladores (Arquitectura)
**Usuario objetivo:** Desarrolladores y arquitectos

- **[PROYECTO_DETALLADO.md](PROYECTO_DETALLADO.md)** - Documentación técnica completa
  - Resumen funcional del sistema
  - Flujo end-to-end
  - Stack tecnológico (Laravel 11, Livewire, Tailwind)
  - Estructura de directorios
  - Modelo de datos (ER básico)
  - Integración multilenguaje
  - API y rutas principales

### 💻 Para Desarrollo Local
**Usuario objetivo:** Desarrolladores en Codespaces o máquinas locales

- **[CODESPACES_PRUEBAS.md](CODESPACES_PRUEBAS.md)** - Setup y validación local
  - Instalación de dependencias
  - Preparación del entorno
  - Ejecución de migraciones
  - Levantar servidor de desarrollo
  - Ejecutar suite de tests
  - Scripts de utilidad

### 🚀 Planificación Futura (Roadmap)
**Usuario objetivo:** Product managers y líderes técnicos

- **[planificacion/PLAN_ESCALABILIDAD_CERTIFICACIONES.md](planificacion/PLAN_ESCALABILIDAD_CERTIFICACIONES.md)** - Crecimiento del catálogo
- **[planificacion/DESPLIEGUE_STAGING_PRODUCCION.md](planificacion/DESPLIEGUE_STAGING_PRODUCCION.md)** - Estrategia de deploy
- **[planificacion/POLITICA_RETENCION_DATOS.md](planificacion/POLITICA_RETENCION_DATOS.md)** - Compliance y retención
- **[planificacion/FASE_6_LIMPIEZA_TECNICA.md](planificacion/FASE_6_LIMPIEZA_TECNICA.md)** - Refactorización futura (cert_type → certification_id)

---

## 📋 Criterios de Documentación

✅ **Mantener:**
- Documentos operativos y accionables
- Guías para diferentes perfiles de usuario
- Arquitectura y decisiones técnicas registradas
- Roadmap y planes futuros

❌ **Evitar:**
- Documentos obsoletos o duplicados
- Reportes puntuales de fecha
- Instrucciones reemplazadas por UI mejorada
- Planes completados/archivados

---

## 🎓 Flujo de Lectura Recomendado

**Primer acceso (administrador no-técnico):**
1. ADMIN_PANEL_GUIDE.md → "Introducción" + "Acceso al Panel"
2. ADMIN_PANEL_GUIDE.md → Tu caso de uso específico (Certificaciones / Usuarios / etc)

**Primer acceso (desarrollador):**
1. PROYECTO_DETALLADO.md → "Resumen funcional" + "Stack técnico"
2. PROYECTO_DETALLADO.md → Secciones específicas según interés
3. CODESPACES_PRUEBAS.md → Para levantar ambiente local

**Roadmap futuro:**
1. Carpeta `planificacion/` → Planes por trimestre


