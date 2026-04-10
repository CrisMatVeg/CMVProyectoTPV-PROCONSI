USE `dbelectrobazar-tpv`;

DROP TABLE IF EXISTS `pagos_venta`;

CREATE TABLE `pagos_venta` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_venta` INT NOT NULL,
    `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `importe` DECIMAL(10,2) NOT NULL,
    `metodo_pago` VARCHAR(50) NOT NULL,
    `id_usuario` INT DEFAULT NULL,
    `notas` TEXT,
    `id_turno` INT DEFAULT NULL,
    CONSTRAINT `fk_pago_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pago_usr` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_pago_turno` FOREIGN KEY (`id_turno`) REFERENCES `caja_turnos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
