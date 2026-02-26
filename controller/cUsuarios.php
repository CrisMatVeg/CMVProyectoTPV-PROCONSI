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

// Volver al dashboard
if (isset($_REQUEST['volver'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Acción: Añadir Usuario
if (isset($_REQUEST['addUsuario'])) {
    $nombre = $_REQUEST['nombre'] ?? '';
    $user = $_REQUEST['username'] ?? '';
    $pass = $_REQUEST['password'] ?? '';
    $rol = $_REQUEST['rol'] ?? 'cajero';

    if (!empty($nombre) && !empty($user) && !empty($pass)) {
        UsuarioPDO::añadirUsuario($nombre, $user, $pass, $rol);
        header('Location: index.php'); // Recargar para ver cambios
        exit;
    }
}

// Acción: Cambiar Rol
if (isset($_REQUEST['cambiarRol'])) {
    $id = $_REQUEST['idUsuario'];
    $nuevoRol = $_REQUEST['nuevoRol'];
    UsuarioPDO::editarRol($id, $nuevoRol);
    header('Location: index.php');
    exit;
}

// Acción: Toggle Estado (Baja/Alta)
if (isset($_REQUEST['toggleEstado'])) {
    $id = $_REQUEST['idUsuario'];
    UsuarioPDO::toggleEstatus($id);
    header('Location: index.php');
    exit;
}

// Cargar la lista de usuarios para la vista
$listaUsuarios = UsuarioPDO::listarUsuarios();

require_once $view['layout'];
?>
