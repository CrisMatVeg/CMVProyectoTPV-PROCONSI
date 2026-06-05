-- v17: índice compuesto ventas(num_z, estado) para la pre-agregación de listarCierres
-- Permite GROUP BY num_z con filtro de estado sin full-table-scan.
ALTER TABLE ventas
    ADD INDEX IF NOT EXISTS idx_ventas_num_z_estado (num_z, estado);
