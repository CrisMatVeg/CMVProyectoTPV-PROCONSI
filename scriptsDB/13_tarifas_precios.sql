-- Tarifas de precios: registro de subidas/bajadas globales

CREATE TABLE IF NOT EXISTS tarifas_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('percent','amount') NOT NULL DEFAULT 'percent',
    valor DECIMAL(10,2) NOT NULL,
    fecha_aplicacion DATE NOT NULL,
    aplicada TINYINT(1) NOT NULL DEFAULT 0,
    scope ENUM('todos','categoria','productos') NOT NULL DEFAULT 'todos',
    categoria VARCHAR(50) NULL,
    producto_ids TEXT NULL,
    creado_por INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tarifa_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

-- Si la tabla ya existía sin estos campos, los añadimos de forma segura
ALTER TABLE tarifas_precios
    ADD COLUMN IF NOT EXISTS scope ENUM('todos','categoria','productos') NOT NULL DEFAULT 'todos' AFTER aplicada,
    ADD COLUMN IF NOT EXISTS categoria VARCHAR(50) NULL AFTER scope,
    ADD COLUMN IF NOT EXISTS producto_ids TEXT NULL AFTER categoria;

