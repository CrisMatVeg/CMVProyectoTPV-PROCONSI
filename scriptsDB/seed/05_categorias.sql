-- ============================================================
-- SEED 05: CATEGORÍAS DE PRODUCTO
-- ============================================================
-- Requiere: tipos_iva ya insertados (id=1 = GENERAL 21%)
-- ============================================================
USE `electrobazar`;

INSERT INTO categorias (id, codigo, nombre, id_tipo_iva) VALUES
(1,  'smartphones', 'Smartphones',                1),
(2,  'tablets',     'Tablets',                    1),
(3,  'portatiles',  'Portátiles y Ordenadores',   1),
(4,  'televisores', 'Televisores y Pantallas',     1),
(5,  'audio',       'Audio y Sonido',              1),
(6,  'gaming',      'Gaming y Videojuegos',        1),
(7,  'accesorios',  'Accesorios y Periféricos',   1),
(8,  'wearables',   'Wearables y Smartwatch',      1),
(9,  'smarthome',   'Smart Home y Domótica',       1),
(10, 'camaras',     'Cámaras y Fotografía',        1);
