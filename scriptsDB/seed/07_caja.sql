-- ============================================================
-- SEED 07: CIERRES FISCALES + CAJA TURNOS + MOVIMIENTOS
-- ============================================================
-- Cobertura de fechas (referencia para gen_ventas.php):
--   Cierre  1: 2024-01-01 → 2024-02-29  (Turnos  1- 3)
--   Cierre  2: 2024-03-01 → 2024-04-30  (Turnos  4- 6)
--   Cierre  3: 2024-05-01 → 2024-06-30  (Turnos  7- 9)
--   Cierre  4: 2024-07-01 → 2024-08-31  (Turnos 10-12)
--   Cierre  5: 2024-09-01 → 2024-10-31  (Turnos 13-15)
--   Cierre  6: 2024-11-01 → 2024-12-31  (Turnos 16-18)
--   Cierre  7: 2025-01-01 → 2025-02-28  (Turnos 19-21)
--   Cierre  8: 2025-03-01 → 2025-04-30  (Turnos 22-24)
--   Cierre  9: 2025-05-01 → 2025-07-31  (Turnos 25-27)
--   Cierre 10: 2025-08-01 → 2025-12-31  (Turnos 28-30)
-- ============================================================
USE `electrobazar`;

-- ============================================================
-- 1. CIERRES FISCALES (10)
-- ============================================================
-- Totales aproximados: 1M ventas / 730 días / avg 40 EUR
-- 70% efectivo · 20% tarjeta · 10% bizum
INSERT INTO cierres_fiscales
  (id, fecha, id_usuario, total_efectivo, total_tarjeta, total_bizum, total_general)
VALUES
( 1, '2024-02-29 23:59:00', 1, 2310400.00,  660000.00,  330000.00,  3300400.00),
( 2, '2024-04-30 23:59:00', 2, 2348960.00,  670960.00,  335480.00,  3355400.00),
( 3, '2024-06-30 23:59:00', 3, 2291200.00,  654700.00,  327350.00,  3273250.00),
( 4, '2024-08-31 23:59:00', 1, 2389120.00,  682620.00,  341310.00,  3413050.00),
( 5, '2024-10-31 23:59:00', 2, 2348960.00,  670960.00,  335480.00,  3355400.00),
( 6, '2024-12-31 23:59:00', 3, 2387300.00,  681800.00,  340900.00,  3410000.00),
( 7, '2025-02-28 23:59:00', 1, 2271920.00,  648900.00,  324450.00,  3245270.00),
( 8, '2025-04-30 23:59:00', 2, 2348960.00,  670960.00,  335480.00,  3355400.00),
( 9, '2025-07-31 23:59:00', 3, 3541120.00, 1011760.00,  505880.00,  5058760.00),
(10, '2025-12-31 23:59:00', 1, 5879160.00, 1679760.00,  839880.00,  8398800.00);

-- ============================================================
-- 2. CAJA TURNOS (30 — 3 por cierre)
-- ============================================================
-- Cada turno cubre ~20 días (o más en C9/C10).
-- fondo_inicial/siguiente = 300 EUR.
-- total_retirado = suma de sus caja_movimientos (ver sección 3).
-- efectivo_real  ≈ discrepancia mínima respecto al fondo.
INSERT INTO caja_turnos
  (id, fecha_apertura, id_usuario_apertura, fondo_inicial,
   fecha_cierre, id_usuario_cierre,
   efectivo_real, fondo_siguiente_turno,
   total_retirado, total_ingresado, estado, num_z)
VALUES
-- Cierre 1 (2024-01-01 → 2024-02-29)
( 1,'2024-01-01 08:00:00', 4, 300.00,'2024-01-20 23:30:00',1, 315.00,300.00, 766900.00,0.00,'cerrado', 1),
( 2,'2024-01-21 08:00:00', 5, 300.00,'2024-02-09 23:30:00',1, 320.00,300.00, 767200.00,0.00,'cerrado', 1),
( 3,'2024-02-10 08:00:00', 6, 300.00,'2024-02-29 23:30:00',1, 310.00,300.00, 767000.00,0.00,'cerrado', 1),

-- Cierre 2 (2024-03-01 → 2024-04-30)
( 4,'2024-03-01 08:00:00', 7, 300.00,'2024-03-20 23:30:00',1, 318.00,300.00, 766800.00,0.00,'cerrado', 2),
( 5,'2024-03-21 08:00:00', 8, 300.00,'2024-04-09 23:30:00',1, 322.00,300.00, 767100.00,0.00,'cerrado', 2),
( 6,'2024-04-10 08:00:00', 9, 300.00,'2024-04-30 23:30:00',1, 325.00,300.00, 805400.00,0.00,'cerrado', 2),

