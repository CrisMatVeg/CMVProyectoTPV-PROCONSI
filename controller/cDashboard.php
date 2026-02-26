<?php
/**
 * Controlador: cDashboard
 * 
 * Pantalla intermedia tras el login para elegir entre TPV o Gestión.
 */

// Si se pulsa salir, cerramos sesión
if (isset($_REQUEST['salir'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Navegación
if (isset($_REQUEST['irTPV'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPrivado';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irUsuarios'])) {
    $_SESSION['paginaEnCurso'] = 'Usuarios';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irProductos'])) {
    $_SESSION['paginaEnCurso'] = 'Productos';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irHistorial'])) {
    $_SESSION['paginaEnCurso'] = 'Historial';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irMiPerfil'])) {
    $_SESSION['paginaEnCurso'] = 'MiPerfil';
    header('Location: index.php');
    exit;
}

// Cargamos la vista de dashboard
require_once $view['layout'];
?>
