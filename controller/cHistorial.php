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
$fechaDesdeRaw = $_REQUEST['fechaDesde'] ?? date('Y-m-d', strtotime('-7 days'));
$fechaHastaRaw = $_REQUEST['fechaHasta'] ?? date('Y-m-d');
$idCajero   = isset($_REQUEST['idCajero']) && $_REQUEST['idCajero'] !== '' ? (int)$_REQUEST['idCajero'] : null;

$aErrores = [
    'fechaDesde' => validacionFormularios::validarFecha($fechaDesdeRaw, '2050-01-01', '2020-01-01', 0),
    'fechaHasta' => validacionFormularios::validarFecha($fechaHastaRaw, '2050-01-01', '2020-01-01', 0)
];

$fechaDesde = $aErrores['fechaDesde'] ? date('Y-m-d', strtotime('-7 days')) : $fechaDesdeRaw;
$fechaHasta = $aErrores['fechaHasta'] ? date('Y-m-d') : $fechaHastaRaw;

// Modo de visualización: Ventas o Cierres
$verCierres = isset($_REQUEST['verCierres']) && $_REQUEST['verCierres'] == 1;

// Obtener lista de cajeros para el filtro
$listaCajeros = UsuarioPDO::listarUsuarios();

if ($verCierres) {
    require_once 'model/CierreFiscalPDO.php';
    $listaCierres = CierreFiscalPDO::listarCierres(); // Podríamos filtrar por fecha si fuera necesario
    $listaVentas = [];
} else {
    // Obtener ventas filtradas
    $listaVentas = VentaPDO::buscarVentas($fechaDesde, $fechaHasta, $idCajero);
    $listaCierres = [];
}

$avHistorial = [
    'verCierres'   => $verCierres,
    'ventas'       => $listaVentas,
    'cierres'      => $listaCierres,
    'cajeros'      => $listaCajeros,
    'aErrores'     => $aErrores,
    'filtros'      => [
        'desde'   => $fechaDesde,
        'hasta'   => $fechaHasta,
        'cajero'  => $idCajero
    ],
    'usuario'      => $_SESSION['usuarioActualTPV']->getNombreCompleto()
];

require_once $view['layout'];
?>
