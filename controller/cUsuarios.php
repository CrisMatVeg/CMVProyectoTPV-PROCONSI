<?php
/**
 * Controlador: cUsuarios
 * 
 * Gestiona el listado y acciones de administración de personal.
 */

// Si no es admin, fuera
if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
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

$aErrores = [
    'nombre' => null,
    'login' => null,
    'password' => null
];
$entradaOK = true;
$showModal = false;

// Acción: Añadir Usuario
if (isset($_REQUEST['addUsuario'])) {
    $showModal = true;
    
    $aErrores['nombre'] = validacionFormularios::comprobarAlfabetico($_REQUEST['nombre'] ?? '', 100, 3, 1);
    $aErrores['login'] = validacionFormularios::comprobarAlfaNumerico($_REQUEST['username'] ?? '', 15, 4, 1);
    $aErrores['password'] = validacionFormularios::validarPassword($_REQUEST['password'] ?? '', 20, 4, 1, 1);

    foreach ($aErrores as $e) {
        if ($e != null) $entradaOK = false;
    }

    if ($entradaOK) {
        $nombre = $_REQUEST['nombre'];
        $login = $_REQUEST['username'];
        $pass = $_REQUEST['password'];
        $rol = $_REQUEST['rol'] ?? 'cajero';

        UsuarioPDO::añadirUsuario($nombre, $login, $pass, $rol);
        header('Location: index.php'); // Recargar para ver cambios
        exit;
    }
}

// Acción: Cambiar Rol
if (isset($_REQUEST['cambiarRol'])) {
    $id = (int)$_REQUEST['idUsuario'];
    $nuevoRol = $_REQUEST['nuevoRol'];
    // No permitir que un usuario se cambie el rol a sí mismo
    if ($id !== $_SESSION['usuarioActualTPV']->getId()) {
        UsuarioPDO::editarRol($id, $nuevoRol);
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
?>
