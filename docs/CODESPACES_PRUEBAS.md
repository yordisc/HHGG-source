# Pruebas locales en Codespaces

Esta guia explica como levantar el proyecto y ejecutar pruebas dentro de GitHub Codespaces.

## Requisitos

- Codespace activo sobre este repositorio
- Terminal en la raiz del proyecto
- PHP y Composer disponibles (ya vienen en el entorno)

La configuracion del Codespace levanta MySQL local y deja los puertos 8000, 3306 y 5173 listos para Laravel, base de datos y Vite.

## 1) Instalar dependencias

```bash
sh scripts/local-test.sh
```

## 2) Preparar entorno

`scripts/local-test.sh` ya prepara `.env`, genera la APP_KEY, crea la base SQLite temporal para validacion y ejecuta migraciones y tests.

## 3) Configurar entorno local

Si quieres arrancar el stack de desarrollo de inmediato:

```bash
sh scripts/dev-local.sh --serve
```

## 4) Ejecutar migraciones y seeders

```bash
php artisan migrate:fresh --seed
```

## 5) Levantar aplicacion en desarrollo

```bash
sh scripts/dev-local.sh
```

Nota: `sh scripts/dev-local.sh` levanta servidor Laravel, cola y Vite; si quieres ejecutar validacion previa antes del arranque, usa `sh scripts/dev-local.sh --all`.

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
sh scripts/local-test.sh
```
