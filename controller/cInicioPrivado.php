<?php
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

$userRol = $_SESSION['usuarioActualTPV']->getRol();
$esAdmin = ($userRol === "admin" || $userRol === "administrador");

// Navegación Global handled by index.php

// Estado de caja (turno actual) y posible apertura desde el TPV
require_once 'model/CajaTurnoPDO.php';
require_once 'model/TarifaPrecioPDO.php';
$turnoCaja = CajaTurnoPDO::obtenerTurnoAbierto();
$ultimoFondoSugerido = CajaTurnoPDO::obtenerUltimoFondoSugerido();


// Carga inicial limitada (se completará con scroll infinito/AJAX para optimizar rendimiento)
$oProductos = ProductoPDO::listarProductos(false, 100);
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
        "price" => $oProducto->getPrecioVenta(),
        "iva" => (float)$oProducto->getIva(),
        "precio_coste" => $oProducto->getPrecioCoste(),
        "stock_minimo" => (int)$oProducto->getStockMinimo(),
        "meses_garantia" => (int)$oProducto->getMesesGarantia(),
        "icono" => $icono,
        "cat" => $oProducto->getCategoria(),
        "stock" => (int)$oProducto->getStockActual(),

        "inactive" => !$oProducto->getActivo(),
        "activo" => $oProducto->getActivo(),
        "es_pack" => $oProducto->getEsPack(),
        "atributos" => $oProducto->getAtributos(),
        "mantener_precision" => $oProducto->getMantenerPrecision()
    ];
}


// Estado VeriFactu (cacheado 2 min en sesión para no ejecutar queries en cada recarga)
require_once 'model/AeatQueueService.php';
$aeatTs = '_cache_aeat_ts';
if (!isset($_SESSION['_cache_aeat'], $_SESSION[$aeatTs]) || (time() - $_SESSION[$aeatTs]) > 120) {
    $_SESSION['_cache_aeat'] = (new AeatQueueService())->obtenerResumenEstado();
    $_SESSION[$aeatTs] = time();
}
$resumenAEAT = $_SESSION['_cache_aeat'];

// Cargar categorías dinámicas
require_once 'model/CategoriaPDO.php';
$listaCategorias = CategoriaPDO::listarTodas();

// Cargar promociones activas
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
    "idTurno"     => $turnoCaja ? $turnoCaja['id'] : null,
    "fondoSugerido" => $ultimoFondoSugerido,
    "categorias" => $listaCategorias,
];

$_SESSION['arrayDatosusuarioActualTPV'] = $avInicioPrivado;
// Carga la vista layout principal
require_once $view["layout"];
