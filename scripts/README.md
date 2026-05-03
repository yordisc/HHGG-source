# Scripts de Operacion

Este directorio contiene herramientas para crear certificaciones, gestionar administradores y automatizar flujos locales de desarrollo y validacion.

## 🚀 Scripts Disponibles

### 1. Gestion de admins

Script recomendado para altas, cambios y bajas de administradores.

```bash
bash scripts/manage-admin.sh --help
```

Acciones soportadas:

- `add`: crea un admin o convierte un usuario existente en admin.
- `update`: actualiza nombre, correo y/o password de un admin.
- `delete`: quita rol admin sin borrar usuario.
- `delete --hard`: elimina el usuario por completo.
- `sync-env`: crea/actualiza admin principal desde `ADMIN_PRIMARY_*` en `.env`.

Ejemplos:

```bash
bash scripts/manage-admin.sh add --email yordiscujar@gmail.com --name "Yordis" --password "1234567890"
bash scripts/manage-admin.sh update --email admin@miempresa.com --name "Admin Ops"
bash scripts/manage-admin.sh delete --email admin@miempresa.com
bash scripts/manage-admin.sh delete --email admin@miempresa.com --hard --force
bash scripts/manage-admin.sh sync-env --force
```

---

### 2. Crear certificaciones (Artisan)

Metodo flexible y recomendado.

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

### 3. Crear certificaciones (Bash)

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

### 4. CSV a Seeder

Si tienes preguntas en un archivo CSV.

```bash
php scripts/csv-to-seeder.php marketing_101 database/templates/questions-example.csv
```

---

## 📋 Resumen de Archivos

| Archivo                   | Descripcion                             |
| ------------------------- | --------------------------------------- |
| `manage-admin.sh`         | Gestion de admins (add/update/delete)   |
| `create-certification.sh` | Creacion interactiva de certificaciones |
| `dev-local.sh`            | Arranque local (app + cola + frontend)  |
| `local-test.sh`           | Preparacion local + suite de pruebas    |
| `setup-local.sh`          | Bootstrap del entorno local             |
| `profile-serving.sh`      | Perfilado de latencia y serving         |
| `scripts/README.md`       | Documentacion de scripts                |

---

## ⚡ Inicio Rapido

### Opcion A: Crear certificacion (rapido)

```bash
php artisan certification:create --interactive
```

### Opcion B: Crear certificacion con parametros

```bash
php artisan certification:create \
  --slug=my_course \
  --name="My Course Title" \
  --questions=30
```

### Opcion C: Crear certificacion desde bash

```bash
bash scripts/create-certification.sh
```

### Opcion D: Gestionar admins

```bash
bash scripts/manage-admin.sh --help
```

### Opcion E: Desde CSV

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

Si vas a gestionar admins:

- [ ] Definir correo institucional
- [ ] Usar password fuerte (minimo 6, recomendado 12+)
- [ ] Confirmar si quieres revocar rol (`delete`) o eliminar cuenta (`delete --hard`)

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

### Verificar admins existentes:

```bash
php artisan tinker
>>> \App\Models\User::query()->where('is_admin', true)->get(['id','name','email'])
```

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
