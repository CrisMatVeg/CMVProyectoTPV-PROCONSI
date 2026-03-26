<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: gestionTipoIva.php
 * CRUD de tipos de IVA.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/TipoIVAPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV']) || $_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $input['accion'] ?? '';

    switch ($accion) {
        case 'listar':
            $data = TipoIVAPDO::listarTodos();
            echo json_encode(['ok' => true, 'lista' => $data]);
            break;

        case 'añadir':
            $errores = validarTipoIva($input);
            if (!$errores) {
                $overlap = TipoIVAPDO::checkOverlappingDates($input['codigo'], $input['fecha_inicio'], $input['fecha_fin']);
                if ($overlap) {
                    $errores['fecha_inicio'] = "Este rango solapa con '{$overlap['nombre']}' ({$overlap['fecha_inicio']} a " . ($overlap['fecha_fin'] ?? 'siempre') . ")";
                }
            }
            if ($errores) {
                echo json_encode(['ok' => false, 'aErrores' => $errores]);
                break;
            }
            TipoIVAPDO::añadir($input);
            echo json_encode(['ok' => true]);
            break;

        case 'editar':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de tipo de IVA inválido');
            }
            $errores = validarTipoIva($input);
            if (!$errores) {
                $overlap = TipoIVAPDO::checkOverlappingDates($input['codigo'], $input['fecha_inicio'], $input['fecha_fin'], $id);
                if ($overlap) {
                    $errores['fecha_inicio'] = "Este rango solapa con '{$overlap['nombre']}' ({$overlap['fecha_inicio']} a " . ($overlap['fecha_fin'] ?? 'siempre') . ")";
                }
            }
            if ($errores) {
                echo json_encode(['ok' => false, 'aErrores' => $errores]);
                break;
            }
            TipoIVAPDO::editar($id, $input);

            $hoy    = date('Y-m-d');
            $codigo = $input['codigo'];
            $porc   = (float)$input['porcentaje'];
            $fi     = $input['fecha_inicio'];
            $ff     = $input['fecha_fin'] ?: null;
            $activo = !empty($input['activo']);

            if ($activo && $fi <= $hoy && (is_null($ff) || $ff >= $hoy)) {
                DBPDO::ejecutarConsulta(
                    "UPDATE productos SET iva = :iva WHERE codigo_iva = :codigo",
                    [':iva' => $porc, ':codigo' => $codigo]
                );
            }

            echo json_encode(['ok' => true]);
            break;

        case 'eliminar':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de tipo de IVA inválido');
            }
            TipoIVAPDO::eliminar($id);
            echo json_encode(['ok' => true]);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

function validarTipoIva(array $d): array {
    $err = ['codigo' => '', 'nombre' => '', 'porcentaje' => '', 'fecha_inicio' => ''];
    if (empty($d['codigo'])) $err['codigo'] = 'El código es obligatorio';
    if (empty($d['nombre'])) $err['nombre'] = 'El nombre es obligatorio';
    if (!isset($d['porcentaje']) || !is_numeric($d['porcentaje'])) $err['porcentaje'] = 'Porcentaje inválido';
    if (empty($d['fecha_inicio'])) $err['fecha_inicio'] = 'La fecha de inicio es obligatoria';
    elseif (!empty($d['fecha_fin']) && $d['fecha_fin'] < $d['fecha_inicio']) $err['fecha_fin'] = 'La fecha de fin no puede ser anterior a la de inicio';
    return array_filter($err, fn($v) => $v !== '');
}
