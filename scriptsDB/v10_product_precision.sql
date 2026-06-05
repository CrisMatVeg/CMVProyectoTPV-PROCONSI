-- ============================================================
-- SCRIPT 10: ALTA PRECISIÓN DECIMAL PARA PRODUCTOS
-- ============================================================

USE `dbelectrobazar-tpv`;

-- Aumentar precisión de precios para permitir hasta 6 decimales si es necesario
ALTER TABLE productos 
    MODIFY COLUMN precio_venta DECIMAL(12,6) DEFAULT 0.000000,
    MODIFY COLUMN precio_coste DECIMAL(12,6) DEFAULT 0.000000;

-- Añadir bandera para mantener la precisión o redondear (default redondear a 2)
ALTER TABLE productos 
    ADD COLUMN mantener_precision TINYINT(1) NOT NULL DEFAULT 0;

-- También en auditoría de precios para no perder datos históricos precisos
ALTER TABLE auditoria_precios_base
    MODIFY COLUMN precio_old DECIMAL(12,6) NOT NULL,
    MODIFY COLUMN precio_new DECIMAL(12,6) NOT NULL;
