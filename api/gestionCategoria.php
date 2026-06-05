<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: gestionCategoria.php
 * Maneja altas y bajas de categorías vía AJAX.
 */
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/CategoriaPDO.php';
    require_once __DIR__ . '/../model/LogPDO.php';

    // session_start(); // Handled by csrf_check.php

    // Solo administradores
    if (!isset($_SESSION['usuarioActualTPV']) || !$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_productos')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No autorizado para gestionar categorías']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? '';

    switch ($accion) {
        case 'añadir':
            $codigo = $input['codigo'] ?? '';
            $nombre = $input['nombre'] ?? '';
            if (empty($codigo) || empty($nombre)) {
                echo json_encode(['ok' => false, 'error' => 'Código y nombre son obligatorios']);
                break;
            }
            $res = CategoriaPDO::agregar($codigo, $nombre);
            if ($res) {
                $newId = (int)DBPDO::ejecutarConsulta("SELECT LAST_INSERT_ID()")->fetchColumn();
                LogPDO::addLog('CREATE_CATEGORIA', "Categoría creada: {$nombre} ({$codigo})", [
                    'id' => $newId, 'codigo' => $codigo, 'nombre' => $nombre,
                ]);
            }
            echo json_encode(['ok' => $res]);
            break;

        case 'eliminar':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['ok' => false, 'error' => 'ID inválido']);
                break;
            }
            $res = CategoriaPDO::eliminar($id);
            if ($res) {
                LogPDO::addLog('DELETE_CATEGORIA', "Categoría #{$id} eliminada", ['id' => $id]);
            }
            echo json_encode(['ok' => $res]);
            break;

        case 'asignarIVA':
            $idCategoria = (int)($input['idCategoria'] ?? 0);
            $idTipoIva = (int)($input['idTipoIva'] ?? 0);
            if ($idCategoria <= 0 || $idTipoIva <= 0) {
                echo json_encode(['ok' => false, 'error' => 'ID de categoría o IVA inválido']);
                break;
            }
            $res = CategoriaPDO::asignarIVA($idCategoria, $idTipoIva);
            if ($res) {
                LogPDO::addLog('ASIGNAR_IVA_CATEGORIA', "IVA #{$idTipoIva} asignado a categoría #{$idCategoria}", [
                    'id_categoria' => $idCategoria, 'id_tipo_iva' => $idTipoIva,
                ]);
            }
            echo json_encode(['ok' => $res]);
            break;

        case 'editar':
            $id = (int)($input['id'] ?? 0);
            $codigo = $input['codigo'] ?? '';
            $nombre = $input['nombre'] ?? '';
            if ($id <= 0 || empty($codigo) || empty($nombre)) {
                echo json_encode(['ok' => false, 'error' => 'ID, código y nombre son obligatorios']);
                break;
            }
            $res = CategoriaPDO::editar($id, $codigo, $nombre);
            if ($res) {
                LogPDO::addLog('UPDATE_CATEGORIA', "Categoría #{$id} actualizada: {$nombre} ({$codigo})", [
                    'id' => $id, 'codigo' => $codigo, 'nombre' => $nombre,
                ]);
            }
            echo json_encode(['ok' => $res]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción desconocida']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
