<?php
require_once("./config/confAPP.php");
require_once("./config/confDBPDO.php");
session_start();

if (!isset($_SESSION['paginaEnCurso'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPublico';
}

// NAVIGATION: Handle global navigation requests before loading controllers
if (isset($_SESSION['usuarioActualTPV'])) {
    if (isset($_REQUEST['salir'])) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irDashboard'])) {
        $_SESSION['paginaEnCurso'] = 'Dashboard';
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irTPV']) && !isset($_POST['abrirCaja'])) {
        $_SESSION['paginaEnCurso'] = 'inicioPrivado';
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irMiPerfil'])) {
        $_SESSION['paginaEnCurso'] = 'MiPerfil';
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irCierreCaja']) && $_SESSION['usuarioActualTPV']->getRol() === 'admin' && !isset($_POST['doCierre']) && !isset($_POST['abrirCaja'])) {
        $_SESSION['paginaEnCurso'] = 'cierreCaja';
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irProveedores']) && $_SESSION['usuarioActualTPV']->getRol() === 'admin') {
        $_SESSION['paginaEnCurso'] = 'Proveedores';
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irCompras']) && $_SESSION['usuarioActualTPV']->getRol() === 'admin') {
        $_SESSION['paginaEnCurso'] = 'Compras';
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irConfiguracion']) && $_SESSION['usuarioActualTPV']->getRol() === 'admin') {
        $_SESSION['paginaEnCurso'] = 'Configuracion';
        header('Location: index.php');
        exit;
    }
}

require_once($controller[$_SESSION['paginaEnCurso']]);
