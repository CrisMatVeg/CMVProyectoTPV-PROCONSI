-- ============================================================
-- Script: 24_cleanup_monedero.sql
-- Descripción: Elimina el soporte de saldo/monedero que no se usará.
--              Mantiene el método de reembolso pero renombrado a 'vale'.
-- ============================================================

USE `dbelectrobazar-tpv`;

-- 1. Eliminar tabla de movimientos de saldo
DROP TABLE IF EXISTS clientes_saldos_movimientos;

-- 2. Eliminar columna saldo de clientes
ALTER TABLE clientes DROP COLUMN IF EXISTS saldo;

-- 3. Renombrar 'saldo_cliente' a 'vale' en el ENUM de lineas_venta
-- Primero pasamos a un tipo temporal o permitimos el nuevo valor
ALTER TABLE lineas_venta MODIFY COLUMN metodo_reembolso ENUM('efectivo', 'saldo_cliente', 'reemplazo', 'vale', 'otros') NULL DEFAULT NULL;

-- Migramos los datos si existiera alguno (poco probable por el tiempo transcurrido)
UPDATE lineas_venta SET metodo_reembolso = 'vale' WHERE metodo_reembolso = 'saldo_cliente';

-- Dejamos el ENUM definitivo sin 'saldo_cliente'
ALTER TABLE lineas_venta MODIFY COLUMN metodo_reembolso ENUM('efectivo', 'vale', 'reemplazo', 'otros') NULL DEFAULT NULL;
