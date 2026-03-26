<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: listarValesCliente.php
 * Devuelve la lista de vales activos de un cliente seleccionado.
 */

// Desactivar visualización de errores para no romper el JSON
ini_set('display_errors', 0);
error_reporting(0);

// Iniciar búfer de salida para limpiar cualquier salida inesperada
ob_start();

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/ValePDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    // session_start(); // Handled by csrf_check.php

    if (!isset($_SESSION['usuarioActualTPV'])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Sesión no iniciada']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    $idCliente = isset($data['id_cliente']) ? (int)$data['id_cliente'] : null;

    if (!$idCliente) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'ID de cliente no proporcionado']);
        exit;
    }

    $vales = ValePDO::obtenerValesPorCliente($idCliente);
    // Filtrar solo los activos con saldo
    $activos = array_filter($vales, function($v) {
        return $v['estado'] === 'activo' && $v['importe_restante'] > 0;
    });

    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true, 
        'vales' => array_values($activos)
    ]);
} catch (Throwable $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

