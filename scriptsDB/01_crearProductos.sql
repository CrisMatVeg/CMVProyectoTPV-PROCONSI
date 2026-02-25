-- Creación de la tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE, -- Código o referencia del producto
    precio DECIMAL(10, 2) NOT NULL,
    icono VARCHAR(255), -- Puede ser un emoji, una ruta de imagen o clase de icono
    categoria VARCHAR(50),
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserción de datos iniciales desde el catálogo actual
INSERT INTO productos (nombre, codigo, precio, icono, categoria) VALUES
('Auriculares BT Pro X', 'AUD-001', 89.99, '🎧', 'audio'),
('Altavoz JBL Portable', 'AUD-002', 59.99, '🔊', 'audio'),
('Auriculares In-Ear TWS', 'AUD-003', 39.99, '🎵', 'audio'),
('Barra Sonido 2.1', 'AUD-004', 129.99, '📻', 'audio'),
('Funda iPhone 15 Pro', 'MOV-001', 14.99, '📱', 'movil'),
('Protector Pantalla', 'MOV-002', 9.99, '🛡️', 'movil'),
('Soporte Coche Mag', 'MOV-003', 19.99, '🚗', 'movil'),
('Power Bank 20000mAh', 'MOV-004', 34.99, '🔋', 'movil'),
('Mando PS5 DualSense', 'GAM-001', 74.99, '🎮', 'gaming'),
('Headset Gaming RGB', 'GAM-002', 49.99, '🎯', 'gaming'),
('Mousepad XL', 'GAM-003', 14.99, '🖱️', 'gaming'),
('Tarjeta PSN 50€', 'GAM-004', 50.00, '💳', 'gaming'),
('Teclado Mecánico', 'INF-001', 79.99, '⌨️', 'informatica'),
('Ratón Inalámbrico', 'INF-002', 44.99, '🖱️', 'informatica'),
('Hub USB-C 7 en 1', 'INF-003', 34.99, '🔌', 'informatica'),
('SSD Externo 1TB', 'INF-004', 89.99, '💾', 'informatica'),
('Cable USB-C a USB-C 2m', 'CAB-001', 12.99, '🔗', 'cables'),
('Cargador GaN 65W', 'CAB-002', 29.99, '⚡', 'cables'),
('Cable Lightning 1m', 'CAB-003', 9.99, '🍎', 'cables'),
('Cargador Inalámbrico', 'CAB-004', 24.99, '🌀', 'cables'),
('Trípode Flexible 45cm', 'FOT-001', 17.99, '📷', 'foto'),
('Ring Light LED 10"', 'FOT-002', 39.99, '💡', 'foto'),
('Tarjeta SD 128GB V30', 'FOT-003', 22.99, '💿', 'foto'),
('Micrófono Condensador', 'FOT-004', 54.99, '🎙️', 'foto');
