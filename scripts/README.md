# Scripts para Crear Certificaciones

Este directorio contiene herramientas para crear nuevas certificaciones/cursos de forma fácil y segura.

## 🚀 Métodos Disponibles

### 1. **Artisan Command (Recomendado)**

El método más flexible y recomendado.

#### Modo Interactivo:

```bash
php artisan certification:create --interactive
```

#### Modo Rápido:

```bash
php artisan certification:create \
  --slug=marketing_101 \
  --name="Marketing 101" \
  --questions=30 \
  --pass-score=66.67 \
  --cooldown=30
```

**Ventajas:**

- ✅ Validación en tiempo real
- ✅ Interfaz amigable
- ✅ Preguntas interactivas
- ✅ Resumen antes de crear
- ✅ Integración nativa con Laravel

---

### 2. **Script Bash**

Para usuarios avanzados que quieren máximo control.

```bash
bash create-certification.sh
```

**Requisitos:**

- Bash 4+
- bc (para cálculos)
- php artisan disponible

**Características:**

- Interfaz completamente interactiva
- Validación de datos robusta
- Menú de edición antes de guardar
- Carga automática de preguntas
- Colores y formatos legibles

---

### 3. **Seeders**

Para desarrollo programado o batch de certificaciones.

#### Crear un seeder:

```bash
cp database/seeders/CertificationSeederTemplate.php \
   database/seeders/MarketingCertificationSeeder.php
```

#### Editar y ejecutar:

```bash
php artisan db:seed --class=MarketingCertificationSeeder
```

**Ventajas:**

- Versionable en git
- Reproducible
- Ideal para desarrollo en equipo
- Documentable

---

### 4. **CSV a Seeder**

Si tienes preguntas en un archivo CSV.

```bash
php scripts/csv-to-seeder.php marketing_101 database/templates/questions-example.csv
```

---

## 📋 Resumen de Archivos

| Archivo                                               | Descripción             |
| ----------------------------------------------------- | ----------------------- |
| `create-certification.sh`                             | Script bash interactivo |
| `database/templates/questions-example.csv`            | Ejemplo de formato CSV  |
| `database/seeders/CertificationSeederTemplate.php`    | Template para seeders   |
| `app/Console/Commands/CreateCertificationCommand.php` | Comando Artisan         |
| `scripts/README.md`                                   | Documentación completa  |

---

## ⚡ Inicio Rápido

### Opción A: La más fácil

```bash
php artisan certification:create --interactive
```

### Opción B: Si tienes datos listos

```bash
php artisan certification:create \
  --slug=my_course \
  --name="My Course Title" \
  --questions=30
```

### Opción C: Desde bash

```bash
bash scripts/create-certification.sh
```

### Opción D: Desde CSV

```bash
# 1. Prepara tu CSV
# 2. Convierte a seeder
php scripts/csv-to-seeder.php my_course ./data.csv

# 3. Ejecuta
php artisan db:seed --class=MyCourseSeeder
```

---

## 📚 Documentación Completa

Para más detalles, parámetros, ejemplos y solución de problemas, este mismo README es la referencia principal.

---

## ✅ Checklist

Antes de crear una certificación, asegúrate de tener:

- [ ] Slug único (ej: `marketing_101`)
- [ ] Nombre descriptivo (ej: `Marketing 101`)
- [ ] Cantidad de preguntas (mínimo 30 recomendado)
- [ ] Las preguntas con sus 4 opciones cada una
- [ ] La opción correcta para cada pregunta

---

## 🆘 Ayuda

### Ver logs:

```bash
tail -f storage/logs/laravel.log
```

### Verificar certificaciones existentes:

```bash
php artisan tinker
>>> \App\Models\Certification::all()
```

### Probar certificación creada:

1. Visita home: http://localhost:8000
2. Verifica que aparezca tu certificación
3. Haz clic para probar el flujo

---

## 🔧 Requisitos del Sistema

- PHP 8.4+
- Laravel 11
- Base de datos configurada
- Comando `php artisan` funcionando

---

## 📞 Contacto

Si tienes problemas:

1. Revisa `storage/logs/laravel.log`
2. Consulta este README
3. Verifica que la BD está accesible: `php artisan tinker`

---

**Última actualización:** April 2026
