-- ============================================================
-- SCRIPT 11: MÁXIMA PRECISIÓN DECIMAL PARA PRODUCTOS
-- ============================================================

USE `dbelectrobazar-tpv`;

-- Aumentar precisión a 15 decimales (virtualmente ilimitado para TPV)
-- Se usa DECIMAL(30, 15) para mantener exactitud contable evitando errores de punto flotante
ALTER TABLE productos 
    MODIFY COLUMN precio_venta DECIMAL(30,15) DEFAULT 0.000000000000000,
    MODIFY COLUMN precio_coste DECIMAL(30,15) DEFAULT 0.000000000000000,
    MODIFY COLUMN precio_proveedor DECIMAL(30,15) DEFAULT 0.000000000000000;

-- También en auditoría de precios para no perder datos históricos
ALTER TABLE auditoria_precios_base
    MODIFY COLUMN precio_old DECIMAL(30,15) NOT NULL,
    MODIFY COLUMN precio_new DECIMAL(30,15) NOT NULL;
