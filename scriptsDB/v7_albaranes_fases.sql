-- Migración para añadir fases a los albaranes de compra
-- 1. Actualizar el ENUM del campo estado para incluir 'recibido' y 'validado'
ALTER TABLE albaranes_compra MODIFY COLUMN estado ENUM('recibido', 'validado', 'facturado', 'anulado') DEFAULT 'recibido';

-- 2. Migrar los albaranes existentes en estado 'pendiente' (si hubiera alguno con el string antiguo)
-- Nota: En la definición anterior era 'pendiente', lo pasamos a 'validado' porque esos ya afectaron al stock.
-- Ejecutamos una actualización segura.
UPDATE albaranes_compra SET estado = 'validado' WHERE estado = 'pendiente';
UPDATE albaranes_compra SET estado = 'validado' WHERE estado IS NULL;