-- Cierre 3 (2024-05-01 → 2024-06-30)
( 7,'2024-05-01 08:00:00',10, 300.00,'2024-05-20 23:30:00',1, 316.00,300.00, 766900.00,0.00,'cerrado', 3),
( 8,'2024-05-21 08:00:00',11, 300.00,'2024-06-09 23:30:00',1, 321.00,300.00, 767100.00,0.00,'cerrado', 3),
( 9,'2024-06-10 08:00:00',12, 300.00,'2024-06-30 23:30:00',1, 313.00,300.00, 805300.00,0.00,'cerrado', 3),

-- Cierre 4 (2024-07-01 → 2024-08-31)
(10,'2024-07-01 08:00:00',13, 300.00,'2024-07-21 23:30:00',1, 328.00,300.00, 805500.00,0.00,'cerrado', 4),
(11,'2024-07-22 08:00:00',14, 300.00,'2024-08-11 23:30:00',1, 319.00,300.00, 805200.00,0.00,'cerrado', 4),
(12,'2024-08-12 08:00:00',15, 300.00,'2024-08-31 23:30:00',1, 317.00,300.00, 767100.00,0.00,'cerrado', 4),

-- Cierre 5 (2024-09-01 → 2024-10-31)
(13,'2024-09-01 08:00:00',16, 300.00,'2024-09-20 23:30:00',1, 312.00,300.00, 767000.00,0.00,'cerrado', 5),
(14,'2024-09-21 08:00:00',17, 300.00,'2024-10-10 23:30:00',1, 323.00,300.00, 767200.00,0.00,'cerrado', 5),
(15,'2024-10-11 08:00:00',18, 300.00,'2024-10-31 23:30:00',1, 326.00,300.00, 805400.00,0.00,'cerrado', 5),

-- Cierre 6 (2024-11-01 → 2024-12-31)
(16,'2024-11-01 08:00:00',19, 300.00,'2024-11-20 23:30:00',1, 314.00,300.00, 767100.00,0.00,'cerrado', 6),
(17,'2024-11-21 08:00:00',20, 300.00,'2024-12-10 23:30:00',1, 320.00,300.00, 766800.00,0.00,'cerrado', 6),
(18,'2024-12-11 08:00:00', 4, 300.00,'2024-12-31 23:30:00',1, 330.00,300.00, 805500.00,0.00,'cerrado', 6),

-- Cierre 7 (2025-01-01 → 2025-02-28)
(19,'2025-01-01 08:00:00', 5, 300.00,'2025-01-20 23:30:00',1, 316.00,300.00, 766900.00,0.00,'cerrado', 7),
(20,'2025-01-21 08:00:00', 6, 300.00,'2025-02-09 23:30:00',1, 324.00,300.00, 767200.00,0.00,'cerrado', 7),
(21,'2025-02-10 08:00:00', 7, 300.00,'2025-02-28 23:30:00',1, 311.00,300.00, 728800.00,0.00,'cerrado', 7),

-- Cierre 8 (2025-03-01 → 2025-04-30)
(22,'2025-03-01 08:00:00', 8, 300.00,'2025-03-20 23:30:00',1, 319.00,300.00, 766900.00,0.00,'cerrado', 8),
(23,'2025-03-21 08:00:00', 9, 300.00,'2025-04-09 23:30:00',1, 322.00,300.00, 767100.00,0.00,'cerrado', 8),
(24,'2025-04-10 08:00:00',10, 300.00,'2025-04-30 23:30:00',1, 327.00,300.00, 805300.00,0.00,'cerrado', 8),

-- Cierre 9 (2025-05-01 → 2025-07-31) — turnos de 31 días
(25,'2025-05-01 08:00:00',11, 300.00,'2025-05-31 23:30:00',1, 320.00,300.00,1189000.00,0.00,'cerrado', 9),
(26,'2025-06-01 08:00:00',12, 300.00,'2025-06-30 23:30:00',1, 315.00,300.00,1150500.00,0.00,'cerrado', 9),
(27,'2025-07-01 08:00:00',13, 300.00,'2025-07-31 23:30:00',1, 323.00,300.00,1189200.00,0.00,'cerrado', 9),

-- Cierre 10 (2025-08-01 → 2025-12-31) — turnos de ~51 días
(28,'2025-08-01 08:00:00',14, 300.00,'2025-09-20 23:30:00',1, 318.00,300.00,1956200.00,0.00,'cerrado',10),
(29,'2025-09-21 08:00:00',15, 300.00,'2025-11-09 23:30:00',1, 325.00,300.00,1917700.00,0.00,'cerrado',10),
(30,'2025-11-10 08:00:00',16, 300.00,'2025-12-31 23:30:00',1, 335.00,300.00,1994620.00,0.00,'cerrado',10);

