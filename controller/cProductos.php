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
        'precio_coste'   => (float)$oProd->getPrecioCoste(),
        'iva'            => (float)$oProd->getIva(),
        'meses_garantia' => (int)$oProd->getMesesGarantia(),
        'stock_minimo'   => (int)$oProd->getStockMinimo(),
        'icono'          => $icono,
        'categoria'      => $oProd->getCategoria(),
        'activo'         => $oProd->getActivo(),
        'stock'          => (int)$oProd->getStockActual(),
        'codigo_iva'     => $oProd->getCodigoIva(),
        'atributos'      => $oProd->getAtributos(),
        'es_pack'        => $oProd->getEsPack(),
        'id_proveedor'   => $oProd->getIdProveedor(),
        'precio_proveedor' => (float)$oProd->getPrecioProveedor(),
        'margen'         => (float)$oProd->getMargen(),
        'componentes_pack' => $oProd->getEsPack() ? ProductoPDO::obtenerComponentesPack($oProd->getId()) : []
    ];
}


// Obtener tipos de IVA y el general vigente para usarlo en la UI
require_once 'model/TipoIVAPDO.php';
require_once 'model/CategoriaPDO.php';
require_once 'model/ProveedorPDO.php';
$tipoGeneral = TipoIVAPDO::obtenerVigentePorCodigo('GENERAL', date('Y-m-d'));
$ivaGeneralActual = $tipoGeneral['porcentaje'] ?? 21.00;
$tiposIva = TipoIVAPDO::listarVigentesActuales();
$listaCategorias = CategoriaPDO::listarTodas();
$listaProveedores = ProveedorPDO::listarTodos(true); // Solo activos

$avProductos = [
    'productos'  => $listaProductos,
    'usuario'    => $_SESSION['usuarioActualTPV']->getNombre(),
    'ivaGeneral' => $ivaGeneralActual,
    'tipos_iva'  => $tiposIva,
    'categorias' => $listaCategorias,
    'proveedores' => array_map(function ($p) {
        return [
            'id' => $p->getId(),
            'nombre' => $p->getNombre(),
            'aplica_re' => $p->getAplicaRe()
        ];
    }, $listaProveedores)
];



require_once $view['layout'];
