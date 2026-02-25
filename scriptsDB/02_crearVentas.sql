-- ============================================================
-- Script: 02_crearVentas.sql
-- Descripción: Tablas de ventas y líneas de venta
-- ============================================================

-- Tabla principal de ventas (un registro por cobro)
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_ticket INT NOT NULL UNIQUE,              -- número de ticket correlativo
    tipo_cliente ENUM('particular','empresa') DEFAULT 'particular',
    nombre_cliente VARCHAR(100) DEFAULT NULL,        -- obligatorio si es empresa
    nif_cliente VARCHAR(20) DEFAULT NULL,            -- CIF/NIF si es empresa
    metodo_pago ENUM('efectivo','tarjeta') NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    descuento_pct DECIMAL(5,2) DEFAULT 0,
    descuento_amt DECIMAL(10,2) DEFAULT 0,
    base_imponible DECIMAL(10,2) NOT NULL,
    iva_pct DECIMAL(5,2) DEFAULT 21.00,
    iva_amt DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    id_cajero INT DEFAULT NULL,                     -- FK a usuarios.id
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cajero FOREIGN KEY (id_cajero) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de líneas de venta (un registro por producto dentro de cada venta)
CREATE TABLE IF NOT EXISTS lineas_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT DEFAULT NULL,                   -- puede ser NULL si el producto fue borrado
    nombre_producto VARCHAR(100) NOT NULL,           -- copia histórica del nombre
    codigo_producto VARCHAR(50) NOT NULL,            -- copia histórica del código
    precio_unitario DECIMAL(10,2) NOT NULL,
    cantidad INT NOT NULL,
    total_linea DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_venta FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE,
    CONSTRAINT fk_producto FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
