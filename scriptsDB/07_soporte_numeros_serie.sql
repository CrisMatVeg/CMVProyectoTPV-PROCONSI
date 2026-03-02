-- Script: 07_soporte_numeros_serie.sql
-- Añade soporte para control de números de serie por producto.

ALTER TABLE productos ADD COLUMN requiere_serial TINYINT(1) DEFAULT 0;
ALTER TABLE lineas_venta ADD COLUMN numero_serie VARCHAR(100) DEFAULT NULL;
