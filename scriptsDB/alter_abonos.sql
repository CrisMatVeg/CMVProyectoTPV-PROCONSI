-- ============================================================
-- MIGRACIÓN: Sistema de Tickets de Abono
-- Proyecto: ElectroBazar TPV
-- Ejecutar sobre la BD existente para añadir soporte de abonos.
-- ============================================================

USE `dbelectrobazar-tpv`;

-- 1. Añadir tipo de documento (venta / abono)
ALTER TABLE ventas
    ADD COLUMN IF NOT EXISTS tipo_documento ENUM('venta', 'abono') NOT NULL DEFAULT 'venta' AFTER id_turno;

-- 2. Añadir FK al ticket de venta de origen (solo en abonos)
ALTER TABLE ventas
    ADD COLUMN IF NOT EXISTS id_venta_origen INT NULL DEFAULT NULL AFTER tipo_documento;

ALTER TABLE ventas
    ADD CONSTRAINT IF NOT EXISTS fk_ventas_origen FOREIGN KEY (id_venta_origen)
    REFERENCES ventas(id) ON DELETE SET NULL;

-- 3. Ampliar ENUM de estado para incluir 'parcialmente_devuelta'
--    (MySQL no permite ADD IF NOT EXISTS en ENUM; verificar antes de ejecutar)
ALTER TABLE ventas
    MODIFY COLUMN estado ENUM('completada', 'devuelta', 'anulada', 'pendiente_pago', 'parcialmente_devuelta') DEFAULT 'completada';

-- 4. Índice para búsquedas por tipo_documento y origen
CREATE INDEX IF NOT EXISTS idx_ventas_tipo ON ventas(tipo_documento);
CREATE INDEX IF NOT EXISTS idx_ventas_origen ON ventas(id_venta_origen);
