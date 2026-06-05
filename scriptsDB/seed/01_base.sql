-- ============================================================
-- SEED 01: ROLES, PERMISOS, CONFIGURACIÓN, CORRELATIVOS
-- ============================================================
USE `electrobazar`;

-- ROLES
INSERT INTO roles (id, nombre, descripcion) VALUES
(1, 'admin',  'Acceso total al sistema'),
(2, 'cajero', 'Venta y gestión básica de caja');

-- PERMISOS
INSERT INTO permisos (id, clave, descripcion) VALUES
(1,  'acceso_tpv',             'Poder entrar en la interfaz de venta'),
(2,  'gestionar_productos',    'Añadir, editar o eliminar productos'),
(3,  'ver_historial',          'Acceder al histórico de ventas'),
(4,  'ver_analitica',          'Ver informes fiscales y de rendimiento'),
(5,  'gestionar_usuarios',     'Administrar personal y roles'),
(6,  'cerrar_caja',            'Realizar cierres Z'),
(7,  'gestionar_clientes',     'Administrar base de datos de clientes'),
(8,  'gestionar_configuracion','Administrar datos de empresa y ajustes'),
(9,  'gestionar_inventario',   'Realizar entradas de stock y albaranes'),
(10, 'gestionar_proveedores',  'Administrar la base de datos de proveedores'),
(11, 'gestionar_tarifas',      'Administrar tarifas de precios'),
(12, 'gestionar_iva',          'Administrar tipos de IVA'),
(13, 'gestionar_promociones',  'Administrar promociones y descuentos'),
(14, 'cerrar_turno',           'Permitir cerrar turnos de caja'),
(15, 'aplicar_descuentos',     'Aplicar descuentos manuales en el TPV'),
(16, 'gestionar_devoluciones', 'Realizar abonos y devoluciones'),
(17, 'anular_ventas',          'Anular tickets o líneas de venta'),
(18, 'ver_costes',             'Visualizar precios de coste y márgenes'),
(19, 'gestionar_gastos',       'Registrar retiradas de efectivo y gastos de caja');

-- ROL_PERMISOS: admin obtiene todos los permisos
INSERT INTO rol_permisos (id_rol, id_permiso) SELECT 1, id FROM permisos;

-- ROL_PERMISOS: cajero obtiene permisos operativos básicos
INSERT INTO rol_permisos (id_rol, id_permiso) VALUES
(2, 1),  -- acceso_tpv
(2, 3),  -- ver_historial
(2, 6),  -- cerrar_caja
(2, 7),  -- gestionar_clientes
(2, 14), -- cerrar_turno
(2, 15), -- aplicar_descuentos
(2, 16), -- gestionar_devoluciones
(2, 17); -- anular_ventas

-- CONFIGURACIÓN (datos de empresa reales)
INSERT INTO `configuracion` (`id`, `clave`, `valor`, `descripcion`) VALUES
(1,  'empresa_nombre',             'ElectroBazar',                                          'Nombre comercial de la empresa'),
(2,  'empresa_razon_social',       'ElectroBazar S.L.',                                     'Razón social para facturas'),
(3,  'empresa_nif',                '99999910G',                                              'CIF / NIF de la empresa'),
(4,  'empresa_direccion',          'C/ Tecnología 24, 28001 Madrid',                        'Dirección física y código postal'),
(5,  'empresa_telefono',           '+34 91 123 45 67',                                      'Teléfono principal de contacto'),
(6,  'empresa_email',              'info@electrobazar.es',                                   'Correo electrónico principal'),
(7,  'empresa_web',                'www.electrobazar.es',                                   'Página web'),
(8,  'empresa_registro',           'Reg. Mercantil de Madrid, Tomo 5678, Folio 90',         'Datos del registro mercantil'),
(9,  'ticket_pie_pagina',          'Gracias por su compra en ElectroBazar.',                'Mensaje final del ticket'),
(10, 'ticket_politica',            'Conserve este ticket para devoluciones dentro de 15 días.', 'Política de devoluciones'),
(19, 'social_instagram',           '',                                                       'Instagram de la empresa'),
(20, 'social_facebook',            'electrobazar.es',                                       'Facebook de la empresa'),
(23, 'smtp_host',                  'smtp.gmail.com',                                        'Servidor SMTP'),
(24, 'smtp_port',                  '587',                                                   'Puerto SMTP'),
(25, 'smtp_user',                  'cristian.matveg@gmail.com',                             'Usuario SMTP'),
(26, 'smtp_pass',                  'ggiiaglwpgpppjdw',                                      'Contraseña SMTP'),
(27, 'smtp_secure',                'tls',                                                   'Protocolo seguro SMTP'),
(28, 'empresa_aplica_re',          '1',                                                     'Aplica recargo de equivalencia'),
(47, 'verifactu_nombre_sistema',   'ElectroBazar TPV',                                      'Nombre oficial del sistema informático ante AEAT'),
(48, 'verifactu_id_sistema',       '01',                                                    'Identificador único del sistema informático'),
(49, 'verifactu_version_sistema',  '1.0.0',                                                 'Versión actual del software'),
(50, 'verifactu_num_instalacion',  'INST001',                                               'Número de instalación o licencia'),
(51, 'verifactu_cert_path',        'C:/Users/PracticasSoftware4/Desktop/xampp-nuevo/xampp/htdocs/certs/99999910G_prueba.pfx', 'Ruta al certificado .p12 para firma digital'),
(52, 'verifactu_cert_pass',        '',                                                      'Contraseña del certificado .p12'),
(53, 'verifactu_modo_test',        '1',                                                     '1 para modo pruebas (Sandbox), 0 para producción'),
(54, 'verifactu_remision_voluntaria','1',                                                   'Indica si se envía inmediatamente (1) o bajo requerimiento (0)'),
(55, 'verifactu_espera_hasta',     '1778232956',                                            'Timestamp Unix hasta el cual esperar para el siguiente envío'),
(56, 'verifactu_min_lote',         '1',                                                     'Número mínimo de registros a enviar por lote según AEAT'),
(78, 'verifactu_productor_nif',    '99999910G',                                             'NIF del productor del sistema'),
(79, 'verifactu_productor_nombre', 'CERTIFICADO FISICA PRUEBAS',                            'Nombre del productor del sistema');

-- CORRELATIVOS: contadores de secuencias
-- 'ticket' es la clave que usa VentaPDO.php (obtenerSiguienteTicket)
INSERT INTO correlativos (nombre, valor) VALUES
('ticket',         0),
('numero_factura', 0),
('numero_albaran', 0);
