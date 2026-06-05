-- ============================================================
-- SCRIPT: COLA DE ENVIOS AEAT (VeriFactu)
-- ============================================================

USE `dbelectrobazar-tpv`;

CREATE TABLE IF NOT EXISTS cola_envios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    xml_path VARCHAR(255) NULL,
    intentos INT DEFAULT 0,
    ultimo_error TEXT NULL,
    estado ENUM('pendiente', 'enviado', 'error_critico', 'bloqueado') DEFAULT 'pendiente',
    fecha_proximo_intento DATETIME DEFAULT CURRENT_TIMESTAMP,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cola_venta FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Añadir índice para optimizar la búsqueda de pendientes
CREATE INDEX idx_cola_estado_fecha ON cola_envios(estado, fecha_proximo_intento);
