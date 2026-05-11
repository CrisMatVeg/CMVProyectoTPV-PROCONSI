<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: gestionPromocion.php
 * CRUD de promociones de descuento.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/PromocionPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV']) || !$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_promociones')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $input['accion'] ?? '';

    switch ($accion) {
        case 'listar':
            $data = PromocionPDO::listarTodas();
            echo json_encode(['ok' => true, 'lista' => $data]);
            break;

        case 'añadir':
            $errores = validarPromocion($input);
            if ($errores) {
                echo json_encode(['ok' => false, 'aErrores' => $errores]);
                break;
            }
            PromocionPDO::añadir($input);
            echo json_encode(['ok' => true]);
            break;

        case 'editar':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de promoción inválido');
            }
            $errores = validarPromocion($input);
            if ($errores) {
                echo json_encode(['ok' => false, 'aErrores' => $errores]);
                break;
            }
            PromocionPDO::editar($id, $input);
            echo json_encode(['ok' => true]);
            break;

        case 'eliminar':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de promoción inválido');
            }
            PromocionPDO::eliminar($id);
            echo json_encode(['ok' => true]);
            break;

        case 'toggle':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de promoción inválido');
            }
            $activo = PromocionPDO::toggleActivo($id);
            echo json_encode(['ok' => true, 'activo' => $activo]);
            break;

        case 'reordenar':
            $ids = $input['ids'] ?? [];
            if (!is_array($ids)) {
                throw new Exception('Lista de IDs inválida');
            }
            PromocionPDO::actualizarOrden($ids);
            echo json_encode(['ok' => true]);
            break;

        case 'buscarProductos':
            require_once __DIR__ . '/../model/ProductoPDO.php';
            $term = trim($input['term'] ?? '');
            $productos = ProductoPDO::listarProductos(false, 100, 0, $term);
            $result = [];
            foreach ($productos as $p) {
                $result[] = [
                    'id'         => (int)$p->getId(),
                    'nombre'     => $p->getNombre(),
                    'referencia' => $p->getReferencia(),
                    'precio'     => (float)$p->getPrecioVenta(),
                ];
            }
            echo json_encode(['ok' => true, 'productos' => $result]);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

function validarPromocion(array $d): array {
    $err = [];
    $tipo = $d['tipo'] ?? '';
    if (empty($d['descripcion'])) $err['descripcion'] = 'La descripción es obligatoria';
    if (!in_array($tipo, ['percent', 'amount', 'bundle', 'fixed_bundle'], true)) $err['tipo'] = 'Tipo no válido';
    if ($tipo === 'percent' || $tipo === 'amount' || $tipo === 'fixed_bundle') {
        $val = isset($d['valor']) ? (float)$d['valor'] : 0;
        if (!is_numeric($d['valor'] ?? null) || $val <= 0) {
            $err['valor'] = 'El valor debe ser mayor que 0';
        } elseif ($tipo === 'percent' && $val > 100) {
            $err['valor'] = 'El porcentaje no puede ser superior al 100%';
        }
    }
    if ($tipo === 'bundle' || $tipo === 'fixed_bundle') {
        if (empty($d['bundle_buy_qty']) || !is_numeric($d['bundle_buy_qty']) || (int)$d['bundle_buy_qty'] <= 1) $err['bundle_buy_qty'] = 'Cantidad de compra inválida (mín. 2)';
        if ($tipo === 'bundle' && (empty($d['bundle_pay_qty']) || !is_numeric($d['bundle_pay_qty']) || (int)$d['bundle_pay_qty'] < 1)) $err['bundle_pay_qty'] = 'Cantidad de pago inválida';
    }
    return $err;
}
