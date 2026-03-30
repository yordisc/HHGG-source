-- ─────────────────────────────────────────────────────────────────────────────
-- Script de inicialización de MySQL para desarrollo local
-- Se ejecuta automáticamente cuando el contenedor MySQL arranca por primera vez
-- ─────────────────────────────────────────────────────────────────────────────

-- Crear base de datos de desarrollo (ya creada por MYSQL_DATABASE, pero por seguridad)
CREATE DATABASE IF NOT EXISTS `certificados_dev`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Crear base de datos de pruebas (para PHPUnit)
CREATE DATABASE IF NOT EXISTS `certificados_test`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Dar permisos al usuario laravel en ambas bases de datos
GRANT ALL PRIVILEGES ON `certificados_dev`.* TO 'laravel'@'%';
GRANT ALL PRIVILEGES ON `certificados_test`.* TO 'laravel'@'%';

FLUSH PRIVILEGES;
