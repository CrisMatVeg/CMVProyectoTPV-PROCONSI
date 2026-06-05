-- ============================================================
-- estructura_final.sql
-- Base de datos: electrobazar
-- Versión final: incorpora v4 → v21 + consolidated_schema_fixes
-- Verificado contra modelos PHP, seeds y migraciones.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `electrobazar`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `electrobazar`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ROLES Y PERMISOS
-- ============================================================
CREATE TABLE `roles` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `nombre`      VARCHAR(50)  NOT NULL UNIQUE,
  `descripcion` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `permisos` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `clave`       VARCHAR(50)  NOT NULL UNIQUE,
  `descripcion` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rol_permisos` (
  `id_rol`     INT NOT NULL,
  `id_permiso` INT NOT NULL,
  PRIMARY KEY (`id_rol`, `id_permiso`),
  CONSTRAINT `fk_rp_rol`     FOREIGN KEY (`id_rol`)     REFERENCES `roles`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. CONFIGURACIÓN Y TIPOS IVA
-- ============================================================
CREATE TABLE `configuracion` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `clave`       VARCHAR(50)  NOT NULL UNIQUE,
  `valor`       TEXT         DEFAULT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tipos_iva` (
  `id`                    INT AUTO_INCREMENT PRIMARY KEY,
  `codigo`                VARCHAR(20)    NOT NULL,
  `nombre`                VARCHAR(50)    NOT NULL,
  `porcentaje`            DECIMAL(5,2)   NOT NULL,
  `recargo_equivalencia`  DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
  `fecha_inicio`          DATE           NOT NULL,
  `fecha_fin`             DATE           DEFAULT NULL,
  `activo`                TINYINT(1)     NOT NULL DEFAULT 1,
  UNIQUE KEY `uk_codigo_fecha` (`codigo`, `fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. PROVEEDORES, USUARIOS, CLIENTES
-- ============================================================
CREATE TABLE `proveedores` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `cif_nif`          VARCHAR(20)  NOT NULL UNIQUE,
  `nombre`           VARCHAR(150) NOT NULL,
  `direccion`        TEXT         DEFAULT NULL,
  `telefono`         VARCHAR(30)  DEFAULT NULL,
  `email`            VARCHAR(120) DEFAULT NULL,
  `aplica_re`        TINYINT(1)   NOT NULL DEFAULT 0,
  `notas`            TEXT         DEFAULT NULL,
  `activo`           TINYINT(1)   NOT NULL DEFAULT 1,
  `fecha_alta`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `condiciones_pago` VARCHAR(255) DEFAULT NULL,
  `plazo_entrega`    VARCHAR(100) DEFAULT NULL,
  `vencimiento_dias` INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- email, idioma, token_recuperacion/expiracion usados en UsuarioPDO.php
CREATE TABLE `usuarios` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `login`               VARCHAR(50)                      NOT NULL UNIQUE,
  `password`            VARCHAR(255)                     NOT NULL,
  `nombre`              VARCHAR(100)                     NOT NULL,
  `email`               VARCHAR(120)                     DEFAULT NULL,
  `idioma`              VARCHAR(2)                       NOT NULL DEFAULT 'es',
  `id_rol`              INT                              DEFAULT NULL,
  `theme_mode`          ENUM('light','dark','black')     NOT NULL DEFAULT 'light',
  `theme_accent`        VARCHAR(20)                      NOT NULL DEFAULT 'blue',
  `theme_font`          VARCHAR(50)                      NOT NULL DEFAULT 'dm-mono',
  `activo`              TINYINT(1)                       NOT NULL DEFAULT 1,
  `creado_en`           DATETIME                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `token_recuperacion`  VARCHAR(100)                     DEFAULT NULL,
  `token_expiracion`    DATETIME                         DEFAULT NULL,
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- aeat_id_type/aeat_codigo_pais usados en VentaPDO INSERT; puntos/ultima_compra en ClientePDO
CREATE TABLE `clientes` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `tipo`             ENUM('particular','empresa') NOT NULL DEFAULT 'particular',
  `rol`              VARCHAR(50)  NOT NULL DEFAULT 'particular',
  `nombre`           VARCHAR(100) NOT NULL,
  `apellidos`        VARCHAR(150) DEFAULT NULL,
  `nif`              VARCHAR(20)  DEFAULT NULL,
  `aeat_id_type`     VARCHAR(2)   DEFAULT NULL,
  `aeat_codigo_pais` VARCHAR(2)   DEFAULT NULL,
  `email`            VARCHAR(120) DEFAULT NULL,
  `telefono`         VARCHAR(30)  DEFAULT NULL,
  `direccion`        VARCHAR(255) DEFAULT NULL,
  `cp`               VARCHAR(10)  DEFAULT NULL,
  `poblacion`        VARCHAR(100) DEFAULT NULL,
  `provincia`        VARCHAR(100) DEFAULT NULL,
  `es_socio`         TINYINT(1)   NOT NULL DEFAULT 0,
  `fecha_alta`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_baja`       DATETIME     DEFAULT NULL,
  `notas`            TEXT         DEFAULT NULL,
  `puntos`           INT          NOT NULL DEFAULT 0,
  `ultima_compra`    DATETIME     DEFAULT NULL,
  UNIQUE KEY `uk_nif` (`nif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. PRODUCTOS, STOCK, SERIES
-- ============================================================
CREATE TABLE `categorias` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `codigo`     VARCHAR(50)  NOT NULL UNIQUE,
  `nombre`     VARCHAR(100) NOT NULL,
  `id_tipo_iva` INT         DEFAULT NULL,
  CONSTRAINT `fk_cat_iva` FOREIGN KEY (`id_tipo_iva`) REFERENCES `tipos_iva`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `roles_cliente` (
  `id`     INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- precio_coste/proveedor/venta → DECIMAL(15,4) tras migración v13
CREATE TABLE `productos` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `referencia`       VARCHAR(50)   NOT NULL UNIQUE,
  `nombre`           VARCHAR(100)  NOT NULL,
  `descripcion`      TEXT          DEFAULT NULL,
  `precio_coste`     DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `precio_proveedor` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `precio_venta`     DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `stock_actual`     INT           NOT NULL DEFAULT 0,
  `stock_minimo`     INT           NOT NULL DEFAULT 0,
  `meses_garantia`   INT           NOT NULL DEFAULT 24,
  `icono`            BLOB          DEFAULT NULL,
  `categoria`        VARCHAR(50)   DEFAULT NULL,
  `variantes`        JSON          DEFAULT NULL,
  `atributos`        JSON          DEFAULT NULL,
  `iva`              DECIMAL(5,2)  NOT NULL DEFAULT 21.00,
  `codigo_iva`       VARCHAR(20)   NOT NULL DEFAULT 'GENERAL',
  `id_tipo_iva`      INT           DEFAULT NULL,
  `id_proveedor`     INT           DEFAULT NULL,
  `margen`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `es_pack`          TINYINT(1)    NOT NULL DEFAULT 0,
  `requiere_serial`  TINYINT(1)    NOT NULL DEFAULT 0,
  `mantener_precision` TINYINT(1)  NOT NULL DEFAULT 0,
  `activo`           TINYINT(1)    NOT NULL DEFAULT 1,
  `creado_en`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_prod_iva`  FOREIGN KEY (`id_tipo_iva`) REFERENCES `tipos_iva`(`id`)    ON DELETE SET NULL,
  CONSTRAINT `fk_prod_prov` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `productos_pack` (
  `id_pack`    INT NOT NULL,
  `id_producto` INT NOT NULL,
  `cantidad`   INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_pack`, `id_producto`),
  CONSTRAINT `fk_pack_main` FOREIGN KEY (`id_pack`)    REFERENCES `productos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pack_item` FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `numeros_serie` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto`  INT          NOT NULL,
  `numero_serie` VARCHAR(100) NOT NULL UNIQUE,
  `estado`       ENUM('disponible','vendido','garantia') NOT NULL DEFAULT 'disponible',
  CONSTRAINT `fk_ns_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `entradas_stock` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto`    INT          NOT NULL,
  `id_variante`    INT          DEFAULT NULL,
  `cantidad`       INT          NOT NULL,
  `precio_coste`   DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `cmp_anterior`   DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `cmp_resultante` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `stock_anterior` INT          NOT NULL DEFAULT 0,
  `stock_nuevo`    INT          NOT NULL DEFAULT 0,
  `id_usuario`     INT          DEFAULT NULL,
  `notas`          TEXT         DEFAULT NULL,
  `fecha`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ent_prod` FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ent_usr`  FOREIGN KEY (`id_usuario`)  REFERENCES `usuarios`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `movimientos_stock` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `producto_id`     INT          NOT NULL,
  `tipo_movimiento` ENUM('venta','devolucion','compra','ajuste','inicial') NOT NULL,
  `cantidad`        INT          NOT NULL,
  `usuario_id`      INT          DEFAULT NULL,
  `notas`           VARCHAR(255) DEFAULT NULL,
  `fecha`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mov_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mov_usr`  FOREIGN KEY (`usuario_id`)  REFERENCES `usuarios`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- precio_old/new → DECIMAL(15,4) para coincidir con productos.precio_coste (v13)
CREATE TABLE `auditoria_precios_base` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `id_producto` INT           NOT NULL,
  `precio_old`  DECIMAL(15,4) NOT NULL,
  `precio_new`  DECIMAL(15,4) NOT NULL,
  `motivo`      VARCHAR(255)  DEFAULT NULL,
  `id_usuario`  INT           DEFAULT NULL,
  `fecha`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_aud_prod` FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_aud_usr`  FOREIGN KEY (`id_usuario`)  REFERENCES `usuarios`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. CAJA, CIERRES, TURNOS
-- ============================================================
-- total_financiado presente en estructura pero no usado en INSERT (DEFAULT 0)
CREATE TABLE `cierres_fiscales` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `fecha`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario`       INT           NOT NULL,
  `total_efectivo`   DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `total_tarjeta`    DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `total_bizum`      DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `total_financiado` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `total_general`    DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  CONSTRAINT `fk_cf_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `caja_turnos` (
  `id`                   INT AUTO_INCREMENT PRIMARY KEY,
  `fecha_apertura`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario_apertura`  INT           NOT NULL,
  `fondo_inicial`        DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `fecha_cierre`         DATETIME      DEFAULT NULL,
  `id_usuario_cierre`    INT           DEFAULT NULL,
  `efectivo_real`        DECIMAL(12,4) DEFAULT NULL,
  `fondo_siguiente_turno` DECIMAL(12,4) DEFAULT NULL,
  `total_retirado`       DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `total_ingresado`      DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `estado`               ENUM('abierto','cerrado','pendiente_arqueo') NOT NULL DEFAULT 'abierto',
  `num_z`                INT           DEFAULT NULL,
  CONSTRAINT `fk_turn_usr_ap` FOREIGN KEY (`id_usuario_apertura`) REFERENCES `usuarios`(`id`),
  CONSTRAINT `fk_turn_usr_ci` FOREIGN KEY (`id_usuario_cierre`)   REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_turn_z`      FOREIGN KEY (`num_z`)               REFERENCES `cierres_fiscales`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `caja_movimientos` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `id_turno`   INT          NOT NULL,
  `id_usuario` INT          DEFAULT NULL,
  `tipo`       ENUM('retiro','ingreso') NOT NULL,
  `importe`    DECIMAL(12,2) NOT NULL,
  `concepto`   VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cajo_turn` FOREIGN KEY (`id_turno`)   REFERENCES `caja_turnos`(`id`),
  CONSTRAINT `fk_cajo_usr`  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `caja_deudas` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `id_cierre_fiscal` INT          NOT NULL,
  `id_usuario`       INT          NOT NULL,
  `importe`          DECIMAL(12,2) NOT NULL,
  `concepto`         VARCHAR(255) DEFAULT NULL,
  `saldada`          TINYINT(1)   NOT NULL DEFAULT 0,
  `fecha_creacion`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_saldada`    DATETIME     DEFAULT NULL,
  CONSTRAINT `fk_deuda_cierre`  FOREIGN KEY (`id_cierre_fiscal`) REFERENCES `cierres_fiscales`(`id`),
  CONSTRAINT `fk_deuda_usuario` FOREIGN KEY (`id_usuario`)       REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. VENTAS (tabla fiscal principal)
-- metodo_pago incluye 'puntos' (VentaPDO.php línea 127)
-- ============================================================
CREATE TABLE `ventas` (
  `id`                   INT AUTO_INCREMENT PRIMARY KEY,
  `numero_ticket`        INT          NOT NULL UNIQUE,
  `fecha`                DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario`           INT          DEFAULT NULL,
  `id_cliente`           INT          DEFAULT NULL,
  `tipo_cliente`         ENUM('particular','empresa') NOT NULL DEFAULT 'particular',
  `nombre_cliente`       VARCHAR(100) DEFAULT NULL,
  `nif_cliente`          VARCHAR(20)  DEFAULT NULL,
  `aeat_id_type`         VARCHAR(2)   DEFAULT NULL,
  `aeat_codigo_pais`     VARCHAR(2)   DEFAULT NULL,
  `metodo_pago`          ENUM('efectivo','tarjeta','bizum','a_cuenta','mixto','puntos') NOT NULL DEFAULT 'efectivo',
  `subtotal`             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `descuento_pct`        DECIMAL(7,4)  NOT NULL DEFAULT 0.0000,
  `descuento_amt`        DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `descuento_label`      VARCHAR(100) DEFAULT NULL,
  `base_imponible`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_pct`              DECIMAL(7,4)  NOT NULL DEFAULT 21.0000,
  `iva_amt`              DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total`                DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_aeat`           DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `efectivo_recibido`    DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `estado`               ENUM('completada','devuelta','anulada','pendiente_pago','parcialmente_devuelta') NOT NULL DEFAULT 'completada',
  `pagado_a_cuenta`      DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `fecha_limite_pago`    DATE          DEFAULT NULL,
  `es_factura`           TINYINT(1)    NOT NULL DEFAULT 0,
  `comentarios`          TEXT          DEFAULT NULL,
  `num_z`                INT           DEFAULT NULL,
  `id_turno`             INT           DEFAULT NULL,
  `tipo_documento`       ENUM('venta','abono') NOT NULL DEFAULT 'venta',
  `id_venta_origen`      INT           DEFAULT NULL,
  `id_venta_sustituida`  INT           DEFAULT NULL,
  `tipo_rectificativa`   VARCHAR(1)    DEFAULT NULL,
  `hash_actual`          CHAR(64)      DEFAULT NULL,
  `hash_anterior`        CHAR(64)      DEFAULT NULL,
  `fecha_hora_gen_fiscal` DATETIME     DEFAULT NULL,
  `estado_envio_aeat`    ENUM('pendiente','enviado','error_critico','subsanacion_pendiente','bloqueado','anulado_pendiente') NOT NULL DEFAULT 'pendiente',
  `codigo_qr`            TEXT          DEFAULT NULL,
  `qr_verifactu`         TEXT          DEFAULT NULL,
  `puntos_ganados`       INT           NOT NULL DEFAULT 0,
  `puntos_canjeados`     INT           NOT NULL DEFAULT 0,
  `puntos_descuento_amt` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  CONSTRAINT `fk_ventas_usr`    FOREIGN KEY (`id_usuario`)          REFERENCES `usuarios`(`id`)         ON DELETE SET NULL,
  CONSTRAINT `fk_ventas_clie`   FOREIGN KEY (`id_cliente`)          REFERENCES `clientes`(`id`)         ON DELETE SET NULL,
  CONSTRAINT `fk_ventas_z`      FOREIGN KEY (`num_z`)               REFERENCES `cierres_fiscales`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ventas_turn`   FOREIGN KEY (`id_turno`)            REFERENCES `caja_turnos`(`id`)      ON DELETE SET NULL,
  CONSTRAINT `fk_ventas_origen` FOREIGN KEY (`id_venta_origen`)     REFERENCES `ventas`(`id`)           ON DELETE SET NULL,
  CONSTRAINT `fk_ventas_sust`   FOREIGN KEY (`id_venta_sustituida`) REFERENCES `ventas`(`id`)           ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 7. LÍNEAS DE VENTA
-- id_producto NULL (v14): permite productos comodín sin ID
-- ============================================================
CREATE TABLE `lineas_venta` (
  `id`                   INT AUTO_INCREMENT PRIMARY KEY,
  `id_venta`             INT          NOT NULL,
  `id_producto`          INT          DEFAULT NULL,
  `nombre_producto`      VARCHAR(100) NOT NULL,
  `codigo_producto`      VARCHAR(50)  NOT NULL,
  `precio_unitario`      DECIMAL(12,2) NOT NULL,
  `precio_base_snapshot` DECIMAL(12,2) NOT NULL,
  `precio_coste_unitario` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_aplicado`         DECIMAL(7,4)  NOT NULL DEFAULT 21.0000,
  `cantidad`             INT           NOT NULL,
  `meses_garantia`       INT           NOT NULL DEFAULT 24,
  `total_linea`          DECIMAL(12,2) NOT NULL,
  `devuelta`             TINYINT(1)    NOT NULL DEFAULT 0,
  `motivo_devolucion`    VARCHAR(255)  DEFAULT NULL,
  `fecha_devolucion`     DATETIME      DEFAULT NULL,
  `metodo_reembolso`     ENUM('efectivo','vale','reemplazo','otros') DEFAULT NULL,
  `numero_serie`         VARCHAR(100)  DEFAULT NULL,
  CONSTRAINT `fk_lv_venta` FOREIGN KEY (`id_venta`)    REFERENCES `ventas`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_lv_prod`  FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PK compuesta (tabla de unión 1:1 linea↔serie; sin id propio)
CREATE TABLE `lineas_serie_venta` (
  `id_linea_venta`  INT NOT NULL,
  `id_numero_serie` INT NOT NULL,
  PRIMARY KEY (`id_linea_venta`, `id_numero_serie`),
  CONSTRAINT `fk_lsv_linea` FOREIGN KEY (`id_linea_venta`)  REFERENCES `lineas_venta`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_lsv_ns`    FOREIGN KEY (`id_numero_serie`) REFERENCES `numeros_serie`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `lineas_venta_descuentos` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `id_linea_venta`  INT          NOT NULL,
  `tipo_descuento`  ENUM('tarifa','promocion','cupon') NOT NULL,
  `id_origen`       INT          NOT NULL,
  `valor_descontado` DECIMAL(12,2) NOT NULL,
  CONSTRAINT `fk_lvd_linea` FOREIGN KEY (`id_linea_venta`) REFERENCES `lineas_venta`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 8. TARIFAS Y PROMOCIONES
-- id_cliente/excluidos/cliente_ids nullable (v20)
-- id_producto/excluidos nullable + defaults numéricos (v21)
-- ============================================================
CREATE TABLE `tarifas_precios` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `nombre`          VARCHAR(100) NOT NULL,
  `tipo`            ENUM('percent','amount') NOT NULL DEFAULT 'percent',
  `valor`           DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `fecha_aplicacion` DATE         DEFAULT NULL,
  `fecha_fin`       DATE          DEFAULT NULL,
  `dias_semana`     VARCHAR(20)   DEFAULT NULL,
  `hora_inicio`     TIME          DEFAULT NULL,
  `hora_fin`        TIME          DEFAULT NULL,
  `activo`          TINYINT(1)    NOT NULL DEFAULT 1,
  `aplicada`        TINYINT(1)    NOT NULL DEFAULT 0,
  `scope`           ENUM('todos','categoria','productos') NOT NULL DEFAULT 'todos',
  `categoria`       VARCHAR(50)   DEFAULT NULL,
  `producto_ids`    TEXT          DEFAULT NULL,
  `excluidos`       JSON          DEFAULT NULL,
  `tipo_cliente`    ENUM('todos','particular','empresa','socio','mayorista') NOT NULL DEFAULT 'todos',
  `roles_segmento`  VARCHAR(255)  DEFAULT NULL,
  `cliente_ids`     JSON          DEFAULT NULL,
  `es_solo_socios`  TINYINT(1)    NOT NULL DEFAULT 0,
  `id_cliente`      INT           DEFAULT NULL,
  `prioridad`       INT           NOT NULL DEFAULT 0,
  `creado_por`      INT           NOT NULL,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tp_usr`     FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id`),
  CONSTRAINT `fk_tp_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tarifa_productos` (
  `id_tarifa`   INT NOT NULL,
  `id_producto` INT NOT NULL,
  PRIMARY KEY (`id_tarifa`, `id_producto`),
  CONSTRAINT `fk_tp_tarifa`   FOREIGN KEY (`id_tarifa`)   REFERENCES `tarifas_precios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tp_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `promociones` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `codigo`         VARCHAR(50)  DEFAULT NULL UNIQUE,
  `descripcion`    VARCHAR(255) NOT NULL,
  `tipo`           ENUM('percent','amount','bundle','fixed_bundle') NOT NULL DEFAULT 'percent',
  `valor`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `min_subtotal`   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `id_producto`    INT           DEFAULT NULL,
  `producto_ids`   TEXT          DEFAULT NULL,
  `categoria_code` VARCHAR(255)  DEFAULT NULL,
  `bundle_buy_qty` INT           NOT NULL DEFAULT 0,
  `bundle_pay_qty` INT           NOT NULL DEFAULT 0,
  `excluidos`      JSON          DEFAULT NULL,
  `prioridad`      INT           NOT NULL DEFAULT 0,
  `activo`         TINYINT(1)    NOT NULL DEFAULT 1,
  `solo_socios`    TINYINT(1)    NOT NULL DEFAULT 0,
  `roles_segmento` VARCHAR(255)  DEFAULT NULL,
  `dias_semana`    VARCHAR(50)   DEFAULT NULL,
  `hora_inicio`    TIME          DEFAULT NULL,
  `hora_fin`       TIME          DEFAULT NULL,
  `fecha_inicio`   DATE          DEFAULT NULL,
  `fecha_fin`      DATE          DEFAULT NULL,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_promo_prod` FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `promocion_productos` (
  `id_promocion` INT NOT NULL,
  `id_producto`  INT NOT NULL,
  PRIMARY KEY (`id_promocion`, `id_producto`),
  CONSTRAINT `fk_pp_promo` FOREIGN KEY (`id_promocion`) REFERENCES `promociones`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pp_prod`  FOREIGN KEY (`id_producto`)  REFERENCES `productos`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 9. COMPRAS Y ALBARANES
-- ============================================================
CREATE TABLE `facturas_compra_prov` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `proveedor_id`     INT          NOT NULL,
  `numero_factura`   VARCHAR(50)  NOT NULL,
  `fecha_factura`    DATE         NOT NULL,
  `total`            DECIMAL(12,2) NOT NULL,
  `metodo_pago`      VARCHAR(50)  DEFAULT NULL,
  `pagado`           TINYINT(1)   NOT NULL DEFAULT 0,
  `fecha_vencimiento` DATE        DEFAULT NULL,
  CONSTRAINT `fk_fcp_prov` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `albaranes_compra` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `proveedor_id`   INT          NOT NULL,
  `numero_albaran` VARCHAR(50)  NOT NULL,
  `fecha`          DATE         NOT NULL,
  `base_imponible` DECIMAL(12,2) NOT NULL,
  `iva_total`      DECIMAL(12,2) NOT NULL,
  `re_total`       DECIMAL(12,2) NOT NULL,
  `total`          DECIMAL(12,2) NOT NULL,
  `estado`         ENUM('recibido','validado','facturado','anulado') NOT NULL DEFAULT 'recibido',
  `factura_id`     INT          DEFAULT NULL,
  CONSTRAINT `fk_alb_prov` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`),
  CONSTRAINT `fk_alb_fact` FOREIGN KEY (`factura_id`)   REFERENCES `facturas_compra_prov`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `lineas_compra` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `albaran_id`        INT          NOT NULL,
  `producto_id`       INT          NOT NULL,
  `cantidad`          INT          NOT NULL,
  `precio_coste_neto` DECIMAL(12,4) NOT NULL,
  `iva_pct`           DECIMAL(7,2)  NOT NULL,
  `re_pct`            DECIMAL(7,2)  NOT NULL,
  CONSTRAINT `fk_lc_alb`  FOREIGN KEY (`albaran_id`)  REFERENCES `albaranes_compra`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lc_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 10. FINANCIACIÓN
-- ============================================================
CREATE TABLE `financieras` (
  `id`                   INT AUTO_INCREMENT PRIMARY KEY,
  `nombre`               VARCHAR(100) NOT NULL,
  `descripcion`          VARCHAR(255) DEFAULT NULL,
  `comision_cero_interes` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  `comision_con_interes`  DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  `min_importe`          DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `activo`               TINYINT(1)   NOT NULL DEFAULT 1,
  `creado_en`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `comisiones_plazo` (
  `id`                   INT AUTO_INCREMENT PRIMARY KEY,
  `id_financiera`        INT         NOT NULL,
  `meses`                INT         NOT NULL,
  `comision_sin_interes` DECIMAL(7,2) NOT NULL DEFAULT 0.00,
  `comision_con_interes` DECIMAL(7,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_cp_financiera` FOREIGN KEY (`id_financiera`) REFERENCES `financieras`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ventas_financiacion` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `id_venta`          INT          NOT NULL,
  `id_financiera`     INT          NOT NULL,
  `meses`             INT          NOT NULL,
  `cuota_mensual`     DECIMAL(12,2) NOT NULL,
  `importe_intereses` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `modalidad`         ENUM('sin_interes','con_interes') NOT NULL DEFAULT 'sin_interes',
  `estado`            ENUM('pendiente','aprobado','rechazado','cancelado') NOT NULL DEFAULT 'pendiente',
  `notas`             TEXT         DEFAULT NULL,
  CONSTRAINT `fk_vf_venta`      FOREIGN KEY (`id_venta`)      REFERENCES `ventas`(`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_vf_financiera` FOREIGN KEY (`id_financiera`) REFERENCES `financieras`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 11. VALES, PAGOS, LOGS, CORRELATIVOS
-- ============================================================
CREATE TABLE `vales` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `codigo`           VARCHAR(20)  NOT NULL UNIQUE,
  `id_cliente`       INT          DEFAULT NULL,
  `id_venta_origen`  INT          DEFAULT NULL,
  `importe`          DECIMAL(12,2) NOT NULL,
  `importe_restante` DECIMAL(12,2) NOT NULL,
  `fecha_creacion`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado`           ENUM('activo','consumido','vencido') NOT NULL DEFAULT 'activo',
  CONSTRAINT `fk_vale_cliente` FOREIGN KEY (`id_cliente`)      REFERENCES `clientes`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vale_venta`   FOREIGN KEY (`id_venta_origen`) REFERENCES `ventas`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pagos_venta` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `id_venta`    INT          NOT NULL,
  `fecha`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `importe`     DECIMAL(12,2) NOT NULL,
  `metodo_pago` VARCHAR(50)  NOT NULL,
  `id_usuario`  INT          DEFAULT NULL,
  `notas`       TEXT         DEFAULT NULL,
  `id_turno`    INT          DEFAULT NULL,
  CONSTRAINT `fk_pago_venta`  FOREIGN KEY (`id_venta`)   REFERENCES `ventas`(`id`)      ON DELETE CASCADE,
  CONSTRAINT `fk_pago_usr`    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`)    ON DELETE SET NULL,
  CONSTRAINT `fk_pago_turno`  FOREIGN KEY (`id_turno`)   REFERENCES `caja_turnos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `logs_sistema` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `fecha_hora`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario`     INT          DEFAULT NULL,
  `nombre_usuario` VARCHAR(100) NOT NULL DEFAULT 'Sistema/Anónimo',
  `accion`         VARCHAR(50)  NOT NULL,
  `descripcion`    TEXT         DEFAULT NULL,
  `detalles_json`  JSON         DEFAULT NULL,
  CONSTRAINT `fk_log_usr` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `correlativos` (
  `nombre` VARCHAR(50) NOT NULL,
  `valor`  INT         NOT NULL DEFAULT 0,
  PRIMARY KEY (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 12. COLA DE ENVÍOS AEAT Y VERIFACTU
-- respuesta_aeat/fecha_envio usados en AeatQueueService.php
-- ============================================================
CREATE TABLE `cola_envios` (
  `id`                   INT AUTO_INCREMENT PRIMARY KEY,
  `id_venta`             INT           NOT NULL,
  `xml_path`             VARCHAR(255)  DEFAULT NULL,
  `intentos`             INT           NOT NULL DEFAULT 0,
  `ultimo_error`         TEXT          DEFAULT NULL,
  `respuesta_aeat`       MEDIUMBLOB    DEFAULT NULL,
  `estado`               ENUM('pendiente','enviado','error_critico','bloqueado') NOT NULL DEFAULT 'pendiente',
  `fecha_proximo_intento` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_en`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_envio`          DATETIME      DEFAULT NULL,
  CONSTRAINT `fk_cola_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `verifactu_logs` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `id_venta`        INT          NOT NULL,
  `tipo_registro`   VARCHAR(20)  NOT NULL,
  `numero_serie`    VARCHAR(50)  NOT NULL,
  `xml_path`        VARCHAR(255) DEFAULT NULL,
  `hash_anterior`   VARCHAR(255) DEFAULT NULL,
  `hash_actual`     VARCHAR(255) DEFAULT NULL,
  `estado`          ENUM('pendiente','enviado') NOT NULL DEFAULT 'pendiente',
  `respuesta_aeat`  MEDIUMBLOB   DEFAULT NULL,
  `fecha_registro`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_vl_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- hash_anterior/hash_evento: para encadenamiento futuro de eventos (nullable)
CREATE TABLE `verifactu_eventos` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `fecha_hora`   DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo_evento`  VARCHAR(50) NOT NULL,
  `descripcion`  TEXT        DEFAULT NULL,
  `detalles_json` JSON       DEFAULT NULL,
  `hash_anterior` CHAR(64)   DEFAULT NULL,
  `hash_evento`   CHAR(64)   DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 13. ANALÍTICA
-- PK basada en fecha (sin id) para ON DUPLICATE KEY UPDATE en AnaliticaPDO.php
-- ============================================================
CREATE TABLE `analitica_resumen_diario` (
  `fecha`            DATE          NOT NULL,
  `total_tickets`    INT           NOT NULL DEFAULT 0,
  `total_ventas`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_base`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `margen_estimado`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_efectivo`   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_tarjeta`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_bizum`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_a_cuenta`   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `calculado_en`     DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `analitica_producto_diario` (
  `fecha`       DATE          NOT NULL,
  `id_producto` INT           NOT NULL,
  `nombre`      VARCHAR(150)  DEFAULT NULL,
  `categoria`   VARCHAR(50)   DEFAULT NULL,
  `referencia`  VARCHAR(50)   DEFAULT NULL,
  `unidades`    INT           NOT NULL DEFAULT 0,
  `total`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `coste`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`fecha`, `id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `analitica_iva_diario` (
  `fecha`       DATE         NOT NULL,
  `porcentaje`  DECIMAL(5,2) NOT NULL,
  `cuota`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`fecha`, `porcentaje`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 14. LOG AJUSTES GLOBALES (v19: fecha_aplicacion, estado, params_json)
-- ============================================================
CREATE TABLE `log_ajustes_globales` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `fecha`               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario`          INT          NOT NULL,
  `tipo_operacion`      VARCHAR(50)  NOT NULL,
  `valor`               DECIMAL(12,2) NOT NULL,
  `tipo_valor`          VARCHAR(20)  NOT NULL,
  `categoria_nom`       VARCHAR(100) NOT NULL DEFAULT 'Todas',
  `motivo`              TEXT         NOT NULL,
  `productos_afectados` INT          NOT NULL DEFAULT 0,
  `fecha_aplicacion`    DATETIME     DEFAULT NULL,
  `estado`              VARCHAR(20)  NOT NULL DEFAULT 'aplicado',
  `params_json`         TEXT         DEFAULT NULL,
  CONSTRAINT `fk_lag_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ÍNDICES DE RENDIMIENTO
-- (v12, v15, v16, v17, v18, v19, consolidated_schema_fixes)
-- ============================================================

-- v12: claves foráneas de alto volumen
CREATE INDEX idx_ventas_id_usuario   ON ventas      (id_usuario);
CREATE INDEX idx_ventas_id_cliente   ON ventas      (id_cliente);
CREATE INDEX idx_ventas_num_z        ON ventas      (num_z);
CREATE INDEX idx_ventas_id_turno     ON ventas      (id_turno);
CREATE INDEX idx_lineas_id_venta     ON lineas_venta(id_venta);
CREATE INDEX idx_cola_id_venta       ON cola_envios  (id_venta);

-- v15: búsquedas frecuentes del TPV
CREATE INDEX idx_lv_id_producto        ON lineas_venta    (id_producto);
CREATE INDEX idx_clientes_nif          ON clientes        (nif);
CREATE INDEX idx_productos_referencia  ON productos       (referencia);
CREATE INDEX idx_ventas_numero_ticket  ON ventas          (numero_ticket);
CREATE INDEX idx_promo_codigo_activo   ON promociones     (codigo, activo);
CREATE INDEX idx_tp_id_producto        ON tarifa_productos(id_producto);

-- v16: historial y cierres
CREATE INDEX idx_ventas_fecha          ON ventas          (fecha);
CREATE INDEX idx_ventas_fecha_tipo     ON ventas          (fecha, tipo_documento);
CREATE INDEX idx_ventas_estado_aeat    ON ventas          (estado_envio_aeat);
CREATE INDEX idx_turnos_num_z          ON caja_turnos     (num_z);
CREATE INDEX idx_turnos_num_z_estado   ON caja_turnos     (num_z, estado);
CREATE INDEX idx_pv_id_venta           ON pagos_venta     (id_venta);
CREATE INDEX idx_pv_id_turno           ON pagos_venta     (id_turno);
CREATE INDEX idx_deudas_cierre         ON caja_deudas     (id_cierre_fiscal);
CREATE INDEX idx_cola_envios_venta_id  ON cola_envios     (id_venta, id);
CREATE INDEX idx_cierres_fecha         ON cierres_fiscales(fecha);

-- v17: pre-agregación de cierres
CREATE INDEX idx_ventas_num_z_estado   ON ventas (num_z, estado);

-- v18: index cubriente para listarCierres (elimina full-table-scan)
CREATE INDEX idx_ventas_cierre_agg     ON ventas (num_z, estado, fecha, total, metodo_pago);

-- v19: ajustes pendientes
CREATE INDEX idx_lag_pendientes        ON log_ajustes_globales (estado, fecha_aplicacion);

-- consolidated_schema_fixes + crear_tablas
CREATE INDEX idx_cola_estado_fecha ON cola_envios (estado, fecha_proximo_intento);
CREATE INDEX idx_ventas_perf       ON ventas      (estado, metodo_pago, fecha);
CREATE INDEX idx_prod_cat_ref      ON productos   (categoria, referencia);

-- ============================================================
-- TRIGGERS VERIFACTU (inalterabilidad fiscal RD 1007/2023)
-- ============================================================
DELIMITER //

CREATE TRIGGER tg_ventas_prevent_update BEFORE UPDATE ON ventas
FOR EACH ROW
BEGIN
    IF OLD.hash_actual IS NOT NULL THEN
        IF NEW.total            <> OLD.total            OR
           NEW.base_imponible   <> OLD.base_imponible   OR
           NEW.iva_amt          <> OLD.iva_amt          OR
           NEW.subtotal         <> OLD.subtotal         OR
           NEW.fecha            <> OLD.fecha            OR
           NEW.numero_ticket    <> OLD.numero_ticket    OR
           NEW.es_factura       <> OLD.es_factura THEN
            SIGNAL SQLSTATE '45000'
              SET MESSAGE_TEXT = 'VeriFactu: Inalterabilidad violada. No se permite modificar campos que afecten a la huella fiscal.';
        END IF;
    END IF;
END //

CREATE TRIGGER tg_ventas_prevent_delete BEFORE DELETE ON ventas
FOR EACH ROW
BEGIN
    IF OLD.hash_actual IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
          SET MESSAGE_TEXT = 'VeriFactu: Inalterabilidad violada. No se permite eliminar registros del historial fiscal.';
    END IF;
END //

DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
