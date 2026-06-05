-- ============================================================
-- SEED 02: TIPOS DE IVA VIGENTES EN ESPAÑA
-- ============================================================
USE `electrobazar`;

-- Los 4 tipos de IVA legales en España según la Ley 37/1992 del IVA.
-- Recargo de equivalencia aplicable a comercios minoristas (régimen especial).
INSERT INTO tipos_iva (id, codigo, nombre, porcentaje, recargo_equivalencia, fecha_inicio, fecha_fin, activo) VALUES
(1, 'GENERAL',      'IVA general',         21.00, 5.20, '2012-09-01', NULL, 1),
(2, 'REDUCIDO',     'IVA reducido',        10.00, 1.40, '2012-09-01', NULL, 1),
(3, 'SUPERREDUCIDO','IVA superreducido',    4.00, 0.50, '1995-01-01', NULL, 1),
(4, 'EXENTO',       'Operación exenta',     0.00, 0.00, '1992-01-01', NULL, 1);
