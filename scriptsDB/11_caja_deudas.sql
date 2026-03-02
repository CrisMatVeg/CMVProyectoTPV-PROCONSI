-- Registro de deudas de caja (faltantes de efectivo que se deberán devolver)

CREATE TABLE IF NOT EXISTS caja_deudas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cierre_fiscal INT NOT NULL,
    id_usuario INT NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    concepto VARCHAR(255) NULL,
    saldada TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_saldada DATETIME NULL,
    CONSTRAINT fk_caja_deudas_cierre FOREIGN KEY (id_cierre_fiscal) REFERENCES cierres_fiscales(id),
    CONSTRAINT fk_caja_deudas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

