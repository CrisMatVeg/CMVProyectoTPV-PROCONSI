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

if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_tarifas')) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

require_once 'model/TarifaPrecioPDO.php';
require_once 'model/ProductoPDO.php';
require_once 'model/ClientePDO.php';
require_once 'model/CategoriaPDO.php';
require_once 'model/RolClientePDO.php';

if (isset($_REQUEST['volver'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

$avTarifas = [
    'lista'      => TarifaPrecioPDO::listarTodas(),
    'productos'  => ProductoPDO::listarProductos(false),
    'clientes'   => ClientePDO::listarTodos(500),
    'categorias' => CategoriaPDO::listarTodas(),
    'roles'      => RolClientePDO::listarRoles(),
];

require_once $view['layout'];

