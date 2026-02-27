-- Script para modificar la tabla de productos y permitir el guardado de imágenes
ALTER TABLE productos MODIFY COLUMN icono LONGBLOB;

-- NOTA: Esto permitirá guardar los datos binarios de las imágenes directamente en la base de datos.
-- Los datos actuales (emojis) se mantendrán como texto binario, por lo que es recomendable
-- actualizar los productos con imágenes reales tras ejecutar este script.
