-- ============================================================
-- Script: MIGRACION_TOTAL_PROFESIONAL.sql
-- Descripción: Script consolidado para actualizar la base de datos
-- a la versión profesional con soporte de devoluciones.
-- ============================================================

USE electrobazar_tpv;

-- 1. ACTUALIZACIÓN DE USUARIOS
-- Renombra nombre_completo a nombre y username a login
ALTER TABLE usuarios 
  CHANGE COLUMN nombre_completo nombre VARCHAR(100),
  CHANGE COLUMN username login VARCHAR(50),
  MODIFY COLUMN rol ENUM('admin', 'cajero') DEFAULT 'cajero';

-- 2. ACTUALIZACIÓN DE PRODUCTOS
-- Soporte para referencias, IVA, stock y variantes
ALTER TABLE productos
  CHANGE COLUMN codigo referencia VARCHAR(50),
  ADD COLUMN descripcion TEXT AFTER nombre,
  ADD COLUMN precio_coste DECIMAL(10,2) DEFAULT 0.00 AFTER descripcion,
  ADD COLUMN precio_venta DECIMAL(10,2) DEFAULT 0.00 AFTER precio_coste,
  ADD COLUMN iva DECIMAL(5,2) DEFAULT 21.00 AFTER precio_venta,
  ADD COLUMN stock_actual INT DEFAULT 0 AFTER iva,
  ADD COLUMN stock_minimo INT DEFAULT 0 AFTER stock_actual,
  ADD COLUMN meses_garantia INT DEFAULT 24 AFTER stock_minimo,
  ADD COLUMN variantes JSON DEFAULT NULL AFTER meses_garantia;

-- Migración de datos de precios si existen
UPDATE productos SET precio_venta = precio;

-- 3. TABLA DE NÚMEROS DE SERIE
CREATE TABLE IF NOT EXISTS numeros_serie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    numero_serie VARCHAR(100) NOT NULL UNIQUE,
    estado ENUM('disponible', 'vendido', 'garantia') DEFAULT 'disponible',
    CONSTRAINT fk_ns_producto FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. ACTUALIZACIÓN DE VENTAS
-- Añade fecha, id_usuario y estado para devoluciones
ALTER TABLE ventas
    ADD COLUMN fecha DATETIME DEFAULT CURRENT_TIMESTAMP AFTER id,
    ADD COLUMN id_usuario INT AFTER fecha,
    ADD COLUMN estado ENUM('completada', 'devuelta', 'anulada') DEFAULT 'completada' AFTER total,
    ADD COLUMN num_z INT DEFAULT NULL AFTER estado,
    MODIFY COLUMN metodo_pago ENUM('efectivo', 'tarjeta', 'bizum', 'financiado') NOT NULL;

-- Vinculamos cajero antiguo al nuevo campo de usuario
UPDATE ventas SET id_usuario = id_cajero;
ALTER TABLE ventas ADD CONSTRAINT fk_ventas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL;

-- 5. ACTUALIZACIÓN DE LINEAS DE VENTA
ALTER TABLE lineas_venta
    ADD COLUMN iva_aplicado DECIMAL(5,2) DEFAULT 21.00 AFTER precio_unitario,
    ADD COLUMN devuelta BOOLEAN DEFAULT FALSE AFTER total_linea;

-- 6. TABLA DE CONTROL DE SERIES EN VENTA
CREATE TABLE IF NOT EXISTS lineas_serie_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_linea_venta INT NOT NULL,
    id_numero_serie INT NOT NULL,
    CONSTRAINT fk_lsv_linea FOREIGN KEY (id_linea_venta) REFERENCES lineas_venta(id) ON DELETE CASCADE,
    CONSTRAINT fk_lsv_ns FOREIGN KEY (id_numero_serie) REFERENCES numeros_serie(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. CIERRES FISCALES
CREATE TABLE IF NOT EXISTS cierres_fiscales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT NOT NULL,
    total_efectivo DECIMAL(10,2) DEFAULT 0.00,
    total_tarjeta DECIMAL(10,2) DEFAULT 0.00,
    total_general DECIMAL(10,2) DEFAULT 0.00,
    CONSTRAINT fk_cf_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. CLIENTES Y SOCIOS
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nif VARCHAR(20) UNIQUE,
    email VARCHAR(100),
    telefono VARCHAR(20),
    es_socio BOOLEAN DEFAULT FALSE,
    fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Relación de clientes en ventas
ALTER TABLE ventas ADD COLUMN id_cliente INT DEFAULT NULL AFTER id_usuario;
ALTER TABLE ventas ADD CONSTRAINT fk_ventas_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL;
