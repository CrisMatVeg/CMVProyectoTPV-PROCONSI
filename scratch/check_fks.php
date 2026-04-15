<?php
require_once 'config/confDBPDO.php';
try {
    $pdo = new PDO(DSN, USERNAME, PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $iva = $pdo->query("SELECT id, codigo, porcentaje FROM tipos_iva WHERE id = 4")->fetch(PDO::FETCH_ASSOC);
    if (!$iva) {
        $iva = $pdo->query("SELECT id, codigo, porcentaje FROM tipos_iva LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }
    
    $prov = $pdo->query("SELECT id FROM proveedores LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'iva' => $iva,
        'prov' => $prov
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
