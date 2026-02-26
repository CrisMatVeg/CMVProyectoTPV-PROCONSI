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

// Volver al Dashboard
if (isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Valores por defecto para los filtros
$fechaDesde = $_REQUEST['fechaDesde'] ?? date('Y-m-d', strtotime('-7 days'));
$fechaHasta = $_REQUEST['fechaHasta'] ?? date('Y-m-d');
$idCajero   = isset($_REQUEST['idCajero']) && $_REQUEST['idCajero'] !== '' ? (int)$_REQUEST['idCajero'] : null;

// Obtener lista de cajeros para el filtro
$listaCajeros = UsuarioPDO::listarUsuarios();

// Obtener ventas filtradas
$listaVentas = VentaPDO::buscarVentas($fechaDesde, $fechaHasta, $idCajero);

$avHistorial = [
    'ventas'       => $listaVentas,
    'cajeros'      => $listaCajeros,
    'filtros'      => [
        'desde'   => $fechaDesde,
        'hasta'   => $fechaHasta,
        'cajero'  => $idCajero
    ],
    'usuario'      => $_SESSION['usuarioActualTPV']->getNombreCompleto()
];

require_once $view['layout'];
?>
