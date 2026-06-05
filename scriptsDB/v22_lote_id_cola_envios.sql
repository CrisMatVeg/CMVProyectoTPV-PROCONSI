-- v22: Añadir lote_id a cola_envios para identificar registros enviados en el mismo lote VeriFactu
ALTER TABLE cola_envios
  ADD COLUMN lote_id VARCHAR(64) NULL DEFAULT NULL AFTER fecha_envio;

CREATE INDEX idx_cola_lote_id ON cola_envios (lote_id);
