-- Crear tabla de roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
);

-- Crear tabla de permisos
CREATE TABLE IF NOT EXISTS permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
);

-- Tabla para enlazar roles con permisos
CREATE TABLE IF NOT EXISTS rol_permisos (
    id_rol INT NOT NULL,
    id_permiso INT NOT NULL,
    PRIMARY KEY (id_rol, id_permiso),
    FOREIGN KEY (id_rol) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (id_permiso) REFERENCES permisos(id) ON DELETE CASCADE
);

-- Insertar roles base
INSERT IGNORE INTO roles (id, nombre, descripcion) VALUES 
(1, 'Administrador', 'Acceso total al sistema'),
(2, 'Cajero', 'Venta y gestión básica de caja');

-- Insertar permisos base
INSERT IGNORE INTO permisos (id, clave, descripcion) VALUES 
(1, 'acceso_tpv', 'Poder entrar en la interfaz de venta'),
(2, 'gestionar_productos', 'Añadir, editar o eliminar productos'),
(3, 'ver_historial', 'Acceder al histórico de ventas'),
(4, 'ver_analitica', 'Ver informes fiscales y de rendimiento'),
(5, 'gestionar_usuarios', 'Administrar personal y roles'),
(6, 'cerrar_caja', 'Realizar cierres Z'),
(7, 'gestionar_clientes', 'Administrar base de datos de clientes');

-- Asignar permisos a Admin (todos)
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso) 
SELECT 1, id FROM permisos;

-- Asignar permisos a Cajero (TPV, Clientes, Cierre parcial(?))
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso) VALUES 
(2, 1), -- acceso_tpv
(2, 6), -- cerrar_caja
(2, 7); -- gestionar_clientes

-- Migrar usuarios actuales:
-- Primero añadimos una columna id_rol para enlazar
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS id_rol INT;

-- Asignamos id_rol según el ENUM anterior
UPDATE usuarios SET id_rol = 1 WHERE rol = 'admin';
UPDATE usuarios SET id_rol = 2 WHERE rol = 'cajero';

-- Si ya existe id_rol, la establecemos como clave foránea (opcional pero recomendado)
-- ALTER TABLE usuarios ADD CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES roles(id);
