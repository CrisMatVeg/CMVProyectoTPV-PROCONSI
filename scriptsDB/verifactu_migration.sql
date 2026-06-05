-- ============================================================
-- SCRIPT: MIGRACIÓN VERIFACTU (RD 1007/2023)
-- Proyecto: ElectroBazar TPV
-- ============================================================

USE `dbelectrobazar-tpv`;

-- 1. ADICIÓN DE CAMPOS PARA INTEGRIDAD Y ENCADENAMIENTO
-- Usamos ALTER TABLE para no perder datos existentes.
ALTER TABLE ventas
ADD COLUMN hash_actual VARCHAR(64) DEFAULT NULL COMMENT 'Huella del registro actual (SHA256 normalized)',
ADD COLUMN hash_anterior VARCHAR(64) DEFAULT NULL COMMENT 'Huella del registro anterior para encadenamiento',
ADD COLUMN firma_digital LONGTEXT DEFAULT NULL COMMENT 'Firma XAdES del registro',
ADD COLUMN estado_envio_aeat ENUM('pendiente', 'enviado', 'error') DEFAULT 'pendiente' NOT NULL,
ADD COLUMN codigo_qr TEXT DEFAULT NULL COMMENT 'Contenido para el código QR oficial';

-- 2. TRIGGERS PARA ASEGURAR LA INALTERABILIDAD
-- Estos triggers bloquean cambios en registros que ya han sido "emitidos" (tienen hash_actual).

DELIMITER //

-- Bloqueo de UPDATE en campos críticos
CREATE TRIGGER tg_ventas_prevent_update BEFORE UPDATE ON ventas
FOR EACH ROW
BEGIN
    -- Si el registro ya tiene hash (ha sido emitido y encadenado), bloqueamos cambios en campos fiscales
    IF OLD.hash_actual IS NOT NULL THEN
        IF NEW.total <> OLD.total OR 
           NEW.base_imponible <> OLD.base_imponible OR 
           NEW.iva_amt <> OLD.iva_amt OR 
           NEW.subtotal <> OLD.subtotal OR
           NEW.fecha <> OLD.fecha OR
           NEW.numero_ticket <> OLD.numero_ticket OR
           NEW.nif_cliente <> OLD.nif_cliente THEN
           
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'VeriFactu: Inalterabilidad violada. No se permite modificar campos fiscales de una factura ya emitida.';
        END IF;
    END IF;
END //

-- Bloqueo de DELETE
CREATE TRIGGER tg_ventas_prevent_delete BEFORE DELETE ON ventas
FOR EACH ROW
BEGIN
    IF OLD.hash_actual IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'VeriFactu: Inalterabilidad violada. No se permite eliminar registros del historial fiscal.';
    END IF;
END //

DELIMITER ;
