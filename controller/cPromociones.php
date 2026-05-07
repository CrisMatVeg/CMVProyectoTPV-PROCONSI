<?php

/**
 * Controlador: cPromociones
 * Pantalla de administración de descuentos y promociones.
 */

if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_promociones')) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

require_once 'model/PromocionPDO.php';

// Navegación global
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

if (isset($_REQUEST['irMiPerfil'])) {
    $_SESSION['paginaEnCurso'] = 'MiPerfil';
    header('Location: index.php');
    exit;
}

// Navegación básica (volver al Dashboard)
if (isset($_REQUEST['volver']) || isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

require_once 'model/ProductoPDO.php';
require_once 'model/CategoriaPDO.php';

$avPromos = [
    'lista' => PromocionPDO::listarTodas(),
    'productos' => ProductoPDO::listarProductos(false),
    'categorias' => CategoriaPDO::listarTodas(),
];


require_once $view['layout'];
