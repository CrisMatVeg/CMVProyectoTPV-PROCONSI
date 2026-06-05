-- v21: Corregir columnas NOT NULL en tabla promociones.
-- id_producto y excluidos pueden ser NULL (promo sin producto específico).
-- Numéricos sin default causan error si el frontend no los envía.
ALTER TABLE promociones
  MODIFY id_producto    INT(11)       NULL DEFAULT NULL,
  MODIFY excluidos      LONGBLOB      NULL DEFAULT NULL,
  MODIFY min_subtotal   DECIMAL(12,2) NOT NULL DEFAULT 0,
  MODIFY bundle_buy_qty INT(11)       NOT NULL DEFAULT 0,
  MODIFY bundle_pay_qty INT(11)       NOT NULL DEFAULT 0,
  MODIFY prioridad      INT(11)       NOT NULL DEFAULT 0;
