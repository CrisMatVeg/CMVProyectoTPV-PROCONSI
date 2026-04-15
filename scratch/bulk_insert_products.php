<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

require_once 'config/confDBPDO.php';

// Configuration
$numProducts = 20000;
$csvFile = __DIR__ . '/products_stress_test.csv';
$ivaId = 1; // GENERAL
$ivaCode = 'GENERAL';
$ivaPercent = 21.00;
$provId = 1;

echo "Starting stress test data generation...\n";

try {
    // 0. Check count before
    $optionsCheck = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    $pdoPre = new PDO(DSN, USERNAME, PASSWORD, $optionsCheck);
    $countBefore = $pdoPre->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    echo "Count before insertion: $countBefore\n";

    // 1. Generate CSV
    $file = fopen($csvFile, 'w');
    if (!$file) throw new Exception("Could not create CSV file at $csvFile");

    // Columns: referencia, nombre, descripcion, precio_coste, precio_proveedor, precio_venta, stock_actual, stock_minimo, meses_garantia, categoria, iva, codigo_iva, id_tipo_iva, id_proveedor, margen, es_pack, requiere_serial, activo
    for ($i = 1; $i <= $numProducts; $i++) {
        // Use a more unique prefix to avoid any collisions
        $ref = 'STRESS_TEST_' . str_pad($i, 6, '0', STR_PAD_LEFT);
        $name = "Product Stress Test $i";
        $desc = "Automatic generation for performance testing purposes. Record number $i.";
        $cost = rand(10, 500) / 100;
        $provPrice = $cost * 1.1;
        $salePrice = $cost * 1.5;
        $stock = rand(0, 100);
        $stockMin = rand(1, 5);
        $garantia = 24;
        $category = 'STRESS_TEST';
        $margen = $salePrice - $cost;
        
        fputcsv($file, [
            $ref, $name, $desc, $cost, $provPrice, $salePrice, 
            $stock, $stockMin, $garantia, $category, $ivaPercent, $ivaCode, 
            $ivaId, $provId, $margen, 0, 0, 1
        ]);
    }
    fclose($file);
    echo "Generated $numProducts product records in CSV.\n";

    // 2. Connect and Load
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_LOCAL_INFILE => true
    ];
    
    $pdo = new PDO(DSN, USERNAME, PASSWORD, $options);

    // Ensure it's enabled on server side too
    $pdo->exec("SET GLOBAL local_infile = 1");

    $csvPathForSql = str_replace('\\', '/', $csvFile);

    // Adjusting line termination to '\n' as fputcsv usually uses that
    $sql = "
        LOAD DATA LOCAL INFILE '$csvPathForSql'
        INTO TABLE productos
        FIELDS TERMINATED BY ',' 
        OPTIONALLY ENCLOSED BY '\"'
        LINES TERMINATED BY '\n'
        (referencia, nombre, descripcion, precio_coste, precio_proveedor, precio_venta, 
         stock_actual, stock_minimo, meses_garantia, categoria, iva, codigo_iva, 
         id_tipo_iva, id_proveedor, margen, es_pack, requiere_serial, activo)
    ";

    echo "Executing LOAD DATA LOCAL INFILE...\n";
    $startTime = microtime(true);
    $affectedRows = $pdo->exec($sql);
    $endTime = microtime(true);
    
    $duration = round($endTime - $startTime, 4);
    echo "Affected rows: $affectedRows\n";
    echo "Successfully loaded products in $duration seconds.\n";

    // 3. Verification
    $countAfter = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    echo "Total products in database: $countAfter\n";
    echo "Newly added: " . ($countAfter - $countBefore) . "\n";

    // 4. Cleanup
    unlink($csvFile);
    echo "Temporary CSV file removed.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (isset($csvFile) && file_exists($csvFile)) {
        // unlink($csvFile); // Keep it for debugging if needed or remove it?
    }
    exit(1);
}
