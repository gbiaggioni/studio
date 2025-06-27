-- Este script crea la tabla necesaria para la aplicación QREasy en una base de datos MariaDB/MySQL.
-- Puedes ejecutarlo usando una herramienta como phpMyAdmin o directamente desde la línea de comandos de MySQL.

CREATE TABLE IF NOT EXISTS `qr_codes` (
  `id_db` VARCHAR(36) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `url_destino` TEXT NOT NULL,
  `short_id` VARCHAR(12) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_db`),
  UNIQUE KEY `short_id_UNIQUE` (`short_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
