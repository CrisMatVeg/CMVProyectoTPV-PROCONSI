<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: registrarLogUI.php
 * Permite registrar logs desde el frontend (JS)
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/LogPDO.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? '';
    $descripcion = $input['descripcion'] ?? '';
    $detalles = $input['detalles'] ?? null;

    if (empty($accion) || empty($descripcion)) {
        throw new Exception('Acción y descripción son obligatorias');
    }

    $res = LogPDO::addLog($accion, $descripcion, $detalles);
    echo json_encode(['ok' => $res]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>
