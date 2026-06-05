-- v16: índices para rendimiento del historial de ventas y cierres
-- Ejecutar una vez. Usa IF NOT EXISTS → idempotente en MySQL 8.0+.

-- ventas.fecha: columna más usada en historial (filtro de rango).
-- Sin este índice cada carga del historial hace full-table-scan.
ALTER TABLE ventas
    ADD INDEX IF NOT EXISTS idx_ventas_fecha (fecha);

-- ventas(fecha, tipo_documento): cubre el caso combinado fecha + tipo en contarVentas/buscarVentas
ALTER TABLE ventas
    ADD INDEX IF NOT EXISTS idx_ventas_fecha_tipo (fecha, tipo_documento);

-- ventas.estado_envio_aeat: filtro "solo incidencias VeriFactu"
ALTER TABLE ventas
    ADD INDEX IF NOT EXISTS idx_ventas_estado_aeat (estado_envio_aeat);

-- caja_turnos.num_z: lookup de turnos por cierre en listarTurnosPorCierre y UPDATEs del cierre
ALTER TABLE caja_turnos
    ADD INDEX IF NOT EXISTS idx_turnos_num_z (num_z);

-- caja_turnos(num_z, estado): cubre el UPDATE de realizarCierre
ALTER TABLE caja_turnos
    ADD INDEX IF NOT EXISTS idx_turnos_num_z_estado (num_z, estado);

-- pagos_venta.id_venta: usado por PagoPDO::obtenerPagosPorVenta
ALTER TABLE pagos_venta
    ADD INDEX IF NOT EXISTS idx_pv_id_venta (id_venta);

-- pagos_venta.id_turno: usado por obtenerResumenParaCierre y obtenerTotalesMetodosTurno
ALTER TABLE pagos_venta
    ADD INDEX IF NOT EXISTS idx_pv_id_turno (id_turno);

-- caja_deudas.id_cierre_fiscal: usado en JOIN de listarCierres
ALTER TABLE caja_deudas
    ADD INDEX IF NOT EXISTS idx_deudas_cierre (id_cierre_fiscal);

-- cola_envios(id_venta, id): cubre el MAX(id) GROUP BY id_venta en buscarVentas
-- id_venta ya indexado (v12), pero el compuesto permite index-only scan
ALTER TABLE cola_envios
    ADD INDEX IF NOT EXISTS idx_cola_envios_venta_id (id_venta, id);

-- cierres_fiscales.fecha: filtro de rango en listarCierres y contarCierres
ALTER TABLE cierres_fiscales
    ADD INDEX IF NOT EXISTS idx_cierres_fecha (fecha);
