<?php
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

    session_start();
    if (!isset($_SESSION['usuarioActualTPV']) || $_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        http_response_code(401);
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
            TarifaPrecioPDO::añadir($input, $_SESSION['usuarioActualTPV']->getId());
            echo json_encode(['ok' => true]);
            break;

        case 'aplicar':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de tarifa inválido');
            }
            TarifaPrecioPDO::aplicar($id);
            echo json_encode(['ok' => true]);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

function validarTarifa(array $d): array {
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
    if (!in_array($d['tipo'] ?? '', ['percent','amount'], true)) {
        $err['tipo'] = 'Tipo no válido';
    }
    if (!isset($d['valor']) || !is_numeric($d['valor'])) {
        $err['valor'] = 'Valor inválido';
    }
    if (empty($d['fecha_aplicacion'])) {
        $err['fecha_aplicacion'] = 'La fecha de aplicación es obligatoria';
    }

    $scope = $d['scope'] ?? 'todos';
    if (!in_array($scope, ['todos','categoria','productos'], true)) {
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

