-- ============================================================
-- SEED 06: TARIFAS DE PRECIO + PROMOCIONES
-- ============================================================
-- Requiere: usuarios (id=1), productos y categorias cargados
-- ============================================================
USE `electrobazar`;

-- TARIFAS DE PRECIO (5)
-- campo tipo_cliente: 'todos'|'particular'|'empresa'|'socio'|'mayorista'
-- campo scope:        'todos'|'categoria'|'productos'
INSERT INTO tarifas_precios
  (id, nombre, tipo, valor, fecha_aplicacion, fecha_fin,
   activo, aplicada, scope, categoria, tipo_cliente,
   es_solo_socios, prioridad, creado_por, created_at)
VALUES
(1, 'Tarifa VIP Socios',   'percent', 15.00, '2024-01-01', NULL,
   1, 0, 'todos',     NULL,           'todos',     1, 10, 1, '2024-01-01 09:00:00'),
(2, 'Tarifa Empresa',      'percent', 10.00, '2024-01-01', NULL,
   1, 0, 'todos',     NULL,           'empresa',   0,  8, 1, '2024-01-01 09:00:00'),
(3, 'Tarifa Verano 2025',  'percent',  5.00, '2025-06-01', '2025-08-31',
   1, 0, 'categoria', 'audio,accesorios', 'todos', 0,  5, 1, '2025-05-15 10:00:00'),
(4, 'Tarifa Mayorista',    'percent', 20.00, '2024-01-01', NULL,
   1, 0, 'todos',     NULL,           'mayorista', 0, 15, 1, '2024-01-01 09:00:00'),
(5, 'Tarifa Navidad 2024', 'percent',  8.00, '2024-12-01', '2025-01-06',
   0, 0, 'todos',     NULL,           'todos',     0,  6, 1, '2024-11-20 11:00:00');

-- PROMOCIONES (5)
-- tipo 'bundle': bundle_buy_qty unidades compradas, bundle_pay_qty pagadas
-- tipo 'amount': descuento fijo en euros
-- tipo 'percent': descuento porcentual
INSERT INTO promociones
  (id, codigo, descripcion, tipo, valor, min_subtotal,
   categoria_code, bundle_buy_qty, bundle_pay_qty,
   prioridad, activo, solo_socios, dias_semana,
   fecha_inicio, fecha_fin)
VALUES
(1, '3X2ACC',     '3x2 en Accesorios y Periféricos',
   'bundle',  0.00,  0.00, 'accesorios', 3, 2,
   10, 1, 0, NULL,  '2024-01-01', NULL),
(2, 'BIENVENIDA', 'Descuento Bienvenida 10 EUR (min. 50 EUR)',
   'amount',  10.00, 50.00, NULL,        0, 0,
    5, 1, 0, NULL,  '2024-01-01', NULL),
(3, 'LUNESIVA',   'Lunes sin IVA (-17,36%)',
   'percent', 17.36, 0.00,  NULL,        0, 0,
    8, 1, 0, '1',   '2024-01-01', NULL),
(4, 'PACKFAM',    'Pack Familiar Tablets -15%',
   'percent', 15.00, 0.00,  'tablets',   0, 0,
    7, 1, 0, NULL,  '2024-01-01', NULL),
(5, 'FLASHGAMING','Flash Gaming Fines de Semana -25%',
   'percent', 25.00, 0.00,  'gaming',    0, 0,
    9, 1, 0, '6,7', '2024-01-01', NULL);
