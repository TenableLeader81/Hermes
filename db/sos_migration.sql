-- ══════════════════════════════════════════════════════
--  Migración SOS — Ejecuta cada bloque por separado en phpMyAdmin
--  Si un ALTER dice "Duplicate column" simplemente ignóralo y sigue
-- ══════════════════════════════════════════════════════

-- Paso 1: Columna descripcion
ALTER TABLE `reportes` ADD COLUMN `descripcion` TEXT NULL AFTER `subcategoria`;

-- Paso 2: Columna visibilidad
ALTER TABLE `reportes` ADD COLUMN `visibilidad` ENUM('publica','interna') NOT NULL DEFAULT 'publica' AFTER `longitud`;

-- Paso 3: Columna estado
ALTER TABLE `reportes` ADD COLUMN `estado` ENUM('pendiente','resuelto') NOT NULL DEFAULT 'pendiente' AFTER `visibilidad`;

-- Paso 4: Agregar SOS al enum de categoria
ALTER TABLE `reportes`
  MODIFY COLUMN `categoria` ENUM('Accidente','Robo','Falla electrica','SOS') NOT NULL;
