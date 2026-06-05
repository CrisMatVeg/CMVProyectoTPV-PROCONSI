<?php
// ============================================================
// gen_productos.php - 20.000 productos con LOAD DATA LOCAL INFILE
// Ejecutar: php scriptsDB/seed/gen_productos.php
// ============================================================
set_time_limit(0);
ini_set('memory_limit', '256M');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../../config/confDBPDO.php';

$tmpDir  = __DIR__ . '/tmp';
$csvFile = $tmpDir . '/productos.csv';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

// ============================================================
// DEFINICIÓN DE CATEGORÍAS
// Cada categoría: count, prefix, rango coste/margen, garantía,
// lista de bases (marca+modelo) y lista de specs (variante).
// nombre final = base + ' ' + spec  → máx 100 chars
// ============================================================
$cats = [

    // ---- SMARTPHONES (2000) ----
    'smartphones' => [
        'count'   => 2000,
        'prefix'  => 'SMRT',
        'coste'   => [180, 1100],
        'margen'  => [20, 38],
        'garantia'=> 24,
        'bases'   => [
            'Samsung Galaxy S24 Ultra','Samsung Galaxy S24+','Samsung Galaxy S24',
            'Samsung Galaxy S23 FE','Samsung Galaxy A54','Samsung Galaxy A34',
            'Samsung Galaxy A25','Samsung Galaxy A15',
            'Apple iPhone 15 Pro Max','Apple iPhone 15 Pro','Apple iPhone 15',
            'Apple iPhone 14 Pro','Apple iPhone 14','Apple iPhone 13',
            'Xiaomi 14 Ultra','Xiaomi 14','Xiaomi 13T Pro',
            'Xiaomi Redmi Note 13 Pro+','Xiaomi Redmi Note 13','Xiaomi POCO X6 Pro',
            'Huawei Pura 70 Ultra','Huawei Pura 70 Pro','Huawei Pura 70',
            'Huawei Nova 12 Pro','Huawei Nova 12','Huawei P60 Pro',
            'Google Pixel 8 Pro','Google Pixel 8','Google Pixel 7a','Google Pixel 7 Pro',
            'Motorola Edge 50 Ultra','Motorola Edge 50 Pro','Motorola Edge 50',
            'Motorola Moto G84','Motorola Moto G54',
            'OnePlus 12','OnePlus 11','OnePlus Nord 3','OnePlus Nord CE3',
            'Sony Xperia 1 VI','Sony Xperia 5 V','Sony Xperia 10 VI',
        ],
        'specs'   => [
            '128GB Negro','128GB Blanco','128GB Azul Medianoche','128GB Verde Menta',
            '128GB Morado Lavanda','128GB Rosa Palido','128GB Grafito',
            '256GB Negro','256GB Blanco','256GB Azul Cielo','256GB Verde Bosque',
            '256GB Grafito','256GB Titanio Natural','256GB Cobre','256GB Arena',
            '512GB Negro','512GB Blanco Nieve','512GB Titanio Negro',
            '512GB Azul Horizonte','512GB Verde Oliva',
            '1TB Negro','1TB Titanio Blanco','1TB Grafito Espacial',
            '64GB Negro','64GB Blanco','64GB Azul',
            '128GB Dorado','128GB Plata Artica','256GB Dorado','256GB Morado',
            '128GB Rojo','256GB Rojo','512GB Verde','512GB Azul Medianoche',
            '256GB Lila','128GB Azul Real','256GB Negro Mate','128GB Carbono',
            '256GB Bronce','512GB Cobre Mate','128GB Indigo',
            '256GB Titanio Azul','256GB Titanio Verde','256GB Titanio Negro',
            '128GB Lavanda','512GB Grafito Medio',
            '128GB Coral','256GB Ocre','128GB Verde Salvia','256GB Azul Artico',
        ],
        'desc'    => 'Smartphone con pantalla AMOLED, procesador de alto rendimiento y sistema de camaras avanzado. Conectividad 5G, bateria de larga duracion y diseno premium.',
    ],

    // ---- TABLETS (1500) ----
    'tablets' => [
        'count'   => 1500,
        'prefix'  => 'TABL',
        'coste'   => [150, 1200],
        'margen'  => [22, 40],
        'garantia'=> 24,
        'bases'   => [
            'Samsung Galaxy Tab S9 Ultra','Samsung Galaxy Tab S9+','Samsung Galaxy Tab S9',
            'Samsung Galaxy Tab S9 FE','Samsung Galaxy Tab A9+','Samsung Galaxy Tab A9',
            'Apple iPad Pro 12.9 M4','Apple iPad Pro 11 M4','Apple iPad Air 13 M2',
            'Apple iPad Air 11 M2','Apple iPad mini 7','Apple iPad 10.9',
            'Xiaomi Pad 6 Pro','Xiaomi Pad 6','Xiaomi Redmi Pad SE',
            'Xiaomi Redmi Pad Pro','Xiaomi Redmi Pad',
            'Lenovo Tab P12 Pro','Lenovo Tab P12','Lenovo Tab P11 Pro',
            'Lenovo Tab P11','Lenovo Tab M11','Lenovo Tab M10 Plus',
            'Huawei MatePad Pro 13.2','Huawei MatePad 11.5','Huawei MatePad SE 11',
            'Amazon Fire HD 10','Amazon Fire HD 8',
            'Microsoft Surface Pro 9','Microsoft Surface Go 3',
        ],
        'specs'   => [
            'WiFi 128GB Gris','WiFi 256GB Gris','WiFi 512GB Grafito',
            'WiFi 64GB Plata','WiFi 128GB Plata','WiFi 256GB Plata',
            'WiFi 64GB Negro','WiFi 128GB Negro','WiFi 256GB Negro',
            '5G 128GB Gris','5G 256GB Gris','5G 512GB Grafito',
            '5G 128GB Plata','5G 256GB Plata',
            'WiFi 128GB Azul','WiFi 256GB Azul','WiFi 64GB Verde',
            'WiFi 128GB Verde','WiFi 256GB Beige','WiFi 128GB Beige',
            '4G 64GB Gris','4G 128GB Gris','4G 64GB Negro','4G 128GB Negro',
            'WiFi 8GB 256GB Plata','WiFi 8GB 512GB Plata',
            'WiFi 12GB 256GB Gris','WiFi 12GB 512GB Gris',
            'WiFi 16GB 512GB Negro','WiFi 16GB 1TB Negro',
            'WiFi 128GB Azul Marino','WiFi 256GB Azul Marino',
            'WiFi 128GB Gris Espacial','WiFi 256GB Gris Espacial',
            'WiFi 128GB Violeta','WiFi 256GB Violeta',
            'WiFi 64GB Coral','WiFi 128GB Coral',
            '5G 64GB Negro','5G 64GB Plata','5G 256GB Negro','5G 256GB Azul',
            'WiFi 32GB Negro','WiFi 32GB Azul','WiFi 32GB Plata',
            'WiFi 256GB Grafito Espacial','WiFi 128GB Titanio',
            'WiFi 256GB Titanio','5G 128GB Titanio','5G 256GB Titanio',
            'WiFi 128GB Amarillo',
        ],
        'desc'    => 'Tablet con pantalla de alta resolucion, procesador eficiente y bateria de larga autonomia. Ideal para trabajo, estudio y entretenimiento.',
    ],

    // ---- PORTATILES (2500) ----
    'portatiles' => [
        'count'   => 2500,
        'prefix'  => 'PORT',
        'coste'   => [400, 2200],
        'margen'  => [15, 28],
        'garantia'=> 24,
        'bases'   => [
            'HP Pavilion 15','HP Pavilion 14','HP Victus 16','HP Victus 15',
            'HP EliteBook 840 G10','HP EliteBook 1040 G10',
            'HP Spectre x360 14','HP ENVY x360 15',
            'Dell XPS 15','Dell XPS 13','Dell Inspiron 15','Dell Inspiron 14',
            'Dell Latitude 5540','Dell Latitude 7440',
            'Lenovo ThinkPad X1 Carbon','Lenovo ThinkPad T14','Lenovo ThinkPad E16',
            'Lenovo IdeaPad 5 Pro','Lenovo IdeaPad 5','Lenovo IdeaPad 3',
            'Lenovo Legion Pro 7','Lenovo Legion 5 Pro',
            'ASUS VivoBook 15','ASUS VivoBook 14','ASUS ZenBook 14',
            'ASUS ZenBook Pro 16','ASUS ROG Zephyrus G14',
            'ASUS ROG Strix G16','ASUS TUF Gaming A15',
            'Apple MacBook Air 15 M3','Apple MacBook Air 13 M3',
            'Apple MacBook Pro 16 M3 Pro','Apple MacBook Pro 14 M3',
            'Acer Swift Go 14','Acer Swift Edge 16','Acer Aspire 5',
            'Acer Nitro 5','Acer Predator Helios 18',
            'MSI Modern 15','MSI Stealth 16','MSI Raider GE78 HX',
            'Microsoft Surface Laptop 5',
            'Huawei MateBook D 15','Huawei MateBook X Pro',
            'LG Gram 16','LG Gram 14',
            'Toshiba Dynabook Tecra A50',
        ],
        'specs'   => [
            'Intel Core i5 8GB 256GB SSD','Intel Core i5 8GB 512GB SSD',
            'Intel Core i5 16GB 512GB SSD','Intel Core i7 16GB 512GB SSD',
            'Intel Core i7 16GB 1TB SSD','Intel Core i7 32GB 1TB SSD',
            'Intel Core i9 32GB 1TB SSD','Intel Core i9 64GB 2TB SSD',
            'Core Ultra 5 16GB 512GB SSD','Core Ultra 7 16GB 1TB SSD',
            'Core Ultra 9 32GB 2TB SSD',
            'AMD Ryzen 5 8GB 256GB SSD','AMD Ryzen 5 8GB 512GB SSD',
            'AMD Ryzen 5 16GB 512GB SSD','AMD Ryzen 7 16GB 512GB SSD',
            'AMD Ryzen 7 16GB 1TB SSD','AMD Ryzen 7 32GB 1TB SSD',
            'AMD Ryzen 9 32GB 2TB SSD',
            'M1 8GB 256GB SSD','M1 8GB 512GB SSD',
            'M2 8GB 256GB SSD','M2 8GB 512GB SSD','M2 16GB 512GB SSD',
            'M3 8GB 256GB SSD','M3 8GB 512GB SSD','M3 16GB 512GB SSD',
            'M3 Pro 18GB 512GB SSD','M3 Pro 36GB 512GB SSD',
            'M3 Max 36GB 1TB SSD','M3 Max 64GB 2TB SSD',
            'RTX 4060 16GB 512GB SSD','RTX 4060 16GB 1TB SSD',
            'RTX 4070 16GB 1TB SSD','RTX 4070 32GB 1TB SSD',
            'RTX 4090 32GB 2TB SSD',
            'Negro i5 8GB 512GB SSD','Plata i7 16GB 512GB SSD',
            'Gris i7 16GB 1TB SSD','Negro Ryzen 5 8GB 512GB SSD',
            'Plata Ryzen 7 16GB 1TB SSD','Negro i5 8GB 256GB SSD',
            'Plata i5 16GB 512GB SSD','Gris Espacial 16GB 512GB SSD',
            'Negro 32GB 1TB SSD','Blanco 8GB 256GB SSD',
            '14 pulgadas i5 8GB 512GB','15.6 pulgadas i7 16GB 1TB',
            '16 pulgadas i7 32GB 1TB','13.3 pulgadas i5 8GB 256GB',
        ],
        'desc'    => 'Portatil de alto rendimiento con pantalla de alta resolucion, procesador de ultima generacion y almacenamiento SSD rapido. Diseno ligero y ergonomico.',
    ],

    // ---- TELEVISORES (2000) ----
    'televisores' => [
        'count'   => 2000,
        'prefix'  => 'TVSN',
        'coste'   => [200, 2500],
        'margen'  => [18, 32],
        'garantia'=> 36,
        'bases'   => [
            'Samsung Neo QLED 8K QN900D','Samsung Neo QLED 4K QN95D',
            'Samsung QLED 4K Q80D','Samsung QLED 4K Q70D',
            'Samsung Crystal UHD 4K AU9','Samsung The Frame LS03',
            'Samsung Smart TV 4K BU8500',
            'LG OLED evo G4','LG OLED evo C4','LG OLED B4',
            'LG QNED MiniLED 99 4K','LG QNED MiniLED 90 4K',
            'LG NanoCell 90 4K','LG UHD 80 4K','LG Full HD 70LM6375',
            'Sony BRAVIA XR A95L OLED','Sony BRAVIA XR A80L OLED',
            'Sony BRAVIA XR X95L Mini LED','Sony BRAVIA XR X90L 4K',
            'Sony BRAVIA X85L 4K','Sony BRAVIA X80L 4K',
            'Philips Ambilight OLED908','Philips Ambilight OLED808',
            'Philips Ambilight 4K 8507','Philips Ambilight 4K 6807',
            'Hisense ULED 4K U9K MiniLED','Hisense ULED 4K U8K',
            'Hisense ULED 4K U7K','Hisense Smart TV 4K A7K',
            'TCL QLED 4K C805','TCL QLED 4K C735',
            'TCL LED 4K P745','TCL Smart TV S5400A',
            'Panasonic OLED TX-55MZ2000','Panasonic OLED TX-65MZ1500',
            'Philips 4K Ultra HD PUS8118','Philips 4K Ultra HD PUS7608',
        ],
        'specs'   => [
            '32 pulgadas','43 pulgadas','50 pulgadas','55 pulgadas',
            '65 pulgadas','75 pulgadas','85 pulgadas','98 pulgadas',
            '43 pulgadas Negro','50 pulgadas Negro','55 pulgadas Negro',
            '65 pulgadas Negro','75 pulgadas Negro','85 pulgadas Negro',
            '43 pulgadas Plata','55 pulgadas Plata','65 pulgadas Plata',
            '43 pulgadas 4K HDR10+','55 pulgadas 4K HDR10+',
            '65 pulgadas 4K HDR10+','75 pulgadas 4K HDR10+',
            '55 pulgadas 120Hz','65 pulgadas 120Hz','75 pulgadas 120Hz',
            '55 pulgadas 4K 144Hz','65 pulgadas 4K 144Hz',
            '43 pulgadas 4K Smart TV','55 pulgadas 4K Smart TV',
            '65 pulgadas 4K Smart TV','75 pulgadas 4K Smart TV',
            '65 pulgadas 8K','75 pulgadas 8K','85 pulgadas 8K',
            '55 pulgadas OLED 4K','65 pulgadas OLED 4K',
            '77 pulgadas OLED 4K','83 pulgadas OLED 4K',
            '43 pulgadas Titan Black','55 pulgadas Titan Black',
            '65 pulgadas Titan Black','75 pulgadas Titan Black',
            '43 pulgadas 4K UHD','55 pulgadas 4K UHD',
            '65 pulgadas 4K UHD','75 pulgadas 4K UHD',
            '55 pulgadas MiniLED 4K','65 pulgadas MiniLED 4K',
        ],
        'desc'    => 'Televisor inteligente con tecnologia de imagen avanzada, sistema Smart TV integrado y acceso a las principales plataformas de streaming. HDMI 2.1.',
    ],

    // ---- AUDIO (2000) ----
    'audio' => [
        'count'   => 2000,
        'prefix'  => 'AUDI',
        'coste'   => [15, 500],
        'margen'  => [30, 55],
        'garantia'=> 24,
        'bases'   => [
            'Sony WH-1000XM5','Sony WH-1000XM4','Sony WF-1000XM5','Sony WF-1000XM4',
            'Sony WH-CH720N','Sony LinkBuds S','Sony SRS-XB43','Sony SRS-XG300',
            'JBL Live Pro 2','JBL Tune 770NC','JBL Tune 510BT',
            'JBL Charge 5','JBL Flip 6','JBL Go 3','JBL Xtreme 3',
            'Bose QuietComfort Ultra Headphones','Bose QuietComfort 45',
            'Bose QuietComfort Earbuds II','Bose SoundLink Max',
            'Sennheiser Momentum 4 Wireless','Sennheiser HD 450BT',
            'Sennheiser ACCENTUM Plus','Sennheiser HD 560S',
            'Jabra Evolve2 75','Jabra Elite 85h','Jabra Elite 7 Active',
            'Audio-Technica ATH-M50x','Audio-Technica ATH-M40x',
            'Marshall Emberton III','Marshall Acton III','Marshall Stanmore III',
            'Harman Kardon Aura Studio 4','Harman Kardon SoundSticks 4',
            'Bang & Olufsen Beosound A1','Bang & Olufsen Beoplay H95',
            'Beats Studio Pro','Beats Studio Buds+','Beats Fit Pro',
            'Anker Soundcore Life Q45','Anker Soundcore A40',
        ],
        'specs'   => [
            'Negro','Blanco','Azul Medianoche','Plata','Rojo','Verde Oliva',
            'Negro Mate','Blanco Perla','Azul Marino','Gris Pizarra',
            'Negro con Funda','Blanco con Funda',
            'Negro ANC','Blanco ANC','Azul ANC','Plata ANC',
            'Negro Bluetooth 5.3','Plata Bluetooth 5.3',
            'Negro IPX5','Blanco IPX5','Azul IPX5','Rojo IPX5',
            'Negro IP67','Plata IP67',
            'Negro 360W','Blanco 360W',
            'Negro 40W','Blanco 40W','Rojo 40W',
            'Ebony Negro','Cream Blanco','Sage Verde',
            'Negro Carbono','Gris Ceniza','Beige Arena',
            'Blanco Alabastro','Negro Titanio',
            'Negro 24h bateria','Blanco 24h bateria',
            'Negro 48h bateria','Plata 48h bateria',
            'Negro Edicion Especial','Blanco Edicion Especial',
            'Azul Edicion Limitada','Rojo Edicion Limitada',
            'Midnight Black','Lunar Rock','Cloud Pink','Forest Green',
        ],
        'desc'    => 'Dispositivo de audio de alta fidelidad con cancelacion activa de ruido, conectividad Bluetooth multipoint y excelente calidad sonora. Bateria de larga autonomia.',
    ],

    // ---- GAMING (2000) ----
    'gaming' => [
        'count'   => 2000,
        'prefix'  => 'GAME',
        'coste'   => [40, 650],
        'margen'  => [20, 40],
        'garantia'=> 24,
        'bases'   => [
            'Sony PlayStation 5 Digital Edition','Sony PlayStation 5 Disc Edition',
            'Sony DualSense Wireless','Sony DualSense Edge',
            'Microsoft Xbox Series X','Microsoft Xbox Series S',
            'Microsoft Xbox Wireless Controller',
            'Nintendo Switch OLED','Nintendo Switch Lite','Nintendo Switch V2',
            'Nintendo Joy-Con',
            'Razer BlackShark V2 Pro','Razer BlackWidow V4 Pro',
            'Razer DeathAdder V3 Pro','Razer Basilisk V3 Pro',
            'Razer Kraken V3 Pro','Razer Nari Ultimate',
            'Logitech G502 X Plus','Logitech G915 TKL',
            'Logitech G Pro X Superlight 2','Logitech G635','Logitech G733',
            'SteelSeries Arctis Nova Pro','SteelSeries Arctis 7+',
            'SteelSeries Rival 5','SteelSeries Apex Pro',
            'Corsair K100 RGB','Corsair Sabre RGB Pro',
            'Corsair Void RGB Elite','Corsair HS80 RGB',
            'ASUS ROG Gladius III','ASUS ROG Delta S',
            'HyperX Cloud Alpha S','HyperX Pulsefire Haste 2',
            'Thrustmaster T300RS GT',
            'BenQ MOBIUZ EX270M','BenQ MOBIUZ EX2510',
            'AOC AGON AG274QZM','LG UltraGear 32GQ950',
            'Samsung Odyssey G9','Samsung Odyssey NEO G8',
        ],
        'specs'   => [
            'Negro','Blanco','Rojo','Azul','Verde','Gris',
            'Negro Edicion Estandar','Blanco Edicion Estandar',
            'Negro 1TB SSD','Blanco 1TB SSD','Negro 2TB SSD',
            'Negro Inalambrico','Blanco Inalambrico',
            'RGB Negro','RGB Blanco','RGB Rojo','RGB Azul',
            'Negro USB','Blanco USB','Negro Jack 3.5mm',
            'Negro PC','Blanco PC',
            'Negro 2.4GHz','Blanco 2.4GHz',
            'Negro Mecanico Red','Negro Mecanico Blue',
            'Rojo Deportivo','Azul Oscuro',
            'Carbon Black','Forge Gray','Phantom White',
            'Neon Purple','Arctic Blue','Midnight Blue',
            'Volcanic Red','Electric Blue','Candy Pink',
            'Matte Black','Matte White',
            '27 pulgadas 1080p 165Hz','27 pulgadas 1440p 165Hz',
            '32 pulgadas 4K 144Hz','24 pulgadas 1080p 240Hz',
            '27 pulgadas 1440p 240Hz',
            '34 pulgadas Ultrawide 144Hz',
            '49 pulgadas Ultrawide Curvo',
        ],
        'desc'    => 'Producto gaming de alto rendimiento para jugadores exigentes. Tecnologia de ultima generacion, baja latencia y materiales premium para la mejor experiencia de juego.',
    ],

    // ---- ACCESORIOS (3000) ----
    'accesorios' => [
        'count'   => 3000,
        'prefix'  => 'ACCS',
        'coste'   => [2, 80],
        'margen'  => [40, 80],
        'garantia'=> 12,
        'bases'   => [
            'Funda Silicona Samsung Galaxy','Funda Silicona iPhone',
            'Funda Silicona Xiaomi','Funda Transparente Samsung',
            'Funda Transparente iPhone','Funda Libro Samsung Galaxy',
            'Funda Libro iPhone','Funda Libro Xiaomi',
            'Protector Pantalla Samsung Galaxy','Protector Pantalla iPhone',
            'Protector Pantalla Cristal Templado','Protector Pantalla Privacidad',
            'Cargador USB-C 65W GaN','Cargador USB-C 45W GaN',
            'Cargador USB-C 20W','Cargador Inalambrico 15W',
            'Cargador Inalambrico Magsafe','Cargador de Coche USB-C 30W',
            'Cargador Multipuerto USB-C',
            'Cable USB-C a USB-C 1m','Cable USB-C a USB-C 2m',
            'Cable USB-C a Lightning 1m','Cable Micro USB 1m',
            'Cable HDMI 2.1 1m','Cable HDMI 2.1 2m',
            'Cable DisplayPort 1.4',
            'Power Bank 20000mAh USB-C','Power Bank 10000mAh USB-C',
            'Power Bank 5000mAh Compacto','Power Bank MagSafe 10000mAh',
            'Raton Inalambrico Logitech','Raton Inalambrico Microsoft',
            'Raton Ergonomico Vertical','Raton Bluetooth Compacto',
            'Teclado Inalambrico Logitech','Teclado Bluetooth Compacto',
            'Teclado Mecanico Compacto TKL',
            'Webcam Full HD 1080p','Webcam 4K 30fps','Webcam con Microfono',
            'Hub USB-C 7 en 1','Hub USB-C 4K 60Hz','Hub USB 3.0 4 puertos',
            'Soporte Portatil Ajustable','Soporte Movil para Escritorio',
            'Soporte Monitor Doble Brazo','Soporte Tablet Mesa',
            'Auriculares con Cable Jack 3.5mm',
            'Microfono USB Cardioide','Microfono Condensador USB',
            'Lector Tarjetas SD y MicroSD','Adaptador USB-C a HDMI 4K',
            'Adaptador USB-C a Jack 3.5mm','Adaptador Lightning a USB-C',
            'Almohadilla Carga Inalambrica 3 en 1',
        ],
        'specs'   => [
            'Negro','Blanco','Azul','Rojo','Verde','Gris','Transparente',
            'Rosa','Morado','Naranja','Amarillo',
            'Negro 1m','Negro 2m','Negro 3m','Blanco 1m','Blanco 2m',
            'Negro Trenzado','Blanco Trenzado',
            'Silicona Negro','Silicona Azul','Silicona Rosa',
            'TPU Transparente','TPU Antigolpes Negro',
            'Cristal 9H','Cristal 9H Anti-espias','Cristal 9H Mate',
            'Pack 2 unidades','Pack 3 unidades',
            'MagSafe Negro','MagSafe Blanco','MagSafe Azul',
            'GaN Negro','GaN Blanco','GaN 2 puertos',
            'Braided Negro','Braided Blanco',
            'Original Negro','Original Blanco',
            'Plata','Plata Mate',
            'Space Gray','Midnight Black',
            'Aluminio Negro','Aluminio Plata',
        ],
        'desc'    => 'Accesorio de calidad compatible con los principales dispositivos del mercado. Fabricado con materiales duraderos y sometido a controles de calidad estrictos.',
    ],

    // ---- WEARABLES (1500) ----
    'wearables' => [
        'count'   => 1500,
        'prefix'  => 'WEAR',
        'coste'   => [30, 550],
        'margen'  => [25, 45],
        'garantia'=> 24,
        'bases'   => [
            'Apple Watch Series 9','Apple Watch Ultra 2','Apple Watch SE 2',
            'Samsung Galaxy Watch 6 Classic','Samsung Galaxy Watch 6',
            'Samsung Galaxy Watch 5 Pro','Samsung Galaxy Watch 5',
            'Samsung Galaxy Fit 3',
            'Garmin Fenix 7X Pro Solar','Garmin Fenix 7S Pro Solar',
            'Garmin Forerunner 965','Garmin Forerunner 265',
            'Garmin Venu 3','Garmin Vivosmart 5',
            'Xiaomi Watch S3','Xiaomi Watch 2 Pro',
            'Xiaomi Smart Band 9','Xiaomi Smart Band 8 Pro',
            'Xiaomi Redmi Watch 4',
            'Huawei Watch GT 4','Huawei Watch GT 3 Pro',
            'Huawei Watch Ultimate','Huawei Band 9',
            'Fitbit Sense 2','Fitbit Versa 4','Fitbit Inspire 3',
            'Apple AirPods Pro 2','Apple AirPods 4','Apple AirPods Max',
            'Samsung Galaxy Buds 3 Pro','Samsung Galaxy Buds 3',
        ],
        'specs'   => [
            'Negro','Blanco','Plata','Dorado','Grafito','Rojo','Azul','Verde',
            'Negro 44mm','Blanco 44mm','Plata 44mm',
            'Negro 40mm','Plata 40mm','Grafito 40mm',
            'Negro 45mm','Plata 45mm','Grafito 45mm','Dorado 45mm',
            'Negro Titanio','Plata Titanio','Dorado Titanio',
            'Negro Acero','Plata Acero','Dorado Acero',
            'Negro GPS','Plata GPS',
            'Negro GPS Cellular','Plata GPS Cellular',
            'Midnight Black','Ivory White','Green','Storm Blue',
            'Carbon Black','Sapphire Blue','Titanium Silver',
            'Negro Deportivo','Blanco Deportivo','Azul Deportivo',
            'Negro con Malla Milanese','Plata con Malla Milanese',
            'Negro ANC','Blanco ANC','Lavander ANC','Azul ANC',
            'Plata In-Ear','Negro In-Ear','Blanco In-Ear',
            'Azul Zafiro','Verde Oliva','Rojo Deportivo',
        ],
        'desc'    => 'Wearable inteligente con monitorizacion de salud y deporte, frecuencia cardiaca, SpO2 y GPS integrado. Pantalla AMOLED y bateria de larga duracion.',
    ],

    // ---- SMART HOME (1500) ----
    'smarthome' => [
        'count'   => 1500,
        'prefix'  => 'SMHM',
        'coste'   => [10, 350],
        'margen'  => [35, 65],
        'garantia'=> 24,
        'bases'   => [
            'Philips Hue White Ambiance E27','Philips Hue White and Color Ambiance E27',
            'Philips Hue White E27','Philips Hue Play Gradient Lightstrip',
            'TP-Link Tapo C310 Camara','TP-Link Tapo C320WS Camara',
            'TP-Link Tapo L630 Tira LED','TP-Link Kasa Enchufe Inteligente',
            'TP-Link Deco XE75 Mesh WiFi 6',
            'Xiaomi Robot Aspirador S10+','Xiaomi Robot Aspirador S20+',
            'Xiaomi Mi Robot Vacuum S12','Xiaomi Mi Smart Speaker',
            'Xiaomi Mi Smart Plug WiFi',
            'Roborock S8 Pro Ultra','Roborock S7 MaxV Ultra',
            'iRobot Roomba j9+','iRobot Roomba i5',
            'Amazon Echo Dot 5 Gen','Amazon Echo 4 Gen',
            'Amazon Echo Show 8 HD','Amazon Echo Show 10',
            'Amazon Echo Show 15 Full HD',
            'Google Nest Hub Max','Google Nest Hub 2 Gen',
            'Google Nest Mini 2 Gen','Google Nest Doorbell',
            'Google Nest Cam Exterior',
            'Ring Video Doorbell Pro 2','Ring Indoor Cam',
            'Aqara Hub M2','Aqara Motion Sensor P1',
            'Tado Smart Thermostat V3+','Netatmo Termostato Inteligente',
        ],
        'specs'   => [
            'Blanco','Negro','Gris','Gris Carbon',
            'E27 Blanco','E27 Multicolor','GU10 Blanco','GU10 Multicolor',
            'Pack 2 unidades','Pack 3 unidades','Pack 4 unidades',
            'Pack Starter Kit','Kit Basico','Kit Premium',
            '1080p Blanco','1080p Negro','2K Blanco','2K Negro',
            '4K Blanco','4K Negro',
            'WiFi 2.4GHz','WiFi 6 Dual Band','Thread y Matter',
            'Zigbee 3.0',
            'con Base de Carga','sin Base de Carga',
            'con Aspiracion y Fregado','solo Aspiracion',
            'Alexa Compatible','Google Compatible','HomeKit Compatible',
            'Interior','Exterior','Interior y Exterior',
            'LED E14 Blanco','LED E14 Multicolor',
            '1080p HD Exterior','2K QHD Exterior',
            'Charcoal','Chalk White','Sandstone',
            'Mist Blue','Sky',
        ],
        'desc'    => 'Dispositivo smart home compatible con Alexa, Google Assistant y Apple HomeKit. Instalacion sencilla via app y automatizacion inteligente del hogar.',
    ],

    // ---- CAMARAS (2000) ----
    'camaras' => [
        'count'   => 2000,
        'prefix'  => 'CAMR',
        'coste'   => [80, 2500],
        'margen'  => [18, 35],
        'garantia'=> 24,
        'bases'   => [
            'Sony Alpha 7 IV','Sony Alpha 7C II','Sony Alpha 7R V',
            'Sony Alpha 6700','Sony Alpha 6400','Sony ZV-E10 II',
            'Canon EOS R6 Mark II','Canon EOS R8','Canon EOS R50',
            'Canon EOS R100','Canon PowerShot V10',
            'Nikon Z8','Nikon Z6 III','Nikon Z5 II','Nikon Z30',
            'Fujifilm X-T5','Fujifilm X-S20','Fujifilm X100VI',
            'Fujifilm GFX100S II',
            'Panasonic Lumix S5 IIX','Panasonic Lumix G9 II',
            'OM System OM-5','OM System OM-1 Mark II',
            'GoPro Hero 12 Black','GoPro Hero 11 Black',
            'DJI Osmo Action 4','DJI Osmo Pocket 3',
            'DJI Mini 4 Pro','DJI Air 3','DJI Mavic 3 Classic',
            'DJI Mini 3 Pro',
            'Insta360 X4','Insta360 GO 3','Insta360 ONE RS',
            'Ricoh GR IIIx',
            'Nikon Coolpix P1000','Panasonic Lumix DC-FZ82',
            'Polaroid Now+','Instax Mini 40',
        ],
        'specs'   => [
            'Solo Cuerpo','con Objetivo 28-70mm','con Objetivo 18-45mm',
            'con Objetivo 24-105mm','con Objetivo 16-50mm',
            'Kit Basico','Kit Avanzado','Kit Profesional',
            'Negro','Plata','Blanco','Grafito Espacial',
            'Negro Solo Cuerpo','Plata Solo Cuerpo',
            'Negro con Objetivo 28-70mm','Plata con Objetivo 28-70mm',
            '24MP Solo Cuerpo','33MP Solo Cuerpo','61MP Solo Cuerpo',
            '24MP con Objetivo','33MP con Objetivo',
            'APS-C Negro','APS-C Plata',
            'Micro 4/3 Negro','Micro 4/3 Plata',
            'Full Frame Negro',
            '4K Negro','4K Blanco','4K Gris Antracita',
            'Starter Kit Negro','Starter Kit Blanco',
            'Fly More Combo','Fly More Combo Plus',
            'con Estabilizador 3 ejes','sin Estabilizador',
            'con WiFi Bluetooth Negro','con WiFi Bluetooth Plata',
            'Negro IP68','Naranja IP68',
            'Negro sin Espejo','Negro DSLR',
            'Grafito 4K 60fps','Negro 4K 120fps',
            '16MP Negro','20MP Negro','26MP Negro',
        ],
        'desc'    => 'Camara digital con sensor de alta resolucion, optica de calidad y multiples modos de captura. Ideal para fotografia y video profesional o semiprofesional.',
    ],
];

