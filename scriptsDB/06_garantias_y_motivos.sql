-- ============================================================
-- Script: 06_garantias_y_motivos.sql
-- Descripción: Añade soporte para garantías extendidas y motivos de devolución
-- ============================================================

USE `dbelectrobazar-tpv`;

-- 1. Añadir meses de garantía a las líneas de venta (captura el valor del producto en el momento de la venta)
ALTER TABLE lineas_venta 
  ADD COLUMN meses_garantia INT DEFAULT 24 AFTER cantidad;

-- 2. Añadir motivo y fecha de devolución a las líneas de venta
ALTER TABLE lineas_venta 
  ADD COLUMN motivo_devolucion VARCHAR(255) DEFAULT NULL AFTER devuelta,
  ADD COLUMN fecha_devolucion DATETIME DEFAULT NULL AFTER motivo_devolucion;
