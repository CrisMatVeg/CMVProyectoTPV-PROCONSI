<?php
// endpoint sencillo para devolver una venta completa (incluye líneas) en JSON
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/VentaPDO.php';

    session_start();
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Sesión no válida o expirada']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $numTicket = isset($input['numTicket']) ? (int)$input['numTicket'] : null;

    if (!$numTicket) {
        throw new Exception('Número de ticket faltante');
    }

    $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    echo json_encode(['ok' => true, 'venta' => $venta]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
