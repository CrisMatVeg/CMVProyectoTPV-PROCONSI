<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: obtenerVenta.php
 * Devuelve una venta completa (incluye líneas) en JSON.
 */
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Sesión no válida o expirada']);
        exit;
    }

    $numTicket = null;
    $idVenta   = null;

    // 1. Intentar obtener desde GET (uso común en enlaces y redirecciones)
    if (isset($_GET['num'])) $numTicket = (int)$_GET['num'];
    if (isset($_GET['id']))  $idVenta   = (int)$_GET['id'];

    // 2. Intentar obtener desde POST JSON (uso común en llamadas fetch asíncronas)
    if (!$numTicket && !$idVenta) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['numTicket'])) $numTicket = (int)$input['numTicket'];
        if (isset($input['idVenta']))   $idVenta   = (int)$input['idVenta'];
    }

    if (!$numTicket && !$idVenta) {
        throw new Exception('Número de ticket o ID de venta faltante');
    }

    if ($numTicket) {
        $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
    } else {
        $venta = VentaPDO::obtenerVentaPorId($idVenta);
    }

    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    echo json_encode(['ok' => true, 'venta' => $venta]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
