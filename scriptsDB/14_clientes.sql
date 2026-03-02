-- Tabla de clientes: datos básicos y flag de socio
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('particular','empresa') NOT NULL DEFAULT 'particular',
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NULL,
    nif VARCHAR(20) NULL,
    email VARCHAR(120) NULL,
    telefono VARCHAR(30) NULL,
    direccion VARCHAR(255) NULL,
    cp VARCHAR(10) NULL,
    poblacion VARCHAR(100) NULL,
    provincia VARCHAR(100) NULL,
    es_socio TINYINT(1) NOT NULL DEFAULT 0,
    fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_baja DATETIME NULL,
    notas TEXT NULL,
    UNIQUE KEY uk_nif (nif)
);

-- Si la tabla ya existía con una estructura antigua, normalizamos columnas
ALTER TABLE clientes
    ADD COLUMN IF NOT EXISTS tipo ENUM('particular','empresa') NOT NULL DEFAULT 'particular' AFTER id,
    ADD COLUMN IF NOT EXISTS apellidos VARCHAR(150) NULL AFTER nombre,
    MODIFY COLUMN nif VARCHAR(20) NULL,
    MODIFY COLUMN email VARCHAR(120) NULL,
    MODIFY COLUMN telefono VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS direccion VARCHAR(255) NULL AFTER telefono,
    ADD COLUMN IF NOT EXISTS cp VARCHAR(10) NULL AFTER direccion,
    ADD COLUMN IF NOT EXISTS poblacion VARCHAR(100) NULL AFTER cp,
    ADD COLUMN IF NOT EXISTS provincia VARCHAR(100) NULL AFTER poblacion,
    ADD COLUMN IF NOT EXISTS fecha_baja DATETIME NULL AFTER fecha_alta,
    ADD COLUMN IF NOT EXISTS notas TEXT NULL AFTER fecha_baja;


