-- ============================================================
-- Script: 19_financiacion_por_plazo.sql
-- Descripción: Actualiza el sistema de financiación para soportar
--              comisiones diferentes según el plazo (3, 6, 12, 18 meses)
-- ============================================================

USE electrobazar_tpv;

-- 1. Crear tabla de comisiones por plazo
CREATE TABLE IF NOT EXISTS comisiones_plazo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_financiera INT NOT NULL,
    meses INT NOT NULL,
    comision_sin_interes DECIMAL(5,2) NOT NULL DEFAULT 0.00,  -- Tienda paga intereses
    comision_con_interes DECIMAL(5,2) NOT NULL DEFAULT 0.00,  -- Cliente paga intereses
    UNIQUE KEY unique_financiera_meses (id_financiera, meses),
    CONSTRAINT fk_comision_financiera FOREIGN KEY (id_financiera) REFERENCES financieras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Limpiar datos anteriores de prueba si existen
DELETE FROM comisiones_plazo;
DELETE FROM financieras WHERE id > 0;
ALTER TABLE financieras AUTO_INCREMENT = 1;

-- 3. Insertar nuevas entidades financieras
INSERT INTO financieras (nombre, descripcion, min_importe, activo) VALUES 
('CETELEM', 'Operador financiero especializado en crédito al consumo', 120.00, 1),
('SANTANDER CONSUMER', 'División de financiación retail del Santander', 150.00, 1),
('CAIXABANK PAY', 'Plataforma de pago y financiación de CaixaBank', 100.00, 1),
('BBVA FINANCIACIÓN', 'Soluciones de financiación para punto de venta', 130.00, 1),
('ING DIRECT', 'Financiación inmediata online', 110.00, 1),
('CAJASUR FINANCIERA', 'Crédito personal y líneas de financiación', 140.00, 1),
('FINANCIACIÓN PROPIA', 'Gestión interna de financiación a socios y clientes habituales', 50.00, 1),
('BANCAJA', 'Financiación flexible de bancaja', 125.00, 1),
('TARJETA REVOLVING', 'Línea de crédito con tarjeta revolving', 80.00, 1);

-- 4. Insertar comisiones por plazo - CETELEM
INSERT INTO comisiones_plazo (id_financiera, meses, comision_sin_interes, comision_con_interes) VALUES 
(1, 3, 2.50, 0.30),
(1, 6, 4.25, 0.50),
(1, 12, 7.50, 1.00),
(1, 18, 10.50, 1.50),
(1, 24, 13.00, 2.00),
(1, 36, 15.00, 2.50);

-- 5. Insertar comisiones por plazo - SANTANDER CONSUMER
INSERT INTO comisiones_plazo (id_financiera, meses, comision_sin_interes, comision_con_interes) VALUES 
(2, 3, 3.00, 0.40),
(2, 6, 4.80, 0.65),
(2, 12, 8.20, 1.20),
(2, 18, 11.00, 1.80),
(2, 24, 13.50, 2.30),
(2, 36, 16.00, 3.00);

-- 6. Insertar comisiones por plazo - CAIXABANK PAY
INSERT INTO comisiones_plazo (id_financiera, meses, comision_sin_interes, comision_con_interes) VALUES 
(3, 3, 2.20, 0.25),
(3, 6, 3.90, 0.45),
(3, 12, 6.80, 0.95),
(3, 18, 9.50, 1.40),
(3, 24, 12.00, 1.90),
(3, 36, 14.50, 2.40);

-- 7. Insertar comisiones por plazo - BBVA FINANCIACIÓN
INSERT INTO comisiones_plazo (id_financiera, meses, comision_sin_interes, comision_con_interes) VALUES 
(4, 3, 2.80, 0.35),
(4, 6, 4.50, 0.55),
(4, 12, 7.80, 1.10),
(4, 18, 10.80, 1.70),
(4, 24, 13.20, 2.20),
(4, 36, 15.50, 2.75);

-- 8. Insertar comisiones por plazo - ING DIRECT
INSERT INTO comisiones_plazo (id_financiera, meses, comision_sin_interes, comision_con_interes) VALUES 
(5, 3, 2.40, 0.32),
(5, 6, 4.10, 0.50),
(5, 12, 7.20, 1.05),
(5, 18, 10.00, 1.60),
(5, 24, 12.80, 2.10),
(5, 36, 15.20, 2.65);

-- 9. Insertar comisiones por plazo - CAJASUR FINANCIERA
INSERT INTO comisiones_plazo (id_financiera, meses, comision_sin_interes, comision_con_interes) VALUES 
(6, 3, 2.60, 0.30),
(6, 6, 4.30, 0.52),
(6, 12, 7.60, 1.08),
(6, 18, 10.30, 1.65),
(6, 24, 13.10, 2.15),
(6, 36, 15.40, 2.70);

-- 10. Insertar comisiones por plazo - FINANCIACIÓN PROPIA
INSERT INTO comisiones_plazo (id_financiera, meses, comision_sin_interes, comision_con_interes) VALUES 
(7, 3, 0.00, 0.00),
(7, 6, 0.00, 0.00),
(7, 12, 0.00, 0.00),
(7, 18, 0.00, 0.00),
(7, 24, 0.00, 0.00),
(7, 36, 0.00, 0.00);

-- 11. Insertar comisiones por plazo - BANCAJA
INSERT INTO comisiones_plazo (id_financiera, meses, comision_sin_interes, comision_con_interes) VALUES 
(8, 3, 2.70, 0.33),
(8, 6, 4.40, 0.53),
(8, 12, 7.70, 1.12),
(8, 18, 10.50, 1.72),
(8, 24, 13.30, 2.25),
(8, 36, 15.60, 2.80);

-- 12. Insertar comisiones por plazo - TARJETA REVOLVING
INSERT INTO comisiones_plazo (id_financiera, meses, comision_sin_interes, comision_con_interes) VALUES 
(9, 3, 2.90, 0.38),
(9, 6, 4.60, 0.58),
(9, 12, 8.00, 1.15),
(9, 18, 11.00, 1.75),
(9, 24, 13.80, 2.30),
(9, 36, 16.00, 2.90);

-- 13. Verificar datos insertados
SELECT 'Financieras registradas:' AS info;
SELECT id, nombre, min_importe, activo FROM financieras ORDER BY id;

SELECT '' AS '';
SELECT 'Comisiones por plazo (ejemplo CETELEM):' AS info;
SELECT f.nombre, cp.meses, cp.comision_sin_interes, cp.comision_con_interes 
FROM comisiones_plazo cp
JOIN financieras f ON cp.id_financiera = f.id
WHERE f.nombre = 'CETELEM'
ORDER BY cp.meses;

SELECT '' AS '';
SELECT 'Total de combinaciones financiera-plazo:' AS info;
SELECT COUNT(*) AS total FROM comisiones_plazo;