-- ============================================================
-- 3. CAJA MOVIMIENTOS (60 — 2 retiros por turno)
-- ============================================================
-- Retiro 1 (medio período): ~40% del total_retirado del turno
-- Retiro 2 (final período): ~60% del total_retirado del turno
-- La suma de ambos = total_retirado del turno correspondiente.
INSERT INTO caja_movimientos
  (id_turno, id_usuario, tipo, importe, concepto, created_at)
VALUES
-- Turno 1 (total_retirado=766900)
( 1, 4,'retiro',306000.00,'Recogida parcial — transporte de valores','2024-01-10 14:00:00'),
( 1, 4,'retiro',460900.00,'Recogida final del período — cierre parcial','2024-01-19 22:00:00'),
-- Turno 2 (total_retirado=767200)
( 2, 5,'retiro',307000.00,'Recogida parcial — transporte de valores','2024-01-31 14:00:00'),
( 2, 5,'retiro',460200.00,'Recogida final del período — cierre parcial','2024-02-08 22:00:00'),
-- Turno 3 (total_retirado=767000)
( 3, 6,'retiro',306500.00,'Recogida parcial — transporte de valores','2024-02-19 14:00:00'),
( 3, 6,'retiro',460500.00,'Recogida final del período — cierre parcial','2024-02-28 22:00:00'),
-- Turno 4 (total_retirado=766800)
( 4, 7,'retiro',305900.00,'Recogida parcial — transporte de valores','2024-03-10 14:00:00'),
( 4, 7,'retiro',460900.00,'Recogida final del período — cierre parcial','2024-03-19 22:00:00'),
-- Turno 5 (total_retirado=767100)
( 5, 8,'retiro',306200.00,'Recogida parcial — transporte de valores','2024-03-31 14:00:00'),
( 5, 8,'retiro',460900.00,'Recogida final del período — cierre parcial','2024-04-08 22:00:00'),
-- Turno 6 (total_retirado=805400)
( 6, 9,'retiro',321700.00,'Recogida parcial — transporte de valores','2024-04-20 14:00:00'),
( 6, 9,'retiro',483700.00,'Recogida final del período — cierre parcial','2024-04-29 22:00:00'),
-- Turno 7 (total_retirado=766900)
( 7,10,'retiro',306000.00,'Recogida parcial — transporte de valores','2024-05-10 14:00:00'),
( 7,10,'retiro',460900.00,'Recogida final del período — cierre parcial','2024-05-19 22:00:00'),
-- Turno 8 (total_retirado=767100)
( 8,11,'retiro',306100.00,'Recogida parcial — transporte de valores','2024-05-31 14:00:00'),
( 8,11,'retiro',461000.00,'Recogida final del período — cierre parcial','2024-06-08 22:00:00'),
-- Turno 9 (total_retirado=805300)
( 9,12,'retiro',321600.00,'Recogida parcial — transporte de valores','2024-06-20 14:00:00'),
( 9,12,'retiro',483700.00,'Recogida final del período — cierre parcial','2024-06-29 22:00:00'),
-- Turno 10 (total_retirado=805500)
(10,13,'retiro',321800.00,'Recogida parcial — transporte de valores','2024-07-11 14:00:00'),
(10,13,'retiro',483700.00,'Recogida final del período — cierre parcial','2024-07-20 22:00:00'),
-- Turno 11 (total_retirado=805200)
(11,14,'retiro',321500.00,'Recogida parcial — transporte de valores','2024-08-01 14:00:00'),
(11,14,'retiro',483700.00,'Recogida final del período — cierre parcial','2024-08-10 22:00:00'),
-- Turno 12 (total_retirado=767100)
(12,15,'retiro',306200.00,'Recogida parcial — transporte de valores','2024-08-21 14:00:00'),
(12,15,'retiro',460900.00,'Recogida final del período — cierre parcial','2024-08-30 22:00:00'),
-- Turno 13 (total_retirado=767000)
(13,16,'retiro',306000.00,'Recogida parcial — transporte de valores','2024-09-10 14:00:00'),
(13,16,'retiro',461000.00,'Recogida final del período — cierre parcial','2024-09-19 22:00:00'),
-- Turno 14 (total_retirado=767200)
(14,17,'retiro',306300.00,'Recogida parcial — transporte de valores','2024-10-01 14:00:00'),
(14,17,'retiro',460900.00,'Recogida final del período — cierre parcial','2024-10-09 22:00:00'),
-- Turno 15 (total_retirado=805400)
(15,18,'retiro',321700.00,'Recogida parcial — transporte de valores','2024-10-21 14:00:00'),
(15,18,'retiro',483700.00,'Recogida final del período — cierre parcial','2024-10-30 22:00:00'),
-- Turno 16 (total_retirado=767100)
(16,19,'retiro',306200.00,'Recogida parcial — transporte de valores','2024-11-10 14:00:00'),
(16,19,'retiro',460900.00,'Recogida final del período — cierre parcial','2024-11-19 22:00:00'),
-- Turno 17 (total_retirado=766800)
(17,20,'retiro',305900.00,'Recogida parcial — transporte de valores','2024-12-01 14:00:00'),
(17,20,'retiro',460900.00,'Recogida final del período — cierre parcial','2024-12-09 22:00:00'),
-- Turno 18 (total_retirado=805500)
(18, 4,'retiro',321800.00,'Recogida parcial — transporte de valores','2024-12-21 14:00:00'),
(18, 4,'retiro',483700.00,'Recogida final del período — cierre parcial','2024-12-30 22:00:00'),
-- Turno 19 (total_retirado=766900)
(19, 5,'retiro',306000.00,'Recogida parcial — transporte de valores','2025-01-10 14:00:00'),
(19, 5,'retiro',460900.00,'Recogida final del período — cierre parcial','2025-01-19 22:00:00'),
-- Turno 20 (total_retirado=767200)
(20, 6,'retiro',306300.00,'Recogida parcial — transporte de valores','2025-01-31 14:00:00'),
(20, 6,'retiro',460900.00,'Recogida final del período — cierre parcial','2025-02-08 22:00:00'),
-- Turno 21 (total_retirado=728800)
(21, 7,'retiro',290000.00,'Recogida parcial — transporte de valores','2025-02-19 14:00:00'),
(21, 7,'retiro',438800.00,'Recogida final del período — cierre parcial','2025-02-27 22:00:00'),
-- Turno 22 (total_retirado=766900)
(22, 8,'retiro',306000.00,'Recogida parcial — transporte de valores','2025-03-10 14:00:00'),
(22, 8,'retiro',460900.00,'Recogida final del período — cierre parcial','2025-03-19 22:00:00'),
-- Turno 23 (total_retirado=767100)
(23, 9,'retiro',306100.00,'Recogida parcial — transporte de valores','2025-03-31 14:00:00'),
(23, 9,'retiro',461000.00,'Recogida final del período — cierre parcial','2025-04-08 22:00:00'),
-- Turno 24 (total_retirado=805300)
(24,10,'retiro',321600.00,'Recogida parcial — transporte de valores','2025-04-20 14:00:00'),
(24,10,'retiro',483700.00,'Recogida final del período — cierre parcial','2025-04-29 22:00:00'),
-- Turno 25 (total_retirado=1189000)
(25,11,'retiro',475000.00,'Recogida parcial — transporte de valores','2025-05-16 14:00:00'),
(25,11,'retiro',714000.00,'Recogida final del período — cierre parcial','2025-05-30 22:00:00'),
-- Turno 26 (total_retirado=1150500)
(26,12,'retiro',460000.00,'Recogida parcial — transporte de valores','2025-06-15 14:00:00'),
(26,12,'retiro',690500.00,'Recogida final del período — cierre parcial','2025-06-29 22:00:00'),
-- Turno 27 (total_retirado=1189200)
(27,13,'retiro',475200.00,'Recogida parcial — transporte de valores','2025-07-16 14:00:00'),
(27,13,'retiro',714000.00,'Recogida final del período — cierre parcial','2025-07-30 22:00:00'),
-- Turno 28 (total_retirado=1956200)
(28,14,'retiro',782000.00,'Recogida parcial — transporte de valores','2025-08-26 14:00:00'),
(28,14,'retiro',1174200.00,'Recogida final del período — cierre parcial','2025-09-19 22:00:00'),
-- Turno 29 (total_retirado=1917700)
(29,15,'retiro',767000.00,'Recogida parcial — transporte de valores','2025-10-15 14:00:00'),
(29,15,'retiro',1150700.00,'Recogida final del período — cierre parcial','2025-11-08 22:00:00'),
-- Turno 30 (total_retirado=1994620)
(30,16,'retiro',797620.00,'Recogida parcial — transporte de valores','2025-12-05 14:00:00'),
(30,16,'retiro',1197000.00,'Recogida final del período — cierre parcial','2025-12-30 22:00:00');
