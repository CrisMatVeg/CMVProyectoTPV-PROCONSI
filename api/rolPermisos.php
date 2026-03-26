<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: rolPermisos.php
 * Devuelve las claves de permisos asignadas a un rol en formato JSON.
 */
// session_start(); // csrf_check already starts session
header('Content-Type: application/json');

// Seguridad mínima (autenticado)
if (!isset($_SESSION['usuarioActualTPV'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/RolPDO.php';

$idRol = isset($_GET['idRol']) ? (int)$_GET['idRol'] : null;

if (!$idRol) {
    echo json_encode(['ok' => false, 'error' => 'ID de rol no proporcionado']);
    exit;
}

$claves = RolPDO::obtenerPermisosRol($idRol);

echo json_encode([
    'ok' => true,
    'idRol' => $idRol,
    'claves' => $claves
]);
