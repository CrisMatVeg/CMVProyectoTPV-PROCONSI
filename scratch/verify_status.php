<?php
require_once 'config/confDBPDO.php';
try {
    $pdo = new PDO(DSN, USERNAME, PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $count = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    $local_infile = $pdo->query("SHOW VARIABLES LIKE 'local_infile'")->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'count' => $count,
        'local_infile' => $local_infile['Value'] ?? 'unknown'
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
