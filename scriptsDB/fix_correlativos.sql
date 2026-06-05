-- Fix: Crear tabla de correlativos para evitar race conditions en tickets
CREATE TABLE IF NOT EXISTS correlativos (
    nombre VARCHAR(50) PRIMARY KEY,
    valor INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inicializar con el máximo actual de ventas para evitar duplicados
INSERT IGNORE INTO correlativos (nombre, valor)
SELECT 'ticket', COALESCE(MAX(numero_ticket), 0) FROM ventas;
