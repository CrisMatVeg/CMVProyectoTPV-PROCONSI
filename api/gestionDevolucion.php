<?php
/**
 * API: gestionDevolucion.php
 * Permite devolver una línea específica o un ticket completo.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/ProductoPDO.php';

    session_start();
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? '';

    if ($accion === 'devolverLinea') {
        $idLinea = (int)($input['idLinea'] ?? 0);
        $res = VentaPDO::devolverLinea($idLinea);
        echo json_encode(['ok' => $res]);
    } else if ($accion === 'devolverTicket') {
        $numTicket = (int)($input['numTicket'] ?? 0);
        $res = VentaPDO::devolverVenta($numTicket);
        echo json_encode(['ok' => $res]);
    } else {
        throw new Exception('Acción no válida');
    }

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
