-- v15: índices de rendimiento para consultas frecuentes
-- Ejecutar una vez. Usa IF NOT EXISTS para que sea idempotente.

-- lineas_venta.id_producto: usado en reportes de venta, analitica y stock
ALTER TABLE lineas_venta
    ADD INDEX IF NOT EXISTS idx_lv_id_producto (id_producto);

-- clientes.nif: búsquedas por NIF / DNI / NIE en el TPV
ALTER TABLE clientes
    ADD INDEX IF NOT EXISTS idx_clientes_nif (nif);

-- productos.referencia: búsqueda por código de referencia / escáner
ALTER TABLE productos
    ADD INDEX IF NOT EXISTS idx_productos_referencia (referencia);

-- ventas.numero_ticket: búsqueda de tickets en historial
ALTER TABLE ventas
    ADD INDEX IF NOT EXISTS idx_ventas_numero_ticket (numero_ticket);

-- promociones(codigo, activo): validación de cupones en cada venta
ALTER TABLE promociones
    ADD INDEX IF NOT EXISTS idx_promo_codigo_activo (codigo, activo);

-- tarifa_productos(id_producto): resolución de tarifas por producto (corrige N+1 en PriceEngine)
ALTER TABLE tarifa_productos
    ADD INDEX IF NOT EXISTS idx_tp_id_producto (id_producto);
