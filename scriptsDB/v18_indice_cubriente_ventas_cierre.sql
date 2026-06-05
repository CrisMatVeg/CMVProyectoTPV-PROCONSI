-- v18: índice cubriente para la pre-agregación de ventas en listarCierres
-- Cubre todas las columnas del SELECT de aggregation: num_z, estado, id, fecha, total, metodo_pago
-- Con este índice MariaDB hace index-only scan (sin tocar la tabla base) al calcular
-- COUNT/MIN/MAX/SUM por num_z → elimina el full-table-scan de 982K filas.
ALTER TABLE ventas
    ADD INDEX IF NOT EXISTS idx_ventas_cierre_agg (num_z, estado, fecha, total, metodo_pago);
