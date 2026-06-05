-- v19: Ajuste masivo unificado con plazo de aplicación
-- Añade columnas a log_ajustes_globales para soportar ajustes programados

ALTER TABLE log_ajustes_globales
    ADD COLUMN fecha_aplicacion DATETIME NULL DEFAULT NULL AFTER motivo,
    ADD COLUMN estado           VARCHAR(20) NOT NULL DEFAULT 'aplicado' AFTER fecha_aplicacion,
    ADD COLUMN params_json      TEXT NULL AFTER estado;

-- Índice para que la consulta de pendientes sea eficiente
CREATE INDEX IF NOT EXISTS idx_lag_pendientes ON log_ajustes_globales (estado, fecha_aplicacion);
