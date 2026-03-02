-- Tabla de promociones / descuentos
CREATE TABLE IF NOT EXISTS promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    tipo ENUM('percent','amount') NOT NULL DEFAULT 'percent',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    min_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    solo_socios TINYINT(1) NOT NULL DEFAULT 0,
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

