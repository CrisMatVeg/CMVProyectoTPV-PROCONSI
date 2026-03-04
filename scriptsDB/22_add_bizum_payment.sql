-- ============================================================
-- Script: 22_add_bizum_payment.sql
-- Descripción: Añade Bizum como método de pago en ventas y cierres fiscales.
-- ============================================================

USE electrobazar_tpv;

-- 1. Actualizar el ENUM de metodo_pago en la tabla ventas
ALTER TABLE ventas MODIFY COLUMN metodo_pago ENUM('efectivo','tarjeta','bizum','financiado') NOT NULL;

-- 2. Añadir columnas para totales por método en cierres fiscales
ALTER TABLE cierres_fiscales ADD COLUMN total_bizum DECIMAL(10,2) DEFAULT 0.00 AFTER total_tarjeta;
ALTER TABLE cierres_fiscales ADD COLUMN total_financiado DECIMAL(10,2) DEFAULT 0.00 AFTER total_bizum;