// ============================================================
// GENERACION DEL CSV
// ============================================================
echo "Generando CSV...\n";
$fh = fopen($csvFile, 'wb');
$cols = [
    'referencia','nombre','descripcion','precio_coste','precio_proveedor',
    'precio_venta','stock_actual','stock_minimo','meses_garantia','categoria',
    'iva','codigo_iva','id_tipo_iva','id_proveedor','margen',
    'es_pack','requiere_serial','mantener_precision','activo','creado_en',
];
fputcsv($fh, $cols);

$fechaMin = strtotime('2022-01-01');
$fechaMax = strtotime('2024-12-31');
$provCiclo = 0;
$total = 0;

foreach ($cats as $cat => $cfg) {
    [$costeMin, $costeMax] = $cfg['coste'];
    [$margenMin, $margenMax] = $cfg['margen'];
    $bases   = $cfg['bases'];
    $specs   = $cfg['specs'];
    $nbBases = count($bases);
    $nbSpecs = count($specs);

    for ($i = 0; $i < $cfg['count']; $i++) {
        $ref    = $cfg['prefix'] . '-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
        $base   = $bases[$i % $nbBases];
        $spec   = $specs[$i % $nbSpecs];
        $nombre = mb_substr($base . ' ' . $spec, 0, 99);

        $coste  = round(mt_rand($costeMin * 100, $costeMax * 100) / 100, 2);
        $margen = round(mt_rand($margenMin * 10, $margenMax * 10) / 10, 1);
        $venta  = round($coste * (1 + $margen / 100), 2);

        $stock    = mt_rand(50, 350);
        $stockMin = mt_rand(5, 25);
        $prov     = ($provCiclo % 5) + 1;
        $provCiclo++;
        $fecha = date('Y-m-d H:i:s', mt_rand($fechaMin, $fechaMax));

        fputcsv($fh, [
            $ref,
            $nombre,
            $cfg['desc'],
            number_format($coste,  2, '.', ''),
            number_format($coste,  2, '.', ''),
            number_format($venta,  2, '.', ''),
            $stock,
            $stockMin,
            $cfg['garantia'],
            $cat,
            '21.00',
            'GENERAL',
            1,
            $prov,
            number_format($margen, 2, '.', ''),
            0, 0, 0, 1,
            $fecha,
        ]);
        $total++;
    }
    echo "  {$cat}: {$cfg['count']} filas escritas\n";
}

