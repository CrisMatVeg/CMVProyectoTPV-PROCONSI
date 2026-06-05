-- ============================================================
-- SEED 03: PROVEEDORES Y ROLES DE CLIENTE
-- ============================================================
USE `electrobazar`;
SET NAMES 'utf8mb4';

-- 5 PROVEEDORES (empresas de distribución de electrónica)
INSERT INTO proveedores (id, cif_nif, nombre, direccion, telefono, email, aplica_re, activo, condiciones_pago, plazo_entrega, vencimiento_dias) VALUES
(1, 'B28123456', 'Samsung Iberia S.L.',             'Calle Arturo Soria 125, 28043 Madrid',        '+34 91 546 00 00', 'pedidos@samsung-iberia.es',       0, 1, 'Transferencia 30 días', '5-7 días hábiles', 30),
(2, 'B28234567', 'LG Electronics España S.L.',      'Av. de la Industria 14, 28108 Alcobendas',    '+34 91 727 55 00', 'distribucion@lg-spain.es',        0, 1, 'Transferencia 60 días', '3-5 días hábiles', 60),
(3, 'A28345678', 'Philips Ibérica S.A.U.',           'Carretera de la Coruña km 23, 28230 Madrid',  '+34 91 516 20 00', 'ventas@philips-iberica.es',        0, 1, 'Confirming 45 días',    '7-10 días hábiles', 45),
(4, 'A28456789', 'Sony España S.A.',                 'Av. de Burgos 12, 28036 Madrid',              '+34 91 383 30 00', 'pedidos@sony-espana.es',           0, 1, 'Transferencia 30 días', '4-6 días hábiles', 30),
(5, 'B28567890', 'Huawei Technologies España S.L.', 'Calle Ribera del Loira 8, 28042 Madrid',      '+34 91 418 70 50', 'b2b@huawei-espana.es',             0, 1, 'Confirming 60 días',    '5-8 días hábiles', 60);

-- ROLES DE CLIENTE
INSERT INTO roles_cliente (id, nombre) VALUES
(1, 'particular'),
(2, 'vip'),
(3, 'mayorista');
