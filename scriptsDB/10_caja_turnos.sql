-- Gestión de turnos de caja y retiradas de efectivo

CREATE TABLE IF NOT EXISTS caja_turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario_apertura INT NOT NULL,
    fondo_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_cierre DATETIME NULL,
    id_usuario_cierre INT NULL,
    efectivo_real DECIMAL(10,2) NULL,
    fondo_siguiente_turno DECIMAL(10,2) NULL,
    total_retirado DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto'
);

CREATE TABLE IF NOT EXISTS caja_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_turno INT NOT NULL,
    tipo ENUM('retiro') NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    concepto VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_caja_movimientos_turno FOREIGN KEY (id_turno) REFERENCES caja_turnos(id)
);