<?php
/**
 * Script de verificación de rendimiento para ElectroBazar TPV.
 */
require_once 'model/DBPDO.php';

echo "Verificando Índices...\n";

$tables = ['ventas', 'cierres_fiscales', 'caja_deudas'];

foreach ($tables as $table) {
    echo "\nÍndices en tabla '$table':\n";
    $q = DBPDO::ejecutarConsulta("SHOW INDEX FROM $table");
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Key_name']} ({$row['Column_name']})\n";
    }
}

echo "\nPrueba de rendimiento (Ventas con OFFSET profundo)...\n";

$start = microtime(true);
// Simulamos una búsqueda en la última página de un set de datos grande
$sql = "SELECT id FROM ventas ORDER BY fecha DESC LIMIT 50 OFFSET 1000";
DBPDO::ejecutarConsulta($sql);
$end = microtime(true);

echo "Tiempo de consulta (ID scan): " . round(($end - $start) * 1000, 2) . "ms\n";

echo "\nPrueba de Cierres (Agregados con joins)...\n";
$start = microtime(true);
$sql = "SELECT cf.id FROM cierres_fiscales cf JOIN ventas v ON v.num_z = cf.id GROUP BY cf.id LIMIT 50";
DBPDO::ejecutarConsulta($sql);
$end = microtime(true);

echo "Tiempo de consulta (Join Z): " . round(($end - $start) * 1000, 2) . "ms\n";

echo "\nVerificación completada.\n";
