-- Limpiar todas las tablas para resetear los datos
TRUNCATE TABLE question_translations;
TRUNCATE TABLE questions;
TRUNCATE TABLE certificates;
TRUNCATE TABLE rate_limits;

-- Reiniciar auto_increment
ALTER TABLE questions AUTO_INCREMENT = 1;
ALTER TABLE question_translations AUTO_INCREMENT = 1;
ALTER TABLE certificates AUTO_INCREMENT = 1;
ALTER TABLE rate_limits AUTO_INCREMENT = 1;
