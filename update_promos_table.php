<?php
require_once './config/confDBPDO.php';
try {
    $conexion = new PDO(DSN, USERNAME, PASSWORD);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Modify 'tipo' enum if possible, or just add new types logic in code
    // Since it's MySQL, we might need to modify the enum.
    $conexion->exec("ALTER TABLE promociones MODIFY COLUMN tipo ENUM('percent', 'amount', 'bundle', 'fixed_bundle')");

    // 2. Add bundle specific columns
    $conexion->exec("ALTER TABLE promociones ADD COLUMN bundle_buy_qty INT DEFAULT NULL AFTER valor");
    $conexion->exec("ALTER TABLE promociones ADD COLUMN bundle_pay_qty INT DEFAULT NULL AFTER bundle_buy_qty");
    $conexion->exec("ALTER TABLE promociones ADD COLUMN id_producto INT DEFAULT NULL AFTER bundle_pay_qty");
    $conexion->exec("ALTER TABLE promociones ADD COLUMN id_categoria INT DEFAULT NULL AFTER id_producto");

    echo "SUCCESS: Promotions table updated for bundles.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
