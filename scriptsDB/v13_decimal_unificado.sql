-- Migración v13: Unificación de tipos DECIMAL en tabla productos
-- productos usaba DECIMAL(30,15) (overhead extremo); se normaliza a DECIMAL(15,4)
-- que ofrece suficiente precisión (hasta 99.999.999.999 con 4 decimales).
-- ventas/lineas_venta ya usan DECIMAL(10,2) y se mantienen (son totales, no precios base).

USE `dbelectrobazar-tpv`;

ALTER TABLE productos
    MODIFY COLUMN precio_coste     DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    MODIFY COLUMN precio_proveedor DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    MODIFY COLUMN precio_venta     DECIMAL(15,4) NOT NULL DEFAULT 0.0000;
