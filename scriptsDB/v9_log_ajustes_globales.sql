CREATE TABLE `log_ajustes_globales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fecha` DATETIME NOT NULL,
  `id_usuario` INT NOT NULL,
  `tipo_operacion` VARCHAR(50) NOT NULL COMMENT 'margen_masivo, ajuste_masivo',
  `valor` DECIMAL(10,2) NOT NULL,
  `tipo_valor` VARCHAR(20) NOT NULL COMMENT 'percent, amount',
  `categoria_nom` VARCHAR(100) NOT NULL DEFAULT 'Todas',
  `motivo` TEXT NOT NULL,
  `productos_afectados` INT NOT NULL DEFAULT 0,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
