<?php
// ============================================================
// gen_clientes.php - 400.000 clientes con LOAD DATA LOCAL INFILE
// Ejecutar: php scriptsDB/seed/gen_clientes.php
// ============================================================
set_time_limit(0);
ini_set('memory_limit', '256M');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../../config/confDBPDO.php';

$tmpDir  = __DIR__ . '/tmp';
$csvFile = $tmpDir . '/clientes.csv';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

// ============================================================
// POOLS DE DATOS
// ============================================================

$nombres_h = [
    'Alejandro','Alberto','Álvaro','Andrés','Antonio','Arturo','Borja','Carlos',
    'Cristian','Daniel','David','Diego','Eduardo','Emilio','Enrique','Ernesto',
    'Esteban','Felipe','Fernando','Francisco','Gabriel','Gonzalo','Guillermo',
    'Héctor','Hugo','Ignacio','Iván','Jaime','Javier','Jorge','José','Juan',
    'Julián','Leonardo','Lorenzo','Lucas','Luis','Manuel','Marcos','Mario',
    'Mateo','Miguel','Nicolás','Óscar','Pablo','Pedro','Rafael','Ramón',
    'Raúl','Ricardo','Roberto','Rodrigo','Rubén','Salvador','Santiago',
    'Sebastián','Sergio','Simón','Tomás','Valentín','Vicente','Víctor',
    'Adrián','Agustín','Aitor','Alfredo','Alonso','Armando','Aurelio',
    'Benito','Benjamín','Cayetano','César','Claudio','Cristóbal',
];

$nombres_m = [
    'Adriana','Ainhoa','Alicia','Almudena','Amanda','Amparo','Ana','Andrea',
    'Ángela','Antonia','Bárbara','Beatriz','Belén','Berta','Blanca','Carmen',
    'Celia','Clara','Claudia','Concepción','Consuelo','Coral','Cristina',
    'Diana','Dolores','Elena','Elvira','Encarnación','Esther','Esperanza',
    'Eva','Fernanda','Gloria','Inés','Irene','Isabel','Julia','Laura',
    'Leticia','Lorena','Lourdes','Lucía','Luciana','Macarena','Manuela',
    'María','Marta','Mercedes','Miriam','Mónica','Natalia','Nuria','Olga',
    'Paloma','Patricia','Paula','Pilar','Raquel','Rebeca','Rocío','Rosa',
    'Sara','Sofía','Sonia','Susana','Teresa','Valentina','Valeria','Vanessa',
    'Verónica','Virginia','Yolanda','Zaida',
];

$pool_apellidos = [
    'García','González','Rodríguez','Fernández','López','Martínez','Sánchez',
    'Pérez','Gómez','Martín','Jiménez','Hernández','Díaz','Moreno','Muñoz',
    'Álvarez','Romero','Alonso','Gutiérrez','Navarro','Torres','Domínguez',
    'Vázquez','Ramos','Gil','Serrano','Blanco','Molina','Morales','Suárez',
    'Ortega','Delgado','Castro','Ortiz','Rubio','Marín','Sanz','Iglesias',
    'Núñez','Medina','Garrido','Cortés','Castillo','Santos','Lozano',
    'Guerrero','Cano','Prieto','Méndez','Cruz','Calvo','Gallego','Vidal',
    'León','Herrera','Márquez','Peña','Flores','Cabrera','Fuentes','Arias',
    'Carmona','Reyes','Aguilar','Pascual','Santiago','Herrero','Pardo',
    'Montes','Carrasco','Campos','Hidalgo','Vargas','Ibáñez','Giménez',
    'Crespo','Bravo','Ferrer','Lorenzo','Mora','Esteban','Moya','Benítez',
    'Soler','Caballero','Expósito','Serra','Puig','Roca','Font','Costa',
    'Vila','Bosch','Sala','Llopis','Roger','Molins','Planas','Camps',
    'Pons','Marco','Durán','Prat','Simón','Valls','Figueras','Castelló',
    'Vergés','Boix','Vives','Mir','Obiols','Clarà','Bassols','Bach',
    'Tarragó','Tort','Jané','Masip','Ribas','Puigdollers','Sistach',
    'Franch','Pijuan','Bover','Mestre','Compte','Palau','Iborra','Signes',
    'Meseguer','Verdú','Colom','Tous','Noguera','Garriga','Esteve','Arnau',
    'Vendrell','Mas','Sala','Pou','Fortuny','Grau','Aranda','Balaguer',
    'Benedicto','Blesa','Borrás','Carbonell','Cuesta','Doménech','Elvira',
    'Escribà','Ferri','Giner','Igual','Jarque','Leal','Llopís','Martí',
    'Miquel','Monfort','Montaner','Mulet','Nebot','Oller','Parra','Querol',
    'Ripoll','Roig','Rosell','Safont','Sanz','Seguí','Selma','Talens',
    'Tudela','Urios','Usó','Vaquer','Ventura','Vilar','Villalba','Vinuesa',
];

