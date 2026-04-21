-- Migración para facturas pendientes y vencimientos automáticos
-- 1. Añadir columna vencimiento_dias a la tabla proveedores
ALTER TABLE proveedores ADD COLUMN vencimiento_dias INT DEFAULT 0;

-- 2. Añadir columna fecha_vencimiento a la tabla facturas_compra_prov
ALTER TABLE facturas_compra_prov ADD COLUMN fecha_vencimiento DATE;

-- 3. Inicializar fecha_vencimiento para facturas existentes
UPDATE facturas_compra_prov SET fecha_vencimiento = fecha_factura WHERE fecha_vencimiento IS NULL;
