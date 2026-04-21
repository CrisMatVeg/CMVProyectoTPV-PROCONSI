-- CONSOLIDADO DE CORRECCIONES DE ESQUEMA (EJECUTAR UNA VEZ)
-- Este script reemplaza las "auto-migraciones" que causaban commits implícitos en runtime.

-- 1. Tabla: ventas (Nuevas columnas de lógica de negocio y puntos)
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS efectivo_recibido DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS puntos_ganados INT DEFAULT 0;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS puntos_canjeados INT DEFAULT 0;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS puntos_descuento_amt DECIMAL(10,2) DEFAULT 0.00;

-- 2. Tabla: pagos_venta (Turno asociado)
ALTER TABLE pagos_venta ADD COLUMN IF NOT EXISTS id_turno INT DEFAULT NULL;

-- 3. Tabla: productos (Proveedores, IVA y Márgenes)
ALTER TABLE productos ADD COLUMN IF NOT EXISTS id_proveedor INT DEFAULT NULL;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS codigo_iva VARCHAR(50) DEFAULT 'GENERAL';
ALTER TABLE productos ADD COLUMN IF NOT EXISTS id_tipo_iva INT DEFAULT NULL;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS margen DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_proveedor DECIMAL(10,4) DEFAULT 0.0000;

-- 4. Tabla: entradas_stock (Creación si no existe)
CREATE TABLE IF NOT EXISTS entradas_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_coste DECIMAL(10,4) NOT NULL DEFAULT 0,
    cmp_anterior DECIMAL(10,4) NOT NULL DEFAULT 0,
    cmp_resultante DECIMAL(10,4) NOT NULL DEFAULT 0,
    stock_anterior INT NOT NULL DEFAULT 0,
    stock_nuevo INT NOT NULL DEFAULT 0,
    id_usuario INT DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_producto (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Optimización de Índices
ALTER TABLE ventas ADD INDEX IF NOT EXISTS idx_ventas_fecha (fecha);
ALTER TABLE ventas ADD INDEX IF NOT EXISTS idx_ventas_perf (estado, metodo_pago, fecha);
ALTER TABLE productos ADD INDEX IF NOT EXISTS idx_prod_cat_ref (categoria, referencia);
