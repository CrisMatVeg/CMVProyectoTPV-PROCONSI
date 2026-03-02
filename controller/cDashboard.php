<?php
/**
 * Controlador: cDashboard
 * 
 * Pantalla intermedia tras el login para elegir entre TPV o Gestión.
 */

// Si se pulsa salir, cerramos sesión
if (isset($_REQUEST['salir'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Navegación
if (isset($_REQUEST['irTPV'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPrivado';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irUsuarios'])) {
    $_SESSION['paginaEnCurso'] = 'Usuarios';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irProductos'])) {
    $_SESSION['paginaEnCurso'] = 'Productos';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irHistorial'])) {
    $_SESSION['paginaEnCurso'] = 'Historial';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irMiPerfil'])) {
    $_SESSION['paginaEnCurso'] = 'MiPerfil';
    header('Location: index.php');
    exit;
}

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
    $avDashboard['productos_count'] = count(ProductoPDO::listarProductos(false));
}

// Cargamos la vista de dashboard
require_once $view['layout'];
?>
