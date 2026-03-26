<?php
/**
 * Controlador: cTarifas
 * Pantalla de administración de tarifas de precios.
 */

if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

require_once 'model/TarifaPrecioPDO.php';
require_once 'model/ProductoPDO.php';
require_once 'model/ClientePDO.php';
require_once 'model/CategoriaPDO.php';
require_once 'model/RolClientePDO.php';

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

// Navegación de retorno a Dashboard
if (isset($_REQUEST['volver']) || isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

$avTarifas = [
    'lista'      => TarifaPrecioPDO::listarTodas(),
    'productos'  => ProductoPDO::listarProductos(false),
    'clientes'   => ClientePDO::listarTodos(),
    'categorias' => CategoriaPDO::listarTodas(),
    'roles'      => RolClientePDO::listarRoles(),
];

require_once $view['layout'];

