-- Tabla de tipos de IVA con vigencias por fecha
CREATE TABLE IF NOT EXISTS tipos_iva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    porcentaje DECIMAL(5,2) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_codigo_fecha (codigo, fecha_inicio)
);

-- Relación con productos: cada producto referencia un código de IVA lógico
ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS codigo_iva VARCHAR(20) NOT NULL DEFAULT 'GENERAL';

-- Carga inicial de tipos de IVA estándar (España, comercio minorista)
INSERT INTO tipos_iva (codigo, nombre, porcentaje, fecha_inicio, fecha_fin, activo) VALUES
('GENERAL', 'IVA general', 21.00, '2021-01-01', NULL, 1),
('REDUCIDO', 'IVA reducido', 10.00, '2021-01-01', NULL, 1),
('SUPERREDUCIDO', 'IVA superreducido', 4.00, '2021-01-01', NULL, 1),
('EXENTO', 'Operaciones exentas', 0.00, '2021-01-01', NULL, 1);


