-- ============================================================
-- SCRIPT: CONTROL DE FLUJO VERIFACTU
-- ============================================================

USE `dbelectrobazar-tpv`;

INSERT INTO configuracion (clave, valor, descripcion) VALUES 
('verifactu_espera_hasta', '0', 'Timestamp (Unix) hasta el cual se debe esperar para el siguiente envío'),
('verifactu_min_lote', '1', 'Número mínimo de registros a enviar por lote según AEAT')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);
