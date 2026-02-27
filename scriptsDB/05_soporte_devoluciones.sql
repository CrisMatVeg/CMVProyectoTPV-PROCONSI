-- ============================================================
-- Script: 05_soporte_devoluciones.sql
-- Descripción: Añade soporte para estados de venta y devoluciones
-- ============================================================

USE electrobazar_tpv;

-- 1. Añadir estado a la tabla de ventas para trazar devoluciones/anulaciones
ALTER TABLE ventas 
  ADD COLUMN estado ENUM('completada', 'devuelta', 'anulada') DEFAULT 'completada' AFTER total;

-- 2. Añadir indicador de devolución a las líneas de venta
ALTER TABLE lineas_venta 
  ADD COLUMN devuelta BOOLEAN DEFAULT FALSE AFTER total_linea;
