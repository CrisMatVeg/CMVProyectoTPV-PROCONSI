-- ============================================================
-- Script: 25_refactor_re_y_stock.sql
-- Descripción: Quita el recargo de equivalencia individual por producto.
--              (Se usará solo el del proveedor).
-- ============================================================

USE `dbelectrobazar-tpv`;

-- 1. Eliminar columna aplica_re de productos
ALTER TABLE productos DROP COLUMN IF EXISTS aplica_re;
