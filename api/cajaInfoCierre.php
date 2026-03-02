<?php
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

    session_start();
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $idCierre = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($idCierre <= 0) {
        throw new Exception('ID de cierre inválido');
    }

    // Buscar turnos asociados a este cierre
    $sqlTurnos = "SELECT * FROM caja_turnos WHERE fecha_cierre IS NOT NULL AND fecha_cierre >= (
                      SELECT fecha FROM cierres_fiscales WHERE id = :id
                  ) AND fecha_cierre <= (
                      SELECT fecha FROM cierres_fiscales WHERE id = :id
                  )";
    $qT = DBPDO::ejecutarConsulta($sqlTurnos, [':id' => $idCierre]);
    $turnos = $qT->fetchAll(PDO::FETCH_ASSOC);

    $retiros = [];
    foreach ($turnos as $t) {
        $movs = CajaTurnoPDO::listarRetiros((int)$t['id']);
        foreach ($movs as $m) {
            $retiros[] = $m;
        }
    }

    $deudas = CajaDeudaPDO::listarPorCierre($idCierre);

    echo json_encode(['ok' => true, 'retiros' => $retiros, 'deudas' => $deudas]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

