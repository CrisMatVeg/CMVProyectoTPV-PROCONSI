-- Migración v6: Añadir 'mixto' al ENUM de metodo_pago en tabla ventas
-- Ejecutar una única vez en la base de datos de producción.

ALTER TABLE ventas
    MODIFY COLUMN metodo_pago ENUM('efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'mixto') NOT NULL;
