<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: liquidarVenta.php
 * Permite registrar un pago parcial o total de una venta con estado 'pendiente_pago'.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/VentaPDO.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $idVenta = (int)($input['idVenta'] ?? 0);
    $importe = (float)($input['importe'] ?? 0);
    $metodo  = $input['metodo'] ?? 'efectivo';

    if ($idVenta <= 0 || $importe <= 0) {
        throw new Exception('ID de venta o importe inválido');
    }

    if (!in_array($metodo, ['efectivo', 'tarjeta', 'bizum', 'transferencia', 'otro'])) {
        $metodo = 'efectivo';
    }

    $idUsuario = $_SESSION['usuarioActualTPV']->getId();
    $res = VentaPDO::liquidarVentaPendiente($idVenta, $importe, $metodo, $idUsuario);

    if ($res) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'No se pudo liquidar la venta (puede que ya esté completada o no exista)']);
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
