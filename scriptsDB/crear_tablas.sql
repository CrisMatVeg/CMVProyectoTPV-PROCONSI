-- ============================================================
-- SCRIPT 1: CREACIÓN DE TABLAS (crear_tablas.sql)
-- Proyecto: ElectroBazar TPV
-- ============================================================

USE `dbelectrobazar-tpv`;

-- 1. ROLES Y PERMISOS
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rol_permisos (
    id_rol INT NOT NULL,
    id_permiso INT NOT NULL,
    PRIMARY KEY (id_rol, id_permiso),
    CONSTRAINT fk_rp_rol FOREIGN KEY (id_rol) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permiso FOREIGN KEY (id_permiso) REFERENCES permisos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. CONFIGURACIÓN Y TIPOS IVA
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT,
    descripcion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tipos_iva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    porcentaje DECIMAL(5,2) NOT NULL,
    recargo_equivalencia DECIMAL(5,2) DEFAULT 0.00,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_codigo_fecha (codigo, fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. ENTIDADES DE NEGOCIO (USUARIOS, CLIENTES, PROVEEDORES)
CREATE TABLE IF NOT EXISTS proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cif_nif VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    direccion TEXT,
    telefono VARCHAR(30),
    email VARCHAR(120),
    aplica_re TINYINT(1) DEFAULT 0,
    notas TEXT,
    activo TINYINT(1) DEFAULT 1,
    fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    condiciones_pago VARCHAR(255) DEFAULT NULL,
    plazo_entrega VARCHAR(100) DEFAULT NULL,
    vencimiento_dias INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    id_rol INT,
    theme_mode ENUM('light','dark','black') NOT NULL DEFAULT 'light',
    theme_accent VARCHAR(20) NOT NULL DEFAULT 'blue',
    theme_font VARCHAR(50) NOT NULL DEFAULT 'dm-mono',
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES roles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('particular','empresa') NOT NULL DEFAULT 'particular',
    rol VARCHAR(50) NOT NULL DEFAULT 'particular',
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NULL,
    nif VARCHAR(20) NULL,
    email VARCHAR(120) NULL,
    telefono VARCHAR(30) NULL,
    direccion VARCHAR(255) NULL,
    cp VARCHAR(10) NULL,
    poblacion VARCHAR(100) NULL,
    provincia VARCHAR(100) NULL,
    es_socio TINYINT(1) NOT NULL DEFAULT 0,
    fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_baja DATETIME NULL,
    notas TEXT NULL,
    UNIQUE KEY uk_nif (nif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. PRODUCTOS Y STOCK
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referencia VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio_coste DECIMAL(30,15) DEFAULT 0.000000000000000,
    precio_proveedor DECIMAL(30,15) DEFAULT 0.000000000000000,
    precio_venta DECIMAL(30,15) DEFAULT 0.000000000000000,
    stock_actual INT DEFAULT 0,
    stock_minimo INT DEFAULT 0,
    meses_garantia INT DEFAULT 24,
    icono BLOB,
    categoria VARCHAR(50),
    variantes JSON DEFAULT NULL,
    atributos JSON DEFAULT NULL,
    iva DECIMAL(5,2) DEFAULT 21.00,
    codigo_iva VARCHAR(20) DEFAULT 'GENERAL',
    id_tipo_iva INT,
    id_proveedor INT,
    margen DECIMAL(10,2) DEFAULT 0.00,
    es_pack TINYINT(1) DEFAULT 0,
    requiere_serial TINYINT(1) DEFAULT 0,
    mantener_precision TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prod_iva FOREIGN KEY (id_tipo_iva) REFERENCES tipos_iva(id) ON DELETE SET NULL,
    CONSTRAINT fk_prod_prov FOREIGN KEY (id_proveedor) REFERENCES proveedores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS productos_pack (
    id_pack INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    PRIMARY KEY (id_pack, id_producto),
    CONSTRAINT fk_pack_main FOREIGN KEY (id_pack) REFERENCES productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pack_item FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS numeros_serie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    numero_serie VARCHAR(100) NOT NULL UNIQUE,
    estado ENUM('disponible', 'vendido', 'garantia') DEFAULT 'disponible',
    CONSTRAINT fk_ns_producto FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS entradas_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_variante INT DEFAULT NULL,
    cantidad INT NOT NULL,
    precio_coste DECIMAL(10,4) NOT NULL DEFAULT 0,
    cmp_anterior DECIMAL(10,4) NOT NULL DEFAULT 0,
    cmp_resultante DECIMAL(10,4) NOT NULL DEFAULT 0,
    stock_anterior INT NOT NULL DEFAULT 0,
    stock_nuevo INT NOT NULL DEFAULT 0,
    id_usuario INT DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ent_prod FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_ent_usr FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS movimientos_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo_movimiento ENUM('venta', 'devolucion', 'compra', 'ajuste', 'inicial') NOT NULL,
    cantidad INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    notas VARCHAR(255),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mov_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_mov_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria_precios_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    precio_old DECIMAL(30,15) NOT NULL,
    precio_new DECIMAL(30,15) NOT NULL,
    motivo VARCHAR(255),
    id_usuario INT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_aud_prod FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_aud_usr FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. CAJA Y TURNOS
CREATE TABLE IF NOT EXISTS cierres_fiscales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT NOT NULL,
    total_efectivo DECIMAL(10,2) DEFAULT 0.00,
    total_tarjeta DECIMAL(10,2) DEFAULT 0.00,
    total_bizum DECIMAL(10,2) DEFAULT 0.00,
    total_general DECIMAL(10,2) DEFAULT 0.00,
    CONSTRAINT fk_cf_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS caja_turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario_apertura INT NOT NULL,
    fondo_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_cierre DATETIME NULL,
    id_usuario_cierre INT NULL,
    efectivo_real DECIMAL(10,2) NULL,
    fondo_siguiente_turno DECIMAL(10,2) NULL,
    total_retirado DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_ingresado DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('abierto','cerrado','pendiente_arqueo') NOT NULL DEFAULT 'abierto',
    num_z INT DEFAULT NULL,
    CONSTRAINT fk_turn_usr_ap FOREIGN KEY (id_usuario_apertura) REFERENCES usuarios(id),
    CONSTRAINT fk_turn_usr_ci FOREIGN KEY (id_usuario_cierre) REFERENCES usuarios(id),
    CONSTRAINT fk_turn_z FOREIGN KEY (num_z) REFERENCES cierres_fiscales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS caja_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_turno INT NOT NULL,
    id_usuario INT NULL,
    tipo ENUM('retiro', 'ingreso') NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    concepto VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cajo_turn FOREIGN KEY (id_turno) REFERENCES caja_turnos(id),
    CONSTRAINT fk_cajo_usr FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS caja_deudas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cierre_fiscal INT NOT NULL,
    id_usuario INT NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    concepto VARCHAR(255) NULL,
    saldada TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_saldada DATETIME NULL,
    CONSTRAINT fk_deuda_cierre FOREIGN KEY (id_cierre_fiscal) REFERENCES cierres_fiscales(id),
    CONSTRAINT fk_deuda_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. VENTAS
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_ticket INT NOT NULL UNIQUE,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT,
    id_cliente INT,
    tipo_cliente ENUM('particular','empresa') NOT NULL DEFAULT 'particular',
    nombre_cliente VARCHAR(100) NULL,
    nif_cliente VARCHAR(20) NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'mixto') NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    descuento_pct DECIMAL(5,2) DEFAULT 0,
    descuento_amt DECIMAL(10,2) DEFAULT 0,
    descuento_label VARCHAR(100) NULL,
    base_imponible DECIMAL(10,2) NOT NULL,
    iva_pct DECIMAL(5,2) DEFAULT 21.00,
    iva_amt DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    efectivo_recibido DECIMAL(10,2) DEFAULT 0,
    estado ENUM('completada', 'devuelta', 'anulada', 'pendiente_pago', 'parcialmente_devuelta') DEFAULT 'completada',
    pagado_a_cuenta DECIMAL(10,2) DEFAULT 0,
    fecha_limite_pago DATE NULL,
    es_factura TINYINT(1) DEFAULT 0,
    num_z INT DEFAULT NULL,
    id_turno INT DEFAULT NULL,
    tipo_documento ENUM('venta', 'abono') NOT NULL DEFAULT 'venta',
    id_venta_origen INT NULL DEFAULT NULL,
    hash_actual VARCHAR(64) DEFAULT NULL,
    hash_anterior VARCHAR(64) DEFAULT NULL,
    firma_digital LONGTEXT DEFAULT NULL,
    estado_envio_aeat ENUM('pendiente', 'enviado', 'error_critico', 'subsanacion_pendiente', 'bloqueado', 'anulado_pendiente') NOT NULL DEFAULT 'pendiente',
    codigo_qr TEXT DEFAULT NULL,
    CONSTRAINT fk_ventas_usr FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_ventas_clie FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL,
    CONSTRAINT fk_ventas_z FOREIGN KEY (num_z) REFERENCES cierres_fiscales(id),
    CONSTRAINT fk_ventas_turn FOREIGN KEY (id_turno) REFERENCES caja_turnos(id),
    CONSTRAINT fk_ventas_origen FOREIGN KEY (id_venta_origen) REFERENCES ventas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lineas_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT,
    nombre_producto VARCHAR(100) NOT NULL,
    codigo_producto VARCHAR(50) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    precio_base_snapshot DECIMAL(10,2) NOT NULL,
    precio_coste_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    iva_aplicado DECIMAL(5,2) DEFAULT 21.00,
    cantidad INT NOT NULL,
    meses_garantia INT DEFAULT 36,
    total_linea DECIMAL(10,2) NOT NULL,
    devuelta BOOLEAN DEFAULT FALSE,
    motivo_devolucion VARCHAR(255) DEFAULT NULL,
    fecha_devolucion DATETIME DEFAULT NULL,
    metodo_reembolso ENUM('efectivo', 'vale', 'reemplazo', 'otros') NULL DEFAULT NULL,
    numero_serie VARCHAR(100) DEFAULT NULL,
    CONSTRAINT fk_lv_venta FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE,
    CONSTRAINT fk_lv_prod FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lineas_serie_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_linea_venta INT NOT NULL,
    id_numero_serie INT NOT NULL,
    CONSTRAINT fk_lsv_linea FOREIGN KEY (id_linea_venta) REFERENCES lineas_venta(id) ON DELETE CASCADE,
    CONSTRAINT fk_lsv_ns FOREIGN KEY (id_numero_serie) REFERENCES numeros_serie(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 7. OTROS (TARIFAS, PROMOCIONES)
CREATE TABLE IF NOT EXISTS tarifas_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('percent','amount') NOT NULL DEFAULT 'percent',
    valor DECIMAL(10,2) NOT NULL,
    fecha_aplicacion DATE NULL,
    fecha_fin DATE NULL,
    dias_semana VARCHAR(20) NULL,
    hora_inicio TIME NULL,
    hora_fin TIME NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    aplicada TINYINT(1) NOT NULL DEFAULT 0,
    scope ENUM('todos','categoria','productos') NOT NULL DEFAULT 'todos',
    categoria VARCHAR(50) NULL,
    producto_ids TEXT NULL,
    excluidos JSON NULL,
    tipo_cliente ENUM('todos', 'particular', 'empresa', 'socio', 'mayorista') DEFAULT 'todos',
    roles_segmento VARCHAR(255) NULL,
    cliente_ids JSON NULL,
    es_solo_socios TINYINT(1) DEFAULT 0,
    id_cliente INT NULL,
    prioridad INT DEFAULT 0,
    creado_por INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tp_usr FOREIGN KEY (creado_por) REFERENCES usuarios(id),
    CONSTRAINT fk_tp_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    tipo ENUM('percent', 'amount', 'bundle', 'fixed_bundle') NOT NULL DEFAULT 'percent',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    min_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    id_producto INT NULL,
    categoria_code VARCHAR(255) NULL,
    bundle_buy_qty INT DEFAULT 0,
    bundle_pay_qty INT DEFAULT 0,
    excluidos JSON NULL,
    prioridad INT DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    solo_socios TINYINT(1) NOT NULL DEFAULT 0,
    roles_segmento VARCHAR(255) NULL,
    dias_semana VARCHAR(50) NULL,
    hora_inicio TIME NULL,
    hora_fin TIME NULL,
    producto_ids TEXT NULL,
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_promo_prod FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lineas_venta_descuentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_linea_venta INT NOT NULL,
    tipo_descuento ENUM('tarifa', 'promocion', 'cupon') NOT NULL,
    id_origen INT NOT NULL,
    valor_descontado DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_lvd_linea FOREIGN KEY (id_linea_venta) REFERENCES lineas_venta(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tarifa_productos (
    id_tarifa INT NOT NULL,
    id_producto INT NOT NULL,
    PRIMARY KEY (id_tarifa, id_producto),
    CONSTRAINT fk_tp_tarifa FOREIGN KEY (id_tarifa) REFERENCES tarifas_precios(id) ON DELETE CASCADE,
    CONSTRAINT fk_tp_producto FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. COMPRAS Y ALBARANES
CREATE TABLE IF NOT EXISTS facturas_compra_prov (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    numero_factura VARCHAR(50) NOT NULL,
    fecha_factura DATE NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    metodo_pago VARCHAR(50),
    pagado TINYINT(1) DEFAULT 0,
    fecha_vencimiento DATE NULL,
    CONSTRAINT fk_fcp_prov FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS albaranes_compra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    numero_albaran VARCHAR(50) NOT NULL,
    fecha DATE NOT NULL,
    base_imponible DECIMAL(10,2) NOT NULL,
    iva_total DECIMAL(10,2) NOT NULL,
    re_total DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('recibido', 'validado', 'facturado', 'anulado') DEFAULT 'recibido',
    factura_id INT DEFAULT NULL,
    CONSTRAINT fk_alb_prov FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
    CONSTRAINT fk_alb_fact FOREIGN KEY (factura_id) REFERENCES facturas_compra_prov(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lineas_compra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    albaran_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_coste_neto DECIMAL(10,4) NOT NULL,
    iva_pct DECIMAL(5,2) NOT NULL,
    re_pct DECIMAL(5,2) NOT NULL,
    CONSTRAINT fk_lc_alb FOREIGN KEY (albaran_id) REFERENCES albaranes_compra(id) ON DELETE CASCADE,
    CONSTRAINT fk_lc_prod FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. OTROS (VALES, LOGS, CATEGORIAS, ROLES CLIENTE)
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    id_tipo_iva INT DEFAULT NULL,
    CONSTRAINT fk_cat_iva FOREIGN KEY (id_tipo_iva) REFERENCES tipos_iva(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles_cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    id_cliente INT DEFAULT NULL,
    id_venta_origen INT DEFAULT NULL,
    importe DECIMAL(10,2) NOT NULL,
    importe_restante DECIMAL(10,2) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo', 'consumido', 'vencido') DEFAULT 'activo',
    CONSTRAINT fk_vale_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL,
    CONSTRAINT fk_vale_venta FOREIGN KEY (id_venta_origen) REFERENCES ventas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT DEFAULT NULL,
    nombre_usuario VARCHAR(100) DEFAULT 'Sistema/Anónimo',
    accion VARCHAR(50) NOT NULL,
    descripcion TEXT,
    detalles_json JSON DEFAULT NULL,
    CONSTRAINT fk_log_usr FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pagos_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    importe DECIMAL(10,2) NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    id_usuario INT DEFAULT NULL,
    notas TEXT,
    id_turno INT DEFAULT NULL,
    CONSTRAINT fk_pago_venta FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE,
    CONSTRAINT fk_pago_usr FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_pago_turno FOREIGN KEY (id_turno) REFERENCES caja_turnos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 10. TABLAS COMPLEMENTARIAS

CREATE TABLE IF NOT EXISTS correlativos (
    nombre VARCHAR(50) PRIMARY KEY,
    valor INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cola_envios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    xml_path VARCHAR(255) NULL,
    intentos INT DEFAULT 0,
    ultimo_error TEXT NULL,
    estado ENUM('pendiente', 'enviado', 'error_critico', 'bloqueado') DEFAULT 'pendiente',
    fecha_proximo_intento DATETIME DEFAULT CURRENT_TIMESTAMP,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cola_venta FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_cola_estado_fecha ON cola_envios(estado, fecha_proximo_intento);

CREATE TABLE IF NOT EXISTS log_ajustes_globales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL,
    id_usuario INT NOT NULL,
    tipo_operacion VARCHAR(50) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    tipo_valor VARCHAR(20) NOT NULL,
    categoria_nom VARCHAR(100) NOT NULL DEFAULT 'Todas',
    motivo TEXT NOT NULL,
    productos_afectados INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_lag_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS analitica_resumen_diario (
    fecha DATE PRIMARY KEY,
    total_tickets INT NOT NULL DEFAULT 0,
    total_ventas DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_base DECIMAL(10,2) NOT NULL DEFAULT 0,
    margen_estimado DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_efectivo DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_tarjeta DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_bizum DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_a_cuenta DECIMAL(10,2) NOT NULL DEFAULT 0,
    calculado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS analitica_producto_diario (
    fecha DATE NOT NULL,
    id_producto INT NOT NULL,
    nombre VARCHAR(150),
    categoria VARCHAR(50),
    referencia VARCHAR(50),
    unidades INT NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    coste DECIMAL(10,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (fecha, id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS analitica_iva_diario (
    fecha DATE NOT NULL,
    porcentaje DECIMAL(5,2) NOT NULL,
    cuota DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (fecha, porcentaje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. TRIGGERS DE INTEGRIDAD VERIFACTU (RD 1007/2023)
DELIMITER //

CREATE TRIGGER tg_ventas_prevent_update BEFORE UPDATE ON ventas
FOR EACH ROW
BEGIN
    -- Si la factura ya tiene huella (fue emitida fiscalmente), bloqueamos cambios en campos críticos
    IF OLD.hash_actual IS NOT NULL THEN
        IF NEW.total <> OLD.total OR 
           NEW.base_imponible <> OLD.base_imponible OR 
           NEW.iva_amt <> OLD.iva_amt OR 
           NEW.subtotal <> OLD.subtotal OR
           NEW.fecha <> OLD.fecha OR
           NEW.numero_ticket <> OLD.numero_ticket OR
           NEW.es_factura <> OLD.es_factura THEN
           
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'VeriFactu: Inalterabilidad violada. No se permite modificar campos que afecten a la huella fiscal.';
        END IF;
    END IF;
END //

CREATE TRIGGER tg_ventas_prevent_delete BEFORE DELETE ON ventas
FOR EACH ROW
BEGIN
    IF OLD.hash_actual IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'VeriFactu: Inalterabilidad violada. No se permite eliminar registros del historial fiscal.';
    END IF;
END //

DELIMITER ;
