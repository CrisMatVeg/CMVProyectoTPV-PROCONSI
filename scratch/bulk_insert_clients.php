<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

require_once 'config/confDBPDO.php';

$numClientes = 400000;
$csvFile = __DIR__ . '/clientes_stress_test.csv';

echo "Starting clientes stress test data generation...\n";

// Datos aleatorios realistas
$tipos = ['particular', 'empresa'];
$roles = ['particular', 'empresa', 'admin'];
$provincias = ['Valencia', 'Madrid', 'Barcelona', 'Sevilla', 'Zaragoza', 'Málaga', 'Murcia', 'Palma', 'Bilbao', 'Alicante'];
$poblaciones = [
    'Valencia' => ['Valencia', 'Paterna', 'Torrent', 'Gandía', 'Sagunto'],
    'Madrid'   => ['Madrid', 'Alcalá de Henares', 'Leganés', 'Getafe', 'Móstoles'],
    'Barcelona'=> ['Barcelona', 'Badalona', 'Sabadell', 'Terrassa', 'Mataró'],
    'Sevilla'  => ['Sevilla', 'Dos Hermanas', 'Utrera', 'Écija', 'Carmona'],
    'Zaragoza' => ['Zaragoza', 'Calatayud', 'Ejea', 'Tarazona', 'Utebo'],
    'Málaga'   => ['Málaga', 'Marbella', 'Vélez-Málaga', 'Fuengirola', 'Torremolinos'],
    'Murcia'   => ['Murcia', 'Cartagena', 'Lorca', 'Molina de Segura', 'Yecla'],
    'Palma'    => ['Palma', 'Inca', 'Manacor', 'Llucmajor', 'Calvià'],
    'Bilbao'   => ['Bilbao', 'Barakaldo', 'Getxo', 'Basauri', 'Portugalete'],
    'Alicante' => ['Alicante', 'Elche', 'Torrevieja', 'Benidorm', 'Orihuela'],
];
$nombresMasc = ['Carlos','Juan','Pedro','Miguel','Antonio','David','José','Manuel','Francisco','Alejandro'];
$nombresFem  = ['María','Laura','Ana','Carmen','Sofía','Isabel','Lucía','Elena','Sara','Marta'];
$apellidos   = ['García','López','Martínez','Sánchez','Pérez','González','Rodríguez','Fernández','Torres','Ramírez'];

try {
    $optionsCheck = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    $pdoPre = new PDO(DSN, USERNAME, PASSWORD, $optionsCheck);
    $countBefore = $pdoPre->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    echo "Clientes antes de insertar: $countBefore\n";

    // 1. Generar CSV
    $file = fopen($csvFile, 'w');
    if (!$file) throw new Exception("No se pudo crear el CSV en $csvFile");

    for ($i = 1; $i <= $numClientes; $i++) {
        $tipo      = $tipos[array_rand($tipos)];
        $rol       = $roles[array_rand($roles)];
        $esMasc    = rand(0, 1);
        $nombre    = $esMasc
                        ? $nombresMasc[array_rand($nombresMasc)]
                        : $nombresFem[array_rand($nombresFem)];
        $apellido1 = $apellidos[array_rand($apellidos)];
        $apellido2 = $apellidos[array_rand($apellidos)];
        $apellidosStr = "$apellido1 $apellido2";

        // NIF único usando el índice + sufijo aleatorio
        $nif       = str_pad($i, 8, '0', STR_PAD_LEFT) . chr(rand(65, 90));

        $email     = strtolower($nombre) . '.' . $i . '@test.com';
        $telefono  = '6' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        $direccion = 'Calle Test ' . rand(1, 999) . ', ' . rand(1, 20) . 'º';
        $cp        = str_pad(rand(1000, 52999), 5, '0', STR_PAD_LEFT);

        $provincia = $provincias[array_rand($provincias)];
        $poblacion = $poblaciones[$provincia][array_rand($poblaciones[$provincia])];

        $esSocio   = rand(0, 1);
        $fechaAlta = date('Y-m-d H:i:s', rand(
            strtotime('2018-01-01'),
            strtotime('2025-12-31')
        ));
        // fecha_baja: solo algunos clientes la tienen
        $fechaBaja = rand(0, 10) === 0
                        ? date('Y-m-d H:i:s', strtotime($fechaAlta) + rand(86400, 31536000))
                        : '';
        $notas        = '';
        $puntos       = rand(0, 5000);
        $ultimaCompra = rand(0, 1)
                        ? date('Y-m-d H:i:s', rand(strtotime($fechaAlta), strtotime('2025-12-31')))
                        : '';

        fputcsv($file, [
            $tipo, $rol, $nombre, $apellidosStr, $nif, $email,
            $telefono, $direccion, $cp, $poblacion, $provincia,
            $esSocio, $fechaAlta, $fechaBaja, $notas, $puntos, $ultimaCompra
        ]);
    }

    fclose($file);
    echo "CSV generado con $numClientes registros.\n";

    // 2. Conectar y cargar
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_LOCAL_INFILE => true
    ];
    $pdo = new PDO(DSN, USERNAME, PASSWORD, $options);
    $pdo->exec("SET GLOBAL local_infile = 1");

    $csvPathForSql = str_replace('\\', '/', $csvFile);

    $sql = "
        LOAD DATA LOCAL INFILE '$csvPathForSql'
        INTO TABLE clientes
        FIELDS TERMINATED BY ','
        OPTIONALLY ENCLOSED BY '\"'
        LINES TERMINATED BY '\n'
        (tipo, rol, nombre, apellidos, nif, email,
         telefono, direccion, cp, poblacion, provincia,
         es_socio, fecha_alta,
         @fecha_baja, notas, puntos, @ultima_compra)
        SET
            fecha_baja    = NULLIF(@fecha_baja, ''),
            ultima_compra = NULLIF(@ultima_compra, '')
    ";
    // Nota: id se omite (auto_increment)
    // fecha_baja y ultima_compra usan variables @var + NULLIF para convertir
    // cadena vacía en NULL, ya que esas columnas admiten NULL en tu tabla

    echo "Ejecutando LOAD DATA LOCAL INFILE...\n";
    $startTime   = microtime(true);
    $affectedRows = $pdo->exec($sql);
    $endTime     = microtime(true);

    $duration = round($endTime - $startTime, 4);
    echo "Filas insertadas: $affectedRows\n";
    echo "Tiempo: $duration segundos.\n";

    // 3. Verificación
    $countAfter = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    echo "Total clientes en BD: $countAfter\n";
    echo "Nuevos añadidos: " . ($countAfter - $countBefore) . "\n";

    // 4. Limpieza
    unlink($csvFile);
    echo "CSV temporal eliminado.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}