<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: gestionTarifa.php
 * Alta y aplicación de tarifas de precios globales.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/TarifaPrecioPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV']) || !$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_tarifas')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $input['accion'] ?? '';

    switch ($accion) {
        case 'listar':
            $lista = TarifaPrecioPDO::listarTodas();
            echo json_encode(['ok' => true, 'lista' => $lista]);
            break;

        case 'añadir':
            $err = validarTarifa($input);
            if ($err) {
                echo json_encode(['ok' => false, 'aErrores' => $err]);
                break;
            }
            // Validar que sea condicional
            if (!esTarifaCondicional($input)) {
                echo json_encode(['ok' => false, 'error' => 'Las tarifas deben ser condicionales (filtros de cliente, fechas o días). Para cambios generales permanentes usa el Ajuste Masivo de Precios.']);
                break;
            }
            TarifaPrecioPDO::agregar($input, $_SESSION['usuarioActualTPV']->getId());
            echo json_encode(['ok' => true]);
            break;

        case 'editar':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID de tarifa inválido');
            $err = validarTarifa($input);
            if ($err) {
                echo json_encode(['ok' => false, 'aErrores' => $err]);
                break;
            }
            if (!esTarifaCondicional($input)) {
                echo json_encode(['ok' => false, 'error' => 'La tarifa debe tener al menos un filtro condicional.']);
                break;
            }
            TarifaPrecioPDO::editar($id, $input);
            echo json_encode(['ok' => true]);
            break;

        case 'eliminar':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID de tarifa inválido');
            $tarifa = TarifaPrecioPDO::obtenerPorId($id);
            if ($tarifa && $tarifa['aplicada']) throw new Exception('No se puede eliminar una tarifa que ya ha sido aplicada permanentemente');
            TarifaPrecioPDO::eliminar($id);
            echo json_encode(['ok' => true]);
            break;

        case 'toggle':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID de tarifa inválido');
            $activo = TarifaPrecioPDO::toggleActivo($id);
            echo json_encode(['ok' => true, 'activo' => $activo]);
            break;


        case 'reordenar':
            $ids = $input['ids'] ?? [];
            if (!is_array($ids)) {
                throw new Exception('Lista de IDs inválida');
            }
            TarifaPrecioPDO::actualizarOrden($ids);
            echo json_encode(['ok' => true]);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

function validarTarifa(array $d): array
{
    $err = [
        'nombre' => '',
        'tipo' => '',
        'valor' => '',
        'fecha_aplicacion' => '',
        'categoria' => '',
        'producto_ids' => '',
    ];

    if (empty($d['nombre'])) {
        $err['nombre'] = 'El nombre es obligatorio';
    }
    if (!in_array($d['tipo'] ?? '', ['percent', 'amount'], true)) {
        $err['tipo'] = 'Tipo no válido';
    }
    $val = isset($d['valor']) ? (float)$d['valor'] : 0;
    if (!is_numeric($d['valor'] ?? null) || $val <= 0) {
        $err['valor'] = 'El valor debe ser mayor que 0';
    } elseif (($d['tipo'] ?? '') === 'percent' && $val > 100) {
        $err['valor'] = 'El porcentaje no puede ser superior al 100%';
    }

    $scope = $d['scope'] ?? 'todos';
    if (!in_array($scope, ['todos', 'categoria', 'productos'], true)) {
        $scope = 'todos';
    }
    if ($scope === 'categoria' && empty($d['categoria'])) {
        $err['categoria'] = 'Debes seleccionar una categoría';
    }
    if ($scope === 'productos') {
        if (empty($d['producto_ids']) || !is_array($d['producto_ids'])) {
            $err['producto_ids'] = 'Debes seleccionar al menos un producto';
        }
    }

    return array_filter($err, fn($v) => $v !== '');
}

function esTarifaCondicional(array $d): bool
{
    return (!empty($d['tipo_cliente']) || 
            !empty($d['roles_segmento']) || 
            !empty($d['cliente_ids']) || 
            !empty($d['id_cliente']) || 
            !empty($d['dias_semana']) || 
            !empty($d['hora_inicio']) || 
            !empty($d['hora_fin']) || 
            !empty($d['fecha_aplicacion']) || 
            !empty($d['fecha_fin']));
}
