USE `dbelectrobazar-tpv`;

CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT,
    descripcion VARCHAR(255)
);

-- Insertar valores por defecto (si no existen)
INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES
('empresa_nombre', 'ElectroBazar', 'Nombre comercial de la empresa'),
('empresa_razon_social', 'ElectroBazar S.L.', 'Razón social para facturas'),
('empresa_nif', 'B12345678', 'CIF / NIF de la empresa'),
('empresa_direccion', 'C/ Tecnología 24, 28001 Madrid', 'Dirección física y código postal'),
('empresa_telefono', '+34 91 123 45 67', 'Teléfono principal de contacto'),
('empresa_email', 'info@electrobazar.es', 'Correo electrónico principal'),
('empresa_web', 'www.electrobazar.es', 'Página web'),
('empresa_registro', 'Reg. Mercantil de Madrid, Tomo 5678, Folio 90', 'Datos del registro mercantil (para facturas)'),
('social_instagram', '@electrobazar', 'Usuario de Instagram'),
('social_facebook', '/electrobazar', 'Usuario de Facebook'),
('ticket_pie_pagina', 'Gracias por su compra en ElectroBazar.', 'Mensaje final del ticket'),
('ticket_politica', 'Conserve este ticket para devoluciones dentro de 15 días.', 'Política de devoluciones para el ticket');
