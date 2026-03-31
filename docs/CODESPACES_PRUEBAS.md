# Pruebas locales en Codespaces

Esta guia explica como levantar el proyecto y ejecutar pruebas dentro de GitHub Codespaces.

## Requisitos

- Codespace activo sobre este repositorio
- Terminal en la raiz del proyecto
- PHP y Composer disponibles (ya vienen en el entorno)

## 1) Instalar dependencias

```bash
composer install
npm install
```

## 2) Preparar entorno

```bash
cp .env.example .env
php artisan key:generate
```

## 3) Configurar DB local simple con SQLite (recomendado en Codespaces)

Crear archivo de base de datos:

```bash
touch database/database.sqlite
```

Editar `.env` y usar:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/workspaces/CertificacionHHGG-source/database/database.sqlite
```

## 4) Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

## 5) Levantar aplicacion en desarrollo

```bash
composer run dev
```

Nota: esto inicia servidor Laravel, cola, logs y Vite.

## 6) Ejecutar pruebas

Correr toda la suite:

```bash
php artisan test
```

Variantes utiles:

```bash
php artisan test tests/Feature
php artisan test tests/Unit
php artisan test --filter NombreDelTest
```

## 7) Limpiar estado si algo falla por cache o migraciones

```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
```

## Atajo: todo en bloque

```bash
composer install && npm install \
&& cp .env.example .env \
&& php artisan key:generate \
&& touch database/database.sqlite \
&& php artisan migrate --seed \
&& php artisan test
```
