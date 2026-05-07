<?php

/**
 * Controller: cRoles.php
 * Gestiona el mantenimiento de roles y permisos.
 */

// Solo usuarios autenticados
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

// Solo administradores o gestores de personal
if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_usuarios')) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Navegación
if (isset($_REQUEST['irUsuarios'])) {
    $_SESSION['paginaEnCurso'] = 'Usuarios';
    header('Location: index.php');
    exit;
}
if (isset($_REQUEST['volver']) || isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Procesar Acciones
$mensajeOk = null;
$error = null;

if (isset($_REQUEST['accion'])) {
    if ($_REQUEST['accion'] === 'guardarRol') {
        $nombre = $_REQUEST['nombreRol'] ?? '';
        $desc = $_REQUEST['descRol'] ?? '';
        $permisosIds = $_REQUEST['permisos'] ?? [];

        if (!empty($nombre)) {
            $idRol = (int)($_REQUEST['idRol'] ?? 0);
            if ($idRol > 0) {
                // Editar (Por ahora el modelo simplificado solo añade y asigna permisos)
                // Podríamos añadir editarRol en RolPDO
                $sql = "UPDATE roles SET nombre = :nom, descripcion = :des WHERE id = :id";
                DBPDO::ejecutarConsulta($sql, [':nom' => $nombre, ':des' => $desc, ':id' => $idRol]);
            } else {
                $idRol = RolPDO::añadirRol($nombre, $desc);
            }

            error_log("GUARDANDO ROL ID: " . $idRol . " CON PERMISOS: " . print_r($permisosIds, true));
            RolPDO::asignarPermisos($idRol, $permisosIds);
            $mensajeOk = "Rol guardado correctamente.";
        } else {
            $error = "El nombre del rol es obligatorio.";
        }
    }
}

// Obtener datos para la vista
$avRoles = [
    'roles' => RolPDO::listarRoles(),
    'permisos' => RolPDO::listarPermisos(),
    'mensajeOk' => $mensajeOk,
    'error' => $error
];

// Cargar la vista
require_once $view['layout'];
