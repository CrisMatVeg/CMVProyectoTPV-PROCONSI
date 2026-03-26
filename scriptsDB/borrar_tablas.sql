-- ============================================================
-- SCRIPT 2: ELIMINACIÓN DE TABLAS (borrar_tablas.sql)
-- Proyecto: ElectroBazar TPV
-- ============================================================

USE `dbelectrobazar-tpv`;

-- Desactivar llaves foráneas para evitar errores de restricción
SET FOREIGN_KEY_CHECKS = 0;

-- 1. OTROS
DROP TABLE IF EXISTS pagos_venta;
DROP TABLE IF EXISTS logs_sistema;
DROP TABLE IF EXISTS vales;
DROP TABLE IF EXISTS roles_cliente;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS promociones;
DROP TABLE IF EXISTS tarifa_productos;
DROP TABLE IF EXISTS tarifas_precios;

-- 2. VENTAS
DROP TABLE IF EXISTS lineas_serie_venta;
DROP TABLE IF EXISTS lineas_venta_descuentos;
DROP TABLE IF EXISTS lineas_venta;
DROP TABLE IF EXISTS ventas;

-- 3. CAJA Y TURNOS
DROP TABLE IF EXISTS caja_deudas;
DROP TABLE IF EXISTS caja_movimientos;
DROP TABLE IF EXISTS caja_turnos;
DROP TABLE IF EXISTS cierres_fiscales;

-- 4. PRODUCTOS Y STOCK
DROP TABLE IF EXISTS auditoria_precios_base;
DROP TABLE IF EXISTS movimientos_stock;
DROP TABLE IF EXISTS entradas_stock;
DROP TABLE IF EXISTS lineas_compra;
DROP TABLE IF EXISTS albaranes_compra;
DROP TABLE IF EXISTS facturas_compra_prov;
DROP TABLE IF EXISTS numeros_serie;
DROP TABLE IF EXISTS productos_pack;
DROP TABLE IF EXISTS productos;

-- 5. ENTIDADES DE NEGOCIO
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS proveedores;

-- 6. CONFIGURACIÓN Y TIPOS IVA
DROP TABLE IF EXISTS tipos_iva;
DROP TABLE IF EXISTS configuracion;

-- 7. ROLES Y PERMISOS
DROP TABLE IF EXISTS rol_permisos;
DROP TABLE IF EXISTS permisos;
DROP TABLE IF EXISTS roles;

-- Reactivar llaves foráneas
SET FOREIGN_KEY_CHECKS = 1;