// Nombres de empresa realistas (se ciclan con sufijo numérico)
$tipos_empresa = [
    'Tecnología Digital','Servicios Informáticos','Distribuciones Electrónicas',
    'Soluciones IT','Innovación Tecnológica','Sistemas Avanzados',
    'Comunicaciones Globales','Redes y Conectividad','Electrónica Aplicada',
    'Ingeniería de Sistemas','Software Empresarial','Hardware Solutions',
    'Telecomunicaciones Sur','Montajes Electrónicos','Automatización Industrial',
    'Seguridad Electrónica','Audiovisuales Profesionales','Informática Empresarial',
    'Gestión Digital','Consultoría Tecnológica','Proyectos Digitales',
    'Desarrollos Multimedia','Instalaciones Técnicas','Mantenimiento Informático',
    'Soluciones Cloud','Infraestructura IT','Virtualización y Datos',
    'Comercio Electrónico','Servicios Cloud','Digitalización Empresarial',
];
$formas_jur = ['S.L.','S.A.','S.L.U.','S.A.U.','S.C.','S.Coop.'];

// Ciudades: [ciudad, provincia, cp]
$ciudades = [
    ['Madrid','Madrid','28001'],['Madrid','Madrid','28010'],
    ['Madrid','Madrid','28020'],['Madrid','Madrid','28030'],
    ['Madrid','Madrid','28045'],['Móstoles','Madrid','28930'],
    ['Alcalá de Henares','Madrid','28801'],['Getafe','Madrid','28901'],
    ['Leganés','Madrid','28914'],['Alcorcón','Madrid','28921'],
    ['Barcelona','Barcelona','08001'],['Barcelona','Barcelona','08010'],
    ['Barcelona','Barcelona','08020'],['Barcelona','Barcelona','08030'],
    ['Badalona','Barcelona','08911'],['Hospitalet de Llobregat','Barcelona','08901'],
    ['Terrassa','Barcelona','08221'],['Sabadell','Barcelona','08201'],
    ['Mataró','Barcelona','08301'],['Cornellà de Llobregat','Barcelona','08940'],
    ['Valencia','Valencia','46001'],['Valencia','Valencia','46010'],
    ['Valencia','Valencia','46020'],['Torrent','Valencia','46900'],
    ['Gandía','Valencia','46700'],
    ['Sevilla','Sevilla','41001'],['Sevilla','Sevilla','41010'],
    ['Dos Hermanas','Sevilla','41701'],['Alcalá de Guadaíra','Sevilla','41500'],
    ['Zaragoza','Zaragoza','50001'],['Zaragoza','Zaragoza','50015'],
    ['Málaga','Málaga','29001'],['Marbella','Málaga','29600'],
    ['Fuengirola','Málaga','29640'],['Vélez-Málaga','Málaga','29700'],
    ['Murcia','Murcia','30001'],['Cartagena','Murcia','30201'],
    ['Bilbao','Vizcaya','48001'],['Barakaldo','Vizcaya','48901'],
    ['Alicante','Alicante','03001'],['Elche','Alicante','03201'],
    ['Córdoba','Córdoba','14001'],['Valladolid','Valladolid','47001'],
    ['Vigo','Pontevedra','36201'],['Gijón','Asturias','33201'],
    ['Granada','Granada','18001'],['Oviedo','Asturias','33001'],
    ['Las Palmas de Gran Canaria','Las Palmas','35001'],
    ['Santa Cruz de Tenerife','Santa Cruz de Tenerife','38001'],
    ['Palma','Islas Baleares','07001'],['Pamplona','Navarra','31001'],
    ['San Sebastián','Guipúzcoa','20001'],['Santander','Cantabria','39001'],
    ['Burgos','Burgos','09001'],['Albacete','Albacete','02001'],
    ['Badajoz','Badajoz','06001'],['Huelva','Huelva','21001'],
    ['Jaén','Jaén','23001'],['Salamanca','Salamanca','37001'],
    ['Logroño','La Rioja','26001'],['Cáceres','Cáceres','10001'],
];

