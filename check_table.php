<?php
require_once './config/confDBPDO.php';
try {
    $conexion = new PDO(DSN, USERNAME, PASSWORD);
    $q = $conexion->query("DESCRIBE categorias");
    if ($q) {
        echo "EXISTS";
    } else {
        echo "NOT_EXISTS";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
