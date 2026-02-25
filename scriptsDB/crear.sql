CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Guardaremos el hash, nunca la clave plana
    rol ENUM('admin', 'cajero') DEFAULT 'cajero',
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertamos un administrador por defecto (password: adminpaso)
-- Nota: En PHP usaremos password_hash(), esto es solo para el ejemplo
INSERT INTO usuarios (nombre_completo, username, password, rol) 
VALUES ('Administrador', 'admin', SHA2('adminpaso',256), 'admin');