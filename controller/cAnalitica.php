<?php

/**
 * Controller: cAnalitica.php
 * Procesa los datos de analítica de ventas para el administrador.
 */

// Solo usuarios autenticados
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

// Solo administradores
if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Navegación
if (isset($_REQUEST['volver']) || isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
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

if (isset($_REQUEST['irCierreCaja'])) {
    $_SESSION['paginaEnCurso'] = 'cierreCaja';
    header('Location: index.php');
    exit;
}

// Filtros de fecha (por defecto últimos 30 días)
$fechaDesdeRaw = $_REQUEST['fechaDesde'] ?? date('Y-m-d', strtotime('-30 days'));
$fechaHastaRaw = $_REQUEST['fechaHasta'] ?? date('Y-m-d');

$aErrores = [
    'fechaDesde' => validacionFormularios::validarFecha($fechaDesdeRaw, '2050-12-31', '2020-01-01', 0),
    'fechaHasta' => validacionFormularios::validarFecha($fechaHastaRaw, '2050-12-31', '2020-01-01', 0)
];

$fechaDesde = $aErrores['fechaDesde'] ? date('Y-m-d', strtotime('-30 days')) : $fechaDesdeRaw;
$fechaHasta = $aErrores['fechaHasta'] ? date('Y-m-d') : $fechaHastaRaw;

// Cargar modelos necesarios
require_once 'model/VentaPDO.php';

// Obtener datos para la vista
$avAnalitica = [
    'kpis' => VentaPDO::obtenerKPIs($fechaDesde, $fechaHasta),
    'porCategoria' => VentaPDO::obtenerVentasPorCategoria($fechaDesde, $fechaHasta),
    'topProductos' => VentaPDO::obtenerTopProductos($fechaDesde, $fechaHasta, 10),
    'rankingProductos' => VentaPDO::obtenerRankingCompletoProductos($fechaDesde, $fechaHasta),
    'margenes' => VentaPDO::obtenerMargenesDetallados($fechaDesde, $fechaHasta),
    'desgloseIva' => VentaPDO::obtenerDesgloseIVA($fechaDesde, $fechaHasta),
    'filtros' => [
        'desde' => $fechaDesde,
        'hasta' => $fechaHasta
    ],
    'aErrores' => $aErrores
];

// Cargar la vista
require_once $view['layout'];
