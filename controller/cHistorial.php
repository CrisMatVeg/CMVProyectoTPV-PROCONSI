<?php

/**
 * Controller: cHistorial.php
 * Gestiona el historial de ventas con filtros.
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

// Navegación Global
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

if (isset($_REQUEST['irCierreCaja'])) {
    $_SESSION['paginaEnCurso'] = 'cierreCaja';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irMiPerfil'])) {
    $_SESSION['paginaEnCurso'] = 'MiPerfil';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['volver']) || isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Valores por defecto para los filtros
$periodo       = $_REQUEST['periodo'] ?? 'hoy';
$idCajero      = isset($_REQUEST['idCajero']) && $_REQUEST['idCajero'] !== '' ? (int)$_REQUEST['idCajero'] : null;
$numeroTicket  = isset($_REQUEST['numeroTicket']) && $_REQUEST['numeroTicket'] !== '' ? $_REQUEST['numeroTicket'] : null;
$tipoDocumento = isset($_REQUEST['tipoDocumento']) && in_array($_REQUEST['tipoDocumento'], ['venta', 'abono', 'todos'])
                 ? $_REQUEST['tipoDocumento'] : 'todos';

// Ordenación
$ordenPor = $_REQUEST['ordenPor'] ?? 'fecha';
$ordenDir = $_REQUEST['ordenDir'] ?? 'DESC';

// Lógica de periodos
switch ($periodo) {
    case 'hoy':
        $fechaDesdeRaw = date('Y-m-d');
        $fechaHastaRaw = date('Y-m-d');
        break;
    case 'semana':
        $fechaDesdeRaw = date('Y-m-d', strtotime('-7 days'));
        $fechaHastaRaw = date('Y-m-d');
        break;
    case 'mes':
        $fechaDesdeRaw = date('Y-m-d', strtotime('-30 days'));
        $fechaHastaRaw = date('Y-m-d');
        break;
    case 'todo':
        $fechaDesdeRaw = '2000-01-01';
        $fechaHastaRaw = date('Y-m-d');
        break;
    case 'personalizado':
    default:
        $fechaDesdeRaw = $_REQUEST['fechaDesde'] ?? date('Y-m-d');
        $fechaHastaRaw = $_REQUEST['fechaHasta'] ?? date('Y-m-d');
        break;
}

$aErrores = [
    'fechaDesde' => validacionFormularios::validarFecha($fechaDesdeRaw, '2050-01-01', '2000-01-01', 0),
    'fechaHasta' => validacionFormularios::validarFecha($fechaHastaRaw, '2050-01-01', '2000-01-01', 0)
];

$fechaDesde = $aErrores['fechaDesde'] ? date('Y-m-d') : $fechaDesdeRaw;
$fechaHasta = $aErrores['fechaHasta'] ? date('Y-m-d') : $fechaHastaRaw;

// Paginación
$pag = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit = 50;
$offset = ($pag - 1) * $limit;

// Modo de visualización: Ventas o Cierres
$verCierres = isset($_REQUEST['verCierres']) && $_REQUEST['verCierres'] == 1;

// Obtener lista de cajeros para el filtro
$listaCajeros = UsuarioPDO::listarUsuarios();

$totalRegistros = 0;

if ($verCierres) {
    require_once 'model/CierreFiscalPDO.php';
    // Mapear ordenación para cierres si es necesario
    $mColsCierres = ['fecha' => 'fecha', 'total' => 'total_general', 'id' => 'id'];
    $orderColZ = $mColsCierres[$ordenPor] ?? 'fecha';
    
    $listaCierres = CierreFiscalPDO::listarCierres($fechaDesde, $fechaHasta, $orderColZ, $ordenDir, $limit, $offset);
    $listaVentas  = [];
    $totalRegistros = CierreFiscalPDO::contarCierres($fechaDesde, $fechaHasta);
} else {
    // Obtener ventas y abonos filtrados
    $listaVentas    = VentaPDO::buscarVentas($fechaDesde, $fechaHasta, $idCajero, $numeroTicket, $tipoDocumento, $limit, $offset, $ordenPor, $ordenDir);
    $totalRegistros = VentaPDO::contarVentas($fechaDesde, $fechaHasta, $idCajero, $numeroTicket, $tipoDocumento);
    $listaCierres = [];
}

$totalPaginas = ceil($totalRegistros / $limit);

$avHistorial = [
    'verCierres'    => $verCierres,
    'ventas'        => $listaVentas,
    'cierres'       => $listaCierres,
    'cajeros'       => $listaCajeros,
    'aErrores'      => $aErrores,
    'paginacion'    => [
        'actual' => $pag,
        'total'  => $totalPaginas,
        'limit'  => $limit,
        'totalRegistros' => $totalRegistros
    ],
    'filtros'       => [
        'periodo'       => $periodo,
        'desde'         => $fechaDesde,
        'hasta'         => $fechaHasta,
        'cajero'        => $idCajero,
        'ticket'        => $numeroTicket,
        'tipoDocumento' => $tipoDocumento,
        'ordenPor'      => $ordenPor,
        'ordenDir'      => $ordenDir
    ],
    'usuario'       => $_SESSION['usuarioActualTPV']->getNombreCompleto()
];

require_once $view['layout'];
