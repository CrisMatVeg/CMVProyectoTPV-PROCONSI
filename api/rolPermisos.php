<?php

/**
 * API: rolPermisos.php
 * Devuelve las claves de permisos asignadas a un rol en formato JSON.
 */
session_start();
header('Content-Type: application/json');

// Seguridad mínima (autenticado)
if (!isset($_SESSION['usuarioActualTPV'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require_once '../model/DBPDO.php';
require_once '../model/RolPDO.php';

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
