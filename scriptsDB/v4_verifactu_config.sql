-- ============================================================
-- SCRIPT: CONFIGURACIÓN VERIFACTU
-- ============================================================

USE `dbelectrobazar-tpv`;

INSERT INTO configuracion (clave, valor, descripcion) VALUES 
('verifactu_nombre_sistema', 'ElectroBazar TPV', 'Nombre oficial del sistema informático ante AEAT'),
('verifactu_id_sistema', '01', 'Identificador único del sistema informático (asignado por desarrollador)'),
('verifactu_version_sistema', '1.0.0', 'Versión actual del software'),
('verifactu_num_instalacion', 'INST001', 'Número de instalación o licencia'),
('verifactu_cert_path', 'config/cert/certificado.p12', 'Ruta al certificado .p12 para firma digital'),
('verifactu_cert_pass', '', 'Contraseña del certificado .p12 (se recomienda cifrar)'),
('verifactu_modo_test', '1', '1 para modo pruebas (Sandbox), 0 para producción'),
('verifactu_remision_voluntaria', '1', 'Indica si se envía inmediatamente (1) o bajo requerimiento (0)')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);
