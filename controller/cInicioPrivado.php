<?php
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

if ($_SESSION['usuarioActualTPV']->getRol() == "admin") {
    $esAdmin = true;
} else {
    $esAdmin = false;
}

if (isset($_REQUEST['salir'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irCierreCaja'])) {
    $_SESSION['paginaEnCurso'] = 'cierreCaja';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irDashboard']) || isset($_REQUEST['volver'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irMiPerfil'])) {
    $_SESSION['paginaEnCurso'] = 'MiPerfil';
    header('Location: index.php');
    exit;
}

// Carga de productos desde la base de datos (incluyendo inactivos para el filtro "De baja")
$oProductos = ProductoPDO::listarProductos(false);
$aProductos = [];
foreach ($oProductos as $oProducto) {
    // Procesamiento del icono (binario a base64 para la vista)
    $icono = $oProducto->getIcono();
    if ($icono && strlen($icono) > 10) { // Si es más largo que un emoji, asumimos imagen
        $icono = 'data:image/png;base64,' . base64_encode($icono);
    }

    $aProductos[] = [
        "id" => $oProducto->getId(),
        "name" => $oProducto->getNombre(),
        "codigo" => $oProducto->getReferencia(),
        "price" => (float)$oProducto->getPrecioVenta(),
        "iva" => (float)$oProducto->getIva(),
        "precio_coste" => (float)$oProducto->getPrecioCoste(),
        "stock_minimo" => (int)$oProducto->getStockMinimo(),
        "meses_garantia" => (int)$oProducto->getMesesGarantia(),
        "requiere_serial" => (int)$oProducto->getRequiereSerial(),
        "icono" => $icono,
        "cat" => $oProducto->getCategoria(),
        "stock" => (int)$oProducto->getStockActual(),
        "inactive" => !$oProducto->getActivo()
    ];
}

// Preparación de los datos del usuario para la vista
$avInicioPrivado = [
    "nombre_completo" => $_SESSION['usuarioActualTPV']->getNombre(),
    "username" => $_SESSION['usuarioActualTPV']->getLogin(),
    "password" => $_SESSION['usuarioActualTPV']->getPassword(),
    "rol" => $_SESSION['usuarioActualTPV']->getRol(),
    "esAdmin" => $esAdmin,
    "productos" => $aProductos
];
$_SESSION['arrayDatosusuarioActualTPV'] = $avInicioPrivado;
// Carga la vista layout principal
require_once $view["layout"];
