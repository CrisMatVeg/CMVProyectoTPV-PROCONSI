-- v20: Hacer nullable las columnas id_cliente, excluidos y cliente_ids en tarifas_precios.
-- id_cliente era NOT NULL pero las tarifas generales (sin cliente específico) envían NULL.
ALTER TABLE tarifas_precios
  MODIFY id_cliente  INT(11)   NULL DEFAULT NULL,
  MODIFY excluidos   LONGBLOB  NULL DEFAULT NULL,
  MODIFY cliente_ids LONGBLOB  NULL DEFAULT NULL;
