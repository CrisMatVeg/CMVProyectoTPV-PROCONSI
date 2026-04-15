<?php
require_once 'config/confDBPDO.php';
try {
    $pdo = new PDO(DSN, USERNAME, PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $ivas = $pdo->query("SELECT id, codigo, porcentaje FROM tipos_iva")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ivas' => $ivas]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