$tipos_via = [
    'Calle','Avenida','Paseo','Plaza','Ronda','Vía',
    'Calle Mayor de','Gran Vía de','Bulevar de','Travesía de',
];
$nombres_via = [
    'Mayor','España','Libertad','Constitución','Real','Cervantes','Colón',
    'Alfonso XIII','Rey Juan Carlos I','Reyes Católicos','General Perón',
    'Alcalá','Gran Vía','Serrano','Goya','Velázquez','Castellana',
    'Sagrada Familia','Rambla','Diagonal','Passeig de Gràcia',
    'Aragón','Balmes','Muntaner','Valencia','Pau Claris','Enric Granados',
    'Rios Rosas','Alberto Aguilera','Bravo Murillo','Fernández de la Hoz',
    'Fuencarral','Hortaleza','Montera','Preciados','Atocha',
    'Doctor Esquerdo','Menéndez Pelayo','Ibiza','Narváez','Núñez de Balboa',
    'Ortega y Gasset','Príncipe de Vergara','Quintana','Sagasta',
    'Santa Engracia','Trafalgar','Zurbano','Génova','Almagro',
];

$dominios = [
    'gmail.com','hotmail.com','hotmail.es','yahoo.es','outlook.com',
    'outlook.es','icloud.com','telefonica.net','vodafone.es','movistar.es',
];

// ============================================================
// GENERACIÓN DEL CSV
// ============================================================
echo "Generando CSV de 400.000 clientes...\n";
$fh = fopen($csvFile, 'wb');

$cols = [
    'tipo','rol','nombre','apellidos','nif','email','telefono',
    'direccion','cp','poblacion','provincia','es_socio','fecha_alta',
];
fputcsv($fh, $cols);

$nbH       = count($nombres_h);
$nbM       = count($nombres_m);
$nbAp      = count($pool_apellidos);
$nbCiud    = count($ciudades);
$nbVia     = count($nombres_via);
$nbTVia    = count($tipos_via);
$nbDom     = count($dominios);
$nbTEmp    = count($tipos_empresa);
$nbFJ      = count($formas_jur);

$fechaMin  = strtotime('2018-01-01');
$fechaMax  = strtotime('2025-05-01');

$total = 400000;
$chunk = 10000;

