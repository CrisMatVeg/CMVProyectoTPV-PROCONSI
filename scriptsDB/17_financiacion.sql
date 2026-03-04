-- ============================================================
-- Script: 17_financiacion.sql
-- Descripción: Tablas y cambios para soportar ventas financiadas mejoradas
-- ============================================================

-- 1. Ampliar los métodos de pago permitidos en la tabla ventas
ALTER TABLE ventas MODIFY COLUMN metodo_pago ENUM('efectivo', 'tarjeta', 'financiado') NOT NULL;

-- 2. Tabla de entidades financieras
CREATE TABLE IF NOT EXISTS financieras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NULL,
    comision_cero_interes DECIMAL(5,2) DEFAULT 0.00, -- Comisión si la tienda paga intereses (0%)
    comision_con_interes DECIMAL(5,2) DEFAULT 0.00,  -- Comisión si el cliente paga intereses
    min_importe DECIMAL(10,2) DEFAULT 150.00,        -- Importe mínimo para financiar
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabla de detalles de financiación para cada venta
CREATE TABLE IF NOT EXISTS ventas_financiacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_financiera INT NOT NULL,
    meses INT NOT NULL DEFAULT 12,
    cuota_mensual DECIMAL(10,2) NOT NULL,
    importe_intereses DECIMAL(10,2) DEFAULT 0.00,
    modalidad ENUM('vendedor_paga_intereses', 'cliente_paga_intereses') DEFAULT 'vendedor_paga_intereses',
    estado ENUM('pendiente', 'aprobada', 'denegada') DEFAULT 'aprobada',
    notas TEXT NULL,
    CONSTRAINT fk_finan_venta FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE,
    CONSTRAINT fk_finan_entidad FOREIGN KEY (id_financiera) REFERENCES financieras(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Datos iniciales de financieras de ejemplo
INSERT INTO financieras (nombre, descripcion, comision_cero_interes, comision_con_interes, min_importe) VALUES 
('CETELEM', 'Financiación al consumo estándar', 2.50, 0.50, 150.00),
('SANTANDER CONSUMER', 'Especialistas en financiación retail', 3.00, 0.75, 200.00),
('CAIXABANK PAY', 'Financiación rápida mediante App', 2.20, 0.40, 120.00),
('FINANCIACIÓN PROPIA', 'Gestión interna de financiación a socios', 0.00, 0.00, 50.00);
