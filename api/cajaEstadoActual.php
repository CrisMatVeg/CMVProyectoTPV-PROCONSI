<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: cajaEstadoActual.php
 * Devuelve el efectivo que debería haber en el cajón en este momento.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/CajaTurnoPDO.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $efectivoActual = CajaTurnoPDO::obtenerEfectivoActual();

    echo json_encode([
        'ok' => true,
        'efectivoActual' => $efectivoActual
    ]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
