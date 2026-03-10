-- ============================================================
-- Script: 23_mejorar_devoluciones_y_garantias.sql
-- Descripción: Soporte para saldo de clientes, métodos de reembolso
--              y actualización de plazos legales (3 años garantía).
-- ============================================================

USE `dbelectrobazar-tpv`;

-- 1. Añadir saldo a la tabla de clientes
ALTER TABLE clientes 
  ADD COLUMN saldo DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER provincia;

-- 2. Tabla para histórico de movimientos de saldo (monedero)
CREATE TABLE IF NOT EXISTS clientes_saldos_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    importe DECIMAL(10,2) NOT NULL, -- Positivo (abono) o negativo (cargo)
    concepto VARCHAR(255) NOT NULL,
    id_linea_venta INT NULL, -- Referencia opcional si viene de una devolución
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_saldo_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Añadir método de reembolso a las líneas de venta
ALTER TABLE lineas_venta 
  ADD COLUMN metodo_reembolso ENUM('efectivo', 'saldo_cliente', 'reemplazo', 'otros') NULL DEFAULT NULL AFTER fecha_devolucion;

-- 4. Actualizar plazos de garantía por defecto a 3 años (36 meses)
-- Para productos nuevos o futuros
ALTER TABLE productos MODIFY COLUMN meses_garantia INT DEFAULT 36;

-- Actualizar productos existentes que estuvieran en el mínimo anterior (24)
UPDATE productos SET meses_garantia = 36 WHERE meses_garantia = 24;

-- Asegurar que las líneas de venta futuras capturen 36 meses si no se especifica
ALTER TABLE lineas_venta MODIFY COLUMN meses_garantia INT DEFAULT 36;
