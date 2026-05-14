-- Migración v12: Índices explícitos en claves foráneas de tablas de alto volumen
-- InnoDB crea índices para FK automáticamente, pero declararlos explícitamente
-- permite al optimizador usarlos con más consistencia entre versiones de MySQL.

USE `dbelectrobazar-tpv`;

CREATE INDEX IF NOT EXISTS idx_ventas_id_usuario ON ventas (id_usuario);
CREATE INDEX IF NOT EXISTS idx_ventas_id_cliente  ON ventas (id_cliente);
CREATE INDEX IF NOT EXISTS idx_ventas_num_z        ON ventas (num_z);
CREATE INDEX IF NOT EXISTS idx_ventas_id_turno     ON ventas (id_turno);

CREATE INDEX IF NOT EXISTS idx_lineas_id_venta ON lineas_venta (id_venta);

CREATE INDEX IF NOT EXISTS idx_cola_id_venta ON cola_envios (id_venta);
