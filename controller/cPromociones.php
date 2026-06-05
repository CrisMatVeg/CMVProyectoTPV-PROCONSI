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

if (isset($_REQUEST['volver'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

require_once 'model/CategoriaPDO.php';

$avPromos = [
    'lista'      => PromocionPDO::listarTodas(),
    'categorias' => CategoriaPDO::listarTodas(),
];


require_once $view['layout'];
