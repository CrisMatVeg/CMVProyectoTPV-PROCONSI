-- Permite id_producto NULL en lineas_venta para productos comodín (productos ad-hoc sin ID)
ALTER TABLE lineas_venta
    MODIFY COLUMN id_producto INT NULL,
    ADD CONSTRAINT fk_lv_prod FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE SET NULL;