fclose($fh);
echo "CSV generado: {$total} filas en {$csvFile}\n\n";

// ============================================================
// LOAD DATA LOCAL INFILE
// ============================================================
echo "Conectando a la BD...\n";
$pdo = new PDO(DSN, USERNAME, PASSWORD, [
    PDO::MYSQL_ATTR_LOCAL_INFILE => true,
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
]);

// Habilitar local_infile en el servidor si no está activo
try {
    $pdo->exec("SET GLOBAL local_infile = 1");
} catch (PDOException $e) {
    // Puede estar ya activo; continuar
}

$csvPath = str_replace('\\', '/', realpath($csvFile));
echo "Cargando desde: {$csvPath}\n";

$sql = "LOAD DATA LOCAL INFILE '{$csvPath}'
    INTO TABLE productos
    CHARACTER SET utf8mb4
    FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
    LINES TERMINATED BY '\n'
    IGNORE 1 LINES
    (referencia, nombre, descripcion, precio_coste, precio_proveedor, precio_venta,
     stock_actual, stock_minimo, meses_garantia, categoria,
     iva, codigo_iva, id_tipo_iva, id_proveedor, margen,
     es_pack, requiere_serial, mantener_precision, activo, creado_en)
    SET icono = NULL, variantes = NULL, atributos = NULL";

$pdo->exec($sql);

$count = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
echo "Productos insertados en BD: {$count}\n";

// Eliminar CSV temporal
unlink($csvFile);
echo "Archivo temporal eliminado.\n";
echo "Completado.\n";
