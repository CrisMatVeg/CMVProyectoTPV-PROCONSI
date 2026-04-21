<?php
require_once __DIR__ . '/../../config/confDBPDO.php';
require_once __DIR__ . '/../../model/DBPDO.php';
require_once __DIR__ . '/../../model/VentaPDO.php';
require_once __DIR__ . '/../../model/CajaTurnoPDO.php';

// Mock session
session_start();
$_SESSION['usuarioActualTPV'] = (object)['getId' => function() { return 1; }];

try {
    // 1. Get current cash
    $cash = CajaTurnoPDO::obtenerEfectivoActual();
    echo "Current cash: $cash\n";

    // 2. Try a sale that requires more change than $cash
    $total = 10;
    $received = $cash + 20; // Needs 20 more change than available
    
    $datos = [
        'tipoCliente' => 'particular',
        'metodoPago' => 'efectivo',
        'subtotal' => 8.26,
        'total' => $total,
        'efectivo' => ['recibido' => $received],
        'lineas' => [
            ['id' => 1, 'qty' => 1, 'price' => 10, 'name' => 'Test Product', 'codigo' => 'TEST']
        ]
    ];

    echo "Attempting sale: Total=$total, Received=$received, Expected Change=" . ($received - $total) . "\n";
    VentaPDO::guardarVenta($datos, 1);
    echo "ERROR: Sale should have been blocked!\n";
} catch (Exception $e) {
    echo "SUCCESS: Caught expected exception: " . $e->getMessage() . "\n";
}
?>
