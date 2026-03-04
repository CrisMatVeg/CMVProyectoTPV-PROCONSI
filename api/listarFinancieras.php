<?php

/**
 * API: listarFinancieras.php
 * Returns active financing entities with their per-month commission tiers.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuarioActualTPV'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

ob_start();

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/FinancieraPDO.php';

    $lista = FinancieraPDO::listarConComisiones();

    ob_end_clean();
    echo json_encode(['ok' => true, 'financieras' => $lista]);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
