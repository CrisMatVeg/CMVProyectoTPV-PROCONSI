<?php

/**
 * Controlador: cUsuarios
 * 
 * Gestiona el listado y acciones de administración de personal.
 */

// Si no es admin, fuera
if (!isset($_SESSION['usuarioActualTPV']) || !$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_usuarios')) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Navegación Global
if (isset($_REQUEST['salir'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irTPV'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPrivado';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irCierreCaja'])) {
    $_SESSION['paginaEnCurso'] = 'cierreCaja';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irMiPerfil'])) {
    $_SESSION['paginaEnCurso'] = 'MiPerfil';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['volver']) || isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irRoles'])) {
    $_SESSION['paginaEnCurso'] = 'Roles';
    header('Location: index.php');
    exit;
}

$aErrores = [
    'nombre' => null,
    'login' => null,
    'password' => null,
    'email' => null
];
$entradaOK = true;
// Obtener lista de roles para los formularios
$listaRoles = RolPDO::listarRoles();

// Cargar la lista de usuarios para la vista
$listaUsuarios = UsuarioPDO::listarUsuarios();

// Acción: Añadir Usuario
if (isset($_REQUEST['addUsuario'])) {
    $showModal = true;

    $aErrores['nombre'] = validacionFormularios::comprobarAlfabetico($_REQUEST['nombre'] ?? '', 100, 3, 1);
    $aErrores['login'] = validacionFormularios::comprobarAlfaNumerico($_REQUEST['username'] ?? '', 15, 4, 1);
    $aErrores['password'] = validacionFormularios::validarPassword($_REQUEST['password'] ?? '', 20, 4, 1, 1);
    $aErrores['email'] = !empty($_REQUEST['email']) ? validacionFormularios::validarEmail($_REQUEST['email']) : null;

    foreach ($aErrores as $e) {
        if ($e != null) $entradaOK = false;
    }

    if ($entradaOK) {
        $nombre = $_REQUEST['nombre'];
        $login = $_REQUEST['username'];
        $pass = $_REQUEST['password'];
        $idRol = (int)($_REQUEST['idRol'] ?? 0);
        $email = $_REQUEST['email'] ?? null;

        // Buscar el nombre del rol para el campo legacy 'rol'
        $nombreRol = 'cajero';
        foreach ($listaRoles as $r) {
            if ($r['id'] == $idRol) {
                $nombreRol = strtolower($r['nombre']);
                break;
            }
        }

        UsuarioPDO::añadirUsuario($nombre, $login, $pass, $nombreRol, $idRol, $email);
        header('Location: index.php?irUsuarios=1'); // Recargar para ver cambios
        exit;
    }
}

// Acción: Editar Usuario (Nombre y Email)
if (isset($_REQUEST['editUsuario'])) {
    $id = (int)$_REQUEST['idUsuario'];
    $nombre = $_REQUEST['nombre_edit'] ?? '';
    $email = $_REQUEST['email_edit'] ?? '';

    if (!empty($nombre)) {
        UsuarioPDO::editarUsuario($id, $nombre, $email);
    }
    header('Location: index.php?irUsuarios=1');
    exit;
}

// Acción: Cambiar Rol
if (isset($_REQUEST['cambiarRol'])) {
    $id = (int)$_REQUEST['idUsuario'];
    $idRol = (int)$_REQUEST['idRol'];

    // Buscar el nombre del rol
    $nombreRol = 'cajero';
    foreach ($listaRoles as $r) {
        if ($r['id'] == $idRol) {
            $nombreRol = strtolower($r['nombre']);
            break;
        }
    }

    // No permitir que un usuario se cambie el rol a sí mismo
    if ($id !== $_SESSION['usuarioActualTPV']->getId()) {
        UsuarioPDO::editarRol($id, $nombreRol, $idRol);
    }
    header('Location: index.php');
    exit;
}

// Acción: Toggle Estado (Baja/Alta)
if (isset($_REQUEST['toggleEstado'])) {
    $id = (int)$_REQUEST['idUsuario'];
    // No permitir que un usuario se dé de baja a sí mismo
    if ($id !== $_SESSION['usuarioActualTPV']->getId()) {
        UsuarioPDO::toggleEstatus($id);
    }
    header('Location: index.php');
    exit;
}

// Cargar la lista de usuarios para la vista
$listaUsuarios = UsuarioPDO::listarUsuarios();

require_once $view['layout'];
