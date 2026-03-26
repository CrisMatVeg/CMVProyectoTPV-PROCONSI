<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: gestionExclusiones.php
 * Obtiene y gestiona exclusiones de productos en tarifas/promociones.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/PriceEngine.php';
    require_once __DIR__ . '/../model/TarifaPrecioPDO.php';
    require_once __DIR__ . '/../model/PromocionPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV']) || $_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        throw new Exception('No autorizado');
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $input['accion'] ?? '';

    switch ($accion) {
        case 'listar_aplicables':
            $idProducto = (int)($input['id_producto'] ?? 0);
            if (!$idProducto) throw new Exception('ID de producto inválido');
            $res = PriceEngine::getRulesForProduct($idProducto);
            echo json_encode(['ok' => true, 'reglas' => $res]);
            break;

        case 'excluir':
            $idProducto = (int)($input['id_producto'] ?? 0);
            $idRegla = (int)($input['id_regla'] ?? 0);
            $tipo = $input['tipo'] ?? ''; // 'tarifa' o 'promocion'
            if (!$idProducto || !$idRegla) throw new Exception('Parámetros inválidos');
            if ($tipo === 'tarifa') {
                TarifaPrecioPDO::excluirProducto($idRegla, $idProducto);
            } elseif ($tipo === 'promocion') {
                PromocionPDO::excluirProducto($idRegla, $idProducto);
            } else {
                throw new Exception('Tipo de regla desconocido');
            }
            echo json_encode(['ok' => true]);
            break;

        default:
            throw new Exception('Acción desconocida');
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
