-- ============================================================
-- MIGRACIÓN v6: Estado 'bloqueado' para cola VeriFactu
-- Ejecutar sobre la BD existente una sola vez.
-- ============================================================

USE `dbelectrobazar-tpv`;

-- 1. Ampliar el ENUM de ventas con los estados reales usados en el código
ALTER TABLE ventas
    MODIFY COLUMN estado_envio_aeat
        ENUM('pendiente','enviado','error_critico','subsanacion_pendiente','bloqueado','anulado_pendiente')
        NOT NULL DEFAULT 'pendiente';

-- 2. Ampliar el ENUM de cola_envios y hacer xml_path nullable
--    (encolarError puede insertar sin xml cuando la generación del XML falla)
ALTER TABLE cola_envios
    MODIFY COLUMN xml_path VARCHAR(255) NULL,
    MODIFY COLUMN estado
        ENUM('pendiente','enviado','error_critico','bloqueado')
        NOT NULL DEFAULT 'pendiente';

-- 3. Limpiar estado legacy 'error' que ya no existe en el ENUM
--    (si hubiese filas con ese valor, se convierten a pendiente para reintento)
UPDATE cola_envios SET estado = 'pendiente' WHERE estado NOT IN ('pendiente','enviado','error_critico','bloqueado');
UPDATE ventas SET estado_envio_aeat = 'pendiente'
    WHERE estado_envio_aeat NOT IN ('pendiente','enviado','error_critico','subsanacion_pendiente','bloqueado','anulado_pendiente');