for ($i = 0; $i < $total; $i++) {
    // --- TIPO Y ROL ---
    $mod = $i % 100;
    if ($mod < 10) {
        // 10% empresa
        $tipo  = 'empresa';
        $tipoEmpNom = $tipos_empresa[$i % $nbTEmp];
        $seq   = intdiv($i, $nbTEmp) + 1;
        $nombre    = mb_substr($tipoEmpNom . ($seq > 1 ? " $seq" : '') . ' ' . $formas_jur[$i % $nbFJ], 0, 99);
        $apellidos_cli = '';
        $rol   = 'empresa';
    } else {
        $tipo = 'particular';
        // Alterna hombre/mujer por paridad del índice sin empresa
        $esMujer = ($i % 2 === 0);
        if ($esMujer) {
            $nombre = $nombres_m[$i % $nbM];
        } else {
            $nombre = $nombres_h[$i % $nbH];
        }
        $ap1           = $pool_apellidos[$i % $nbAp];
        $ap2           = $pool_apellidos[($i * 7 + 3) % $nbAp];
        $apellidos_cli = $ap1 . ' ' . $ap2;

        // ROL: 84% particular, 5% vip, 1% mayorista (del 90% no-empresa)
        if ($mod < 11)      $rol = 'mayorista';   // 1%
        elseif ($mod < 16)  $rol = 'vip';          // 5%
        else                $rol = 'particular';   // resto
    }

    // --- DIRECCIÓN ---
    $ciudad   = $ciudades[$i % $nbCiud];
    $via      = $tipos_via[$i % $nbTVia] . ' ' . $nombres_via[$i % $nbVia];
    $num      = ($i % 199) + 1;
    $piso     = ['', '', '', ', 1° A', ', 2° B', ', 3° C', ', 4° D', ', Bajo', ', Ático'][$i % 9];
    $direccion = mb_substr($via . ' ' . $num . $piso, 0, 254);

    // CP con variación de 2 dígitos finales (ciudades son arrays numéricos: [0]=ciudad, [1]=provincia, [2]=cp)
    $cpBase = $ciudad[2];
    $cpSuf  = str_pad(($i % 90) + 1, 2, '0', STR_PAD_LEFT);
    $cp     = substr($cpBase, 0, 3) . $cpSuf;

    // --- CONTACTO ---
    $nombreSlug = mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $nombre)));
    $emailUser  = $nombreSlug . ($i % 999 > 0 ? ($i % 999) : '');
    $email      = mb_substr($emailUser . '@' . $dominios[$i % $nbDom], 0, 119);

    $telPrefijo = ($i % 3 === 0) ? '7' : '6';
    $telResto   = str_pad($i % 99999999, 8, '0', STR_PAD_LEFT);
    $telefono   = $telPrefijo . $telResto;

    // --- ES SOCIO ---
    $esSocio = ($i % 10 === 0) ? 1 : 0;

    // --- FECHA ALTA ---
    $ts = $fechaMin + intval(($fechaMax - $fechaMin) * ($i / $total));
    $ts += mt_rand(-86400 * 30, 86400 * 30); // variación ±30 días
    $ts = max($fechaMin, min($fechaMax, $ts));
    $fechaAlta = date('Y-m-d H:i:s', $ts);

    // nif = \N (NULL en LOAD DATA INFILE)
    fputcsv($fh, [
        $tipo,
        $rol,
        $nombre,
        $apellidos_cli,
        '\N',     // nif → NULL (LOAD DATA interpreta \N como NULL)
        $email,
        $telefono,
        $direccion,
        $cp,
        $ciudad[0],
        $ciudad[1],
        $esSocio,
        $fechaAlta,
    ]);

    if (($i + 1) % $chunk === 0) {
        echo "  " . number_format($i + 1) . " / " . number_format($total) . " filas...\n";
    }
}

fclose($fh);
$sizeMB = round(filesize($csvFile) / 1024 / 1024, 1);
echo "CSV generado: {$sizeMB} MB en {$csvFile}\n\n";

// ============================================================
// LOAD DATA LOCAL INFILE
// ============================================================
echo "Conectando a la BD...\n";
$pdo = new PDO(DSN, USERNAME, PASSWORD, [
    PDO::MYSQL_ATTR_LOCAL_INFILE => true,
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
]);
try { $pdo->exec("SET GLOBAL local_infile = 1"); } catch (PDOException $e) {}

$csvPath = str_replace('\\', '/', realpath($csvFile));
echo "Cargando desde: {$csvPath}\n";

// nif está en el CSV como \N → LOAD DATA lo interpretará como NULL
// gracias a FIELDS ESCAPED BY '\\'
$sql = "LOAD DATA LOCAL INFILE '{$csvPath}'
    INTO TABLE clientes
    CHARACTER SET utf8mb4
    FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' ESCAPED BY '\\\\'
    LINES TERMINATED BY '\n'
    IGNORE 1 LINES
    (tipo, rol, nombre, apellidos, nif, email, telefono,
     direccion, cp, poblacion, provincia, es_socio, fecha_alta)
    SET fecha_baja = NULL,
        notas     = NULL";

$pdo->exec($sql);

$count = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
echo "Clientes insertados en BD: " . number_format($count) . "\n";

unlink($csvFile);
echo "Archivo temporal eliminado.\n";
echo "Completado.\n";
