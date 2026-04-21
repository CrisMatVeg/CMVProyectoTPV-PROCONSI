<?php
require_once __DIR__ . '/csrf_check.php';

require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/ProductoPDO.php';
require_once __DIR__ . '/../model/Usuario.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['usuarioActualTPV']) || $_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        throw new Exception('No autorizado', 401);
    }

    $historial = ProductoPDO::listarLogAjustesGlobales();

    echo json_encode([
        'ok' => true,
        'logs' => $historial
    ]);

} catch (Throwable $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
