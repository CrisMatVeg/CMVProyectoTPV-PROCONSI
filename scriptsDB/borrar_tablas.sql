-- ============================================================
-- borrar_tablas.sql
-- Proyecto: ElectroBazar TPV
-- Elimina todas las tablas y triggers antes de restaurar.
-- Ejecutar ANTES de estructura_final.sql.
-- ============================================================

USE `electrobazar`;

SET FOREIGN_KEY_CHECKS = 0;

-- TRIGGERS (deben eliminarse antes que las tablas que los usan)
DROP TRIGGER IF EXISTS tg_ventas_prevent_update;
DROP TRIGGER IF EXISTS tg_ventas_prevent_delete;

-- 1. ANALÍTICA Y LOGS
DROP TABLE IF EXISTS analitica_iva_diario;
DROP TABLE IF EXISTS analitica_producto_diario;
DROP TABLE IF EXISTS analitica_resumen_diario;
DROP TABLE IF EXISTS log_ajustes_globales;
DROP TABLE IF EXISTS logs_sistema;

-- 2. VERIFACTU
DROP TABLE IF EXISTS verifactu_logs;
DROP TABLE IF EXISTS verifactu_eventos;
DROP TABLE IF EXISTS cola_envios;

-- 3. PAGOS, VALES, CORRELATIVOS
DROP TABLE IF EXISTS pagos_venta;
DROP TABLE IF EXISTS vales;
DROP TABLE IF EXISTS correlativos;

-- 4. FINANCIACIÓN
DROP TABLE IF EXISTS ventas_financiacion;
DROP TABLE IF EXISTS comisiones_plazo;
DROP TABLE IF EXISTS financieras;

-- 5. DESCUENTOS Y TARIFAS
DROP TABLE IF EXISTS lineas_venta_descuentos;
DROP TABLE IF EXISTS promocion_productos;
DROP TABLE IF EXISTS tarifa_productos;
DROP TABLE IF EXISTS promociones;
DROP TABLE IF EXISTS tarifas_precios;

-- 6. VENTAS Y LÍNEAS
DROP TABLE IF EXISTS lineas_serie_venta;
DROP TABLE IF EXISTS lineas_venta;
DROP TABLE IF EXISTS ventas;

-- 7. CAJA Y TURNOS
DROP TABLE IF EXISTS caja_deudas;
DROP TABLE IF EXISTS caja_movimientos;
DROP TABLE IF EXISTS caja_turnos;
DROP TABLE IF EXISTS cierres_fiscales;

-- 8. COMPRAS
DROP TABLE IF EXISTS lineas_compra;
DROP TABLE IF EXISTS albaranes_compra;
DROP TABLE IF EXISTS facturas_compra_prov;

-- 9. PRODUCTOS Y STOCK
DROP TABLE IF EXISTS auditoria_precios_base;
DROP TABLE IF EXISTS movimientos_stock;
DROP TABLE IF EXISTS entradas_stock;
DROP TABLE IF EXISTS numeros_serie;
DROP TABLE IF EXISTS productos_pack;
DROP TABLE IF EXISTS productos;

-- 10. CATEGORÍAS Y ROLES CLIENTE
DROP TABLE IF EXISTS roles_cliente;
DROP TABLE IF EXISTS categorias;

-- 11. ENTIDADES DE NEGOCIO
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS proveedores;

-- 12. CONFIGURACIÓN Y TIPOS IVA
DROP TABLE IF EXISTS tipos_iva;
DROP TABLE IF EXISTS configuracion;

-- 13. ROLES Y PERMISOS
DROP TABLE IF EXISTS rol_permisos;
DROP TABLE IF EXISTS permisos;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;
