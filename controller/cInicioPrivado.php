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

// Navegación Global handled by index.php

// Estado de caja (turno actual) y posible apertura desde el TPV
require_once 'model/CajaTurnoPDO.php';
$turnoCaja = CajaTurnoPDO::obtenerTurnoAbierto();

if (isset($_POST['abrirCaja']) && !$turnoCaja) {
    $fondoInicial = max(0, (float)($_POST['fondoInicial'] ?? 0));
    CajaTurnoPDO::abrirTurno($_SESSION['usuarioActualTPV']->getId(), $fondoInicial);
    // Recalcular estado de caja tras la apertura
    $turnoCaja = CajaTurnoPDO::obtenerTurnoAbierto();
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
        "inactive" => !$oProducto->getActivo(),
        "variantes" => $oProducto->getVariantes()
    ];
}

// Cargar promociones activas para el TPV
require_once 'model/PromocionPDO.php';
$aPromos = PromocionPDO::listarActivas();

// Preparación de los datos del usuario para la vista
$avInicioPrivado = [
    "nombre_completo" => $_SESSION['usuarioActualTPV']->getNombre(),
    "username" => $_SESSION['usuarioActualTPV']->getLogin(),
    "password" => $_SESSION['usuarioActualTPV']->getPassword(),
    "rol" => $_SESSION['usuarioActualTPV']->getRol(),
    "esAdmin"  => $esAdmin,
    "productos" => $aProductos,
    "promos"   => $aPromos,
    "cajaAbierta" => (bool)$turnoCaja,
];
$_SESSION['arrayDatosusuarioActualTPV'] = $avInicioPrivado;
// Carga la vista layout principal
require_once $view["layout"];
