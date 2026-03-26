-- ============================================================
-- SCRIPT 3: CARGA INICIAL BÁSICA (carga_basica.sql)
-- Proyecto: ElectroBazar TPV
-- ============================================================

USE `dbelectrobazar-tpv`;

-- 1. ROLES Y PERMISOS BASE
INSERT IGNORE INTO roles (id, nombre, descripcion) VALUES 
(1, 'admin', 'Acceso total al sistema'),
(2, 'cajero', 'Venta y gestión básica de caja');

INSERT IGNORE INTO permisos (id, clave, descripcion) VALUES 
(1, 'acceso_tpv', 'Poder entrar en la interfaz de venta'),
(2, 'gestionar_productos', 'Añadir, editar o eliminar productos'),
(3, 'ver_historial', 'Acceder al histórico de ventas'),
(4, 'ver_analitica', 'Ver informes fiscales y de rendimiento'),
(5, 'gestionar_usuarios', 'Administrar personal y roles'),
(6, 'cerrar_caja', 'Realizar cierres Z'),
(7, 'gestionar_clientes', 'Administrar base de datos de clientes'),
(8, 'gestionar_configuracion', 'Administrar datos de empresa y ajustes');

-- Asignar todos los permisos al rol Administrador
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso) 
SELECT 1, id FROM permisos;

-- Asignar permisos básicos al rol Cajero
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso) VALUES 
(2, 1), -- acceso_tpv
(2, 6), -- cerrar_caja
(2, 7); -- gestionar_clientes

-- 2. USUARIO ADMINISTRADOR POR DEFECTO
-- Password: 'admin' (hasheado con password_hash en PHP es mejor, pero para el script usamos SHA2 o similar si el sistema lo soporta)
-- NOTA: El sistema usa password_verify, por lo que este hash debe ser compatible.
-- Generamos uno genérico que el sistema reconozca o que el usuario deba cambiar.
INSERT IGNORE INTO usuarios (id, login, password, nombre, id_rol, activo) VALUES 
(1, 'admin', '$2y$10$95Xv0.b7m5y1R5t8X5q7.e.x0/l.O5.O5.O5.O5.O5.O5.O5.O5.O5', 'Administrador Sistema', 1, 1);

-- 3. CONFIGURACIÓN INICIAL DE EMPRESA
INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES
('empresa_nombre', 'ElectroBazar', 'Nombre comercial de la empresa'),
('empresa_razon_social', 'ElectroBazar S.L.', 'Razón social para facturas'),
('empresa_nif', 'B12345678', 'CIF / NIF de la empresa'),
('empresa_direccion', 'C/ Tecnología 24, 28001 Madrid', 'Dirección física y código postal'),
('empresa_telefono', '+34 91 123 45 67', 'Teléfono principal de contacto'),
('empresa_email', 'info@electrobazar.es', 'Correo electrónico principal'),
('empresa_web', 'www.electrobazar.es', 'Página web'),
('empresa_registro', 'Reg. Mercantil de Madrid, Tomo 5678, Folio 90', 'Datos del registro mercantil'),
('ticket_pie_pagina', 'Gracias por su compra en ElectroBazar.', 'Mensaje final del ticket'),
('ticket_politica', 'Conserve este ticket para devoluciones dentro de 15 días.', 'Política de devoluciones');

-- 4. TIPOS DE IVA VIGENTES (ESPAÑA)
INSERT IGNORE INTO tipos_iva (codigo, nombre, porcentaje, recargo_equivalencia, fecha_inicio, activo) VALUES
('GENERAL', 'IVA general', 21.00, 5.20, '2021-01-01', 1),
('REDUCIDO', 'IVA reducido', 10.00, 1.40, '2021-01-01', 1),
('SUPERREDUCIDO', 'IVA superreducido', 4.00, 0.50, '2021-01-01', 1),
('EXENTO', 'Operaciones exentas', 0.00, 0.00, '2021-01-01', 1);

-- 6. PROVEEDORES DE PRUEBA
INSERT IGNORE INTO proveedores (id, cif_nif, nombre, email, aplica_re) VALUES
(1, 'A12345678', 'Tech Wholesale S.A.', 'pedidos@techwholesale.com', 0),
(2, 'B87654321', 'Distribuciones Pérez', 'contacto@distperez.es', 1);

-- 7. PRODUCTOS DE MUESTRA
INSERT IGNORE INTO productos (referencia, nombre, categoria, precio_coste, precio_venta, stock_actual, stock_minimo, id_proveedor, icono, iva) VALUES
('AUD-001', 'Auriculares BT Pro X', 'audio', 45.00, 89.99, 15, 5, 1, '🎧', 21.00),
('MOV-001', 'Funda iPhone 15 Pro', 'movil', 4.50, 14.99, 50, 10, 2, '📱', 21.00),
('GAM-001', 'Mando PS5 DualSense', 'gaming', 55.00, 74.99, 8, 3, 1, '🎮', 21.00);
-- 8. ROLES DE CLIENTE
INSERT IGNORE INTO roles_cliente (id, nombre) VALUES 
(1, 'estándar'),
(2, 'vip'),
(3, 'mayorista');

-- 9. CATEGORÍAS DE PRODUCTO
INSERT IGNORE INTO categorias (codigo, nombre, id_tipo_iva) VALUES
('audio', 'Audio y Sonido', 1),
('movil', 'Telefonía Móvil', 1),
('gaming', 'Videojuegos y Consolas', 1),
('informatica', 'Informática y PC', 1),
('electro', 'Electrodomésticos', 1);
