-- ============================================================
-- Script: 18_smart_financing_update.sql
-- Descripción: Actualización de tablas de financiación existentes 
-- para soportar el sistema inteligente de rentabilidad.
-- ============================================================

-- 1. Actualizar tabla financieras con campos de control y coste
ALTER TABLE financieras 
    ADD COLUMN comision_cero_interes DECIMAL(5,2) DEFAULT 0.00 AFTER descripcion,
    ADD COLUMN comision_con_interes DECIMAL(5,2) DEFAULT 0.00 AFTER comision_cero_interes,
    ADD COLUMN min_importe DECIMAL(10,2) DEFAULT 150.00 AFTER comision_con_interes;

-- 2. Actualizar tabla ventas_financiacion con la modalidad seleccionada
ALTER TABLE ventas_financiacion 
    ADD COLUMN modalidad ENUM('vendedor_paga_intereses', 'cliente_paga_intereses') DEFAULT 'vendedor_paga_intereses' AFTER importe_intereses,
    ADD COLUMN estado ENUM('pendiente', 'aprobada', 'denegada') DEFAULT 'aprobada' AFTER modalidad;

-- 3. Cargar datos de ejemplo/configuración inicial para las comisiones
UPDATE financieras SET comision_cero_interes = 2.50, comision_con_interes = 0.50, min_importe = 150.00 WHERE nombre = 'CETELEM';
UPDATE financieras SET comision_cero_interes = 3.00, comision_con_interes = 0.75, min_importe = 200.00 WHERE nombre = 'SANTANDER CONSUMER';
UPDATE financieras SET comision_cero_interes = 2.20, comision_con_interes = 0.40, min_importe = 120.00 WHERE nombre = 'CAIXABANK PAY';
UPDATE financieras SET comision_cero_interes = 0.00, comision_con_interes = 0.00, min_importe = 50.00 WHERE nombre = 'FINANCIACIÓN PROPIA';
