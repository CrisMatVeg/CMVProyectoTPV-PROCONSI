<?php
/**
 * Controlador: cTiposIVA
 * Pantalla de administración de tipos de IVA.
 */

if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_iva')) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

require_once 'model/TipoIVAPDO.php';

if (isset($_REQUEST['volver'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

$avTiposIva = [
    'lista' => TipoIVAPDO::listarTodos(),
];

require_once $view['layout'];

