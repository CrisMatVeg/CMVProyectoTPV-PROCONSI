<?php
/**
 * Controller: cProductos.php
 * Gestiona la lista de productos para administración.
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

// Obtener la lista completa de productos (activos e inactivos para gestión)
$oProductos = ProductoPDO::listarProductos(false);
$listaProductos = [];
foreach ($oProductos as $oProd) {
    $icono = $oProd->getIcono();
    if ($icono && strlen($icono) > 10) {
        $icono = 'data:image/png;base64,' . base64_encode($icono);
    }

    $listaProductos[] = [
    'id'             => $oProd->getId(),
    'nombre'         => $oProd->getNombre(),
    'codigo'         => $oProd->getReferencia(),
    'precio'         => (float)$oProd->getPrecioVenta(),
    'precio_coste'   => (float)$oProd->getPrecioCoste(),   // ← añadir
    'iva'            => (float)$oProd->getIva(),           // ← añadir
    'meses_garantia' => (int)$oProd->getMesesGarantia(),   // ← añadir
    'stock_minimo'   => (int)$oProd->getStockMinimo(),     // ← añadir
    'icono'          => $icono,
    'categoria'      => $oProd->getCategoria(),
    'activo'         => $oProd->getActivo(),
    'stock'          => (int)$oProd->getStockActual()
];
}

$avProductos = [
    'productos' => $listaProductos,
    'usuario'   => $_SESSION['usuarioActualTPV']->getNombre()
];

require_once $view['layout'];
?>
