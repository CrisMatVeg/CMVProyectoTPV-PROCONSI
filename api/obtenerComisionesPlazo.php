<?php

/**
 * API: obtenerComisionesPlazo.php
 * Obtiene la comisión de financiación según la entidad y número de meses
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuarioActualTPV'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require_once '../model/FinancieraPDO.php';

// Parámetros por GET o POST
$idFinanciera = $_GET['id'] ?? $_POST['id'] ?? null;
$meses = $_GET['meses'] ?? $_POST['meses'] ?? null;

if (!$idFinanciera || !$meses) {
    echo json_encode(['ok' => false, 'error' => 'Parámetros requeridos: id y meses']);
    exit;
}

try {
    $comision = FinancieraPDO::obtenerComisionesPorPlazo((int)$idFinanciera, (int)$meses);
    
    if (!$comision) {
        echo json_encode([
            'ok' => false,
            'error' => 'No se encontró comisión para esa combinación de financiera y plazo'
        ]);
        exit;
    }
    
    echo json_encode(['ok' => true, 'comision' => $comision]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
