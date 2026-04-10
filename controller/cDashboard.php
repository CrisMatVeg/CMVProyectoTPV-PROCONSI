<?php
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

/**
 * Controlador: cDashboard
 * 
 * Pantalla intermedia tras el login para elegir entre TPV o Gestión.
 */

// Navegación Global handled by index.php
require_once 'model/CajaTurnoPDO.php';
$turnoCaja = CajaTurnoPDO::obtenerTurnoAbierto();

if (isset($_REQUEST['irUsuarios'])) {
    $_SESSION['paginaEnCurso'] = 'Usuarios';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irClientes'])) {
    $_SESSION['paginaEnCurso'] = 'Clientes';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irProductos'])) {
    $_SESSION['paginaEnCurso'] = 'Productos';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irTiposIVA'])) {
    $_SESSION['paginaEnCurso'] = 'TiposIVA';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irTarifas'])) {
    $_SESSION['paginaEnCurso'] = 'Tarifas';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irPromociones'])) {
    $_SESSION['paginaEnCurso'] = 'Promociones';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irHistorial'])) {
    $_SESSION['paginaEnCurso'] = 'Historial';
    header('Location: index.php');
    exit;
}

// irMiPerfil handled by index.php

if (isset($_REQUEST['Analitica'])) {
    $_SESSION['paginaEnCurso'] = 'Analitica';
    header('Location: index.php');
    exit;
}

// Datos de analítica para el admin
$avDashboard = [
    'kpis' => null,
    'metodos' => [],
    'productos_count' => 0
];

if ($_SESSION['usuarioActualTPV']->getRol() === 'admin') {
    require_once 'model/VentaPDO.php';
    require_once 'model/ProductoPDO.php';

    $desde = date('Y-m-d', strtotime('-7 days'));
    $hasta = date('Y-m-d');

    $avDashboard['kpis'] = VentaPDO::obtenerKPIs($desde, $hasta);
    $avDashboard['metodos'] = VentaPDO::obtenerVentasPorMetodo($desde, $hasta);
    $avDashboard['cajeros'] = VentaPDO::obtenerVentasPorCajero($desde, $hasta);
    $avDashboard['productos_count'] = ProductoPDO::contarProductos(true);
    $avDashboard['bajo_stock_count'] = ProductoPDO::contarBajoStock();
    $avDashboard['cajaAbierta'] = (bool)$turnoCaja;
}

// Cargamos la vista de dashboard
require_once $view['layout'];
