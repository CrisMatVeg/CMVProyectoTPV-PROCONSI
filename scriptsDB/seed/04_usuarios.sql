-- ============================================================
-- SEED 04: USUARIOS (20 usuarios)
-- ============================================================
-- Contraseñas (SHA2-256, igual que usa el login):
--   todos → "paso"
--   SHA2('paso',256) = 4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944
-- ============================================================
USE `electrobazar`;

INSERT INTO usuarios (id, login, password, nombre, id_rol, activo) VALUES
-- ADMINISTRADORES (id_rol = 1)
(1,  'admin',     '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Carlos Martínez García',   1, 1),
(2,  'alopez',    '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Ana López Fernández',      1, 1),
(3,  'jgonzalez', '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Juan González Ruiz',       1, 1),

-- CAJEROS (id_rol = 2)
(4,  'mrodriguez','4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'María Rodríguez Sánchez',  2, 1),
(5,  'pherrero',  '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Pedro Herrero Jiménez',    2, 1),
(6,  'lgarcia',   '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Laura García Moreno',      2, 1),
(7,  'fdiaz',     '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Fernando Díaz López',      2, 1),
(8,  'cmoreno',   '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Cristina Moreno Torres',   2, 1),
(9,  'ajimenez',  '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Alberto Jiménez Castro',   2, 1),
(10, 'nserrano',  '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Nuria Serrano Blanco',     2, 1),
(11, 'dromero',   '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'David Romero Iglesias',    2, 1),
(12, 'sfuentes',  '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Sara Fuentes Navarro',     2, 1),
(13, 'iortega',   '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Ignacio Ortega Medina',    2, 1),
(14, 'mcastillo', '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Mónica Castillo Ramos',    2, 1),
(15, 'rsoto',     '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Roberto Soto Herrera',     2, 1),
(16, 'agomez',    '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Adriana Gómez Peña',       2, 1),
(17, 'jnavarro',  '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Javier Navarro León',      2, 1),
(18, 'eflores',   '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Elena Flores Rubio',       2, 1),
(19, 'rtorres',   '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Raúl Torres Molina',       2, 1),
(20, 'bvega',     '4dd09b8f659e27847f94782920fb7e41b2c5afbd7f419a4a3ed8ab7aa5b7f944', 'Beatriz Vega Santos',      2, 1);
