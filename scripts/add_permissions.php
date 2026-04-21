<?php
require_once __DIR__ . "/../config/confDBPDO.php";

try {
    $db = new PDO(DSN, USERNAME, PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $newPerms = [
        ['aplicar_descuentos', 'Permitir aplicar descuentos manuales en el TPV'],
        ['gestionar_devoluciones', 'Permitir realizar abonos y devoluciones'],
        ['anular_ventas', 'Permitir anular tickets o líneas de venta'],
        ['ver_costes', 'Permitir visualizar precios de coste y márgenes'],
        ['gestionar_proveedores', 'Administrar la base de datos de proveedores'],
        ['gestionar_inventario', 'Realizar entradas de stock y albaranes'],
        ['gestionar_gastos', 'Registrar retiradas de efectivo y gastos de caja']
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO permisos (clave, descripcion) VALUES (?, ?)");

    echo "Añadiendo nuevos permisos...\n";
    foreach ($newPerms as $p) {
        $stmt->execute($p);
        if ($stmt->rowCount() > 0) {
            echo " - Creado: {$p[0]}\n";
        } else {
            echo " - Ya existía o error: {$p[0]}\n";
        }
    }

    echo "Operación completada.\n";

} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage() . "\n";
}
