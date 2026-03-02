-- Añadir usuario a los movimientos de caja (para saber quién hace cada retirada)
ALTER TABLE caja_movimientos
    ADD COLUMN IF NOT EXISTS id_usuario INT NULL,
    ADD CONSTRAINT fk_caja_movimientos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id);

