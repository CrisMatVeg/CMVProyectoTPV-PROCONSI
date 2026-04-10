<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: gestionExclusiones.php
 * Obtiene y gestiona exclusiones de productos en tarifas/promociones.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
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
            $engineResult = PriceEngine::getRulesForProduct($idProducto);
            echo json_encode([
                'ok' => true, 
                'reglas' => $engineResult['reglas'],
                'debug_stats' => $engineResult['debug_stats']
            ]);
            break;

        case 'excluir':
            $idProducto = (int)($input['id_producto'] ?? 0);
            $idRegla = (int)($input['id_regla'] ?? 0);
            $tipo = $input['tipo'] ?? ''; // 'tarifa' o 'promocion'
            if (!$idProducto || !$idRegla) throw new Exception('Parámetros inválidos');
            if (($input['tipo'] ?? '') === 'tarifa') {
                $idTarifa = (int)($input['id_regla'] ?? 0);
                if ($idTarifa > 0) {
                    $tarifa = TarifaPrecioPDO::obtenerPorId($idTarifa);
                    if ($tarifa && isset($tarifa['aplicada']) && $tarifa['aplicada']) {
                         throw new Exception('No se puede excluir un producto de una tarifa que ya ha sido aplicada permanentemente');
                    }
                    TarifaPrecioPDO::excluirProducto($idTarifa, $idProducto);
                }
            } elseif (($input['tipo'] ?? '') === 'promocion') {
                PromocionPDO::excluirProducto((int)$input['id_regla'], $idProducto);
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
