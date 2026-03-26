<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: guardarTema.php
 * Guarda las preferencias de tema (modo + acento) para el usuario actual.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $idUsuario = $_SESSION['usuarioActualTPV']->getId();

    $campos = [];
    $params = [':id' => $idUsuario];

    if (isset($input['theme_mode'])) {
        $mode = $input['theme_mode'];
        $validModes = ['light','dark','black'];
        if (in_array($mode, $validModes, true)) {
            $campos[] = "theme_mode = :mode";
            $params[':mode'] = $mode;
        }
    }

    if (isset($input['theme_accent'])) {
        $accent = $input['theme_accent'];
        $validAccents = ['blue','green','red','purple','amber'];
        if (in_array($accent, $validAccents, true)) {
            $campos[] = "theme_accent = :accent";
            $params[':accent'] = $accent;
        }
    }

    if ($campos) {
        $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, $params);
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
