<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: cajaInfoCierre.php
 * Devuelve retiradas y deudas asociadas a un cierre fiscal concreto.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/CajaTurnoPDO.php';
    require_once __DIR__ . '/../model/CajaDeudaPDO.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $idCierre = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($idCierre <= 0) {
        throw new Exception('ID de cierre inválido');
    }

    // Buscar turnos asociados a este cierre mediante la columna num_z
    $turnos = CajaTurnoPDO::listarTurnosPorCierre($idCierre);

    // Cargar todos los movimientos en una sola query (evita N+1)
    $idsTurnos = array_column($turnos, 'id');
    $movimientosPorTurno = CajaTurnoPDO::listarMovimientosPorTurnos($idsTurnos);

    $idsVistos = [];
    $retiros   = [];
    foreach ($turnos as &$t) {
        $t['movimientos'] = $movimientosPorTurno[(int)$t['id']] ?? [];
        foreach ($t['movimientos'] as $m) {
            if (!in_array($m['id'], $idsVistos)) {
                $retiros[]   = $m;
                $idsVistos[] = $m['id'];
            }
        }
    }
    unset($t);

    $deudas = CajaDeudaPDO::listarPorCierre($idCierre);

    echo json_encode([
        'ok'      => true,
        'turnos'  => $turnos,
        'retiros' => $retiros,
        'deudas'  => $deudas
    ]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
