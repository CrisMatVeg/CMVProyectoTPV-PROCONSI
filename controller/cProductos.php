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

// Solo administradores o gestores de productos
if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_productos')) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irCierreCaja'])) {
    $_SESSION['paginaEnCurso'] = 'cierreCaja';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['volver'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Aplicar ajustes masivos programados que hayan vencido
ProductoPDO::aplicarAjustesPendientes();

// Paginación
['pag' => $pag, 'limit' => $limit, 'offset' => $offset] = obtenerPaginacion(50);

$totalProductos = ProductoPDO::contarProductos(false, '', '', 'all', null, null, '', true);
$totalPaginas = ceil($totalProductos / $limit);

// Helper para mapear objetos Producto a arrays para la vista
function mapProducto(Producto $oProd) {
    static $idsAlbaranPendiente = null;
    if ($idsAlbaranPendiente === null) {
        $idsAlbaranPendiente = ProductoPDO::idsConAlbaranPendiente();
    }

    $icono = $oProd->getIcono();
    if ($icono && strlen($icono) > 200 && strpos($icono, 'data:image') === false) {
        $icono = 'data:image/png;base64,' . base64_encode($icono);
    }

    return [
        'id'             => $oProd->getId(),
        'nombre'         => $oProd->getNombre(),
        'referencia'     => $oProd->getReferencia(),
        'precio_venta'   => $oProd->getPrecioVenta(),
        'precio_coste'   => $oProd->getPrecioCoste(),
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
        'precio_proveedor' => $oProd->getPrecioProveedor(),
        'margen'         => (float)$oProd->getMargen(),
        'mantener_precision' => (int)$oProd->getMantenerPrecision(),
        'componentes_pack' => $oProd->getEsPack() ? ProductoPDO::obtenerComponentesPack($oProd->getId()) : [],
        'albaran_pendiente' => in_array($oProd->getId(), $idsAlbaranPendiente)
    ];
}

// Obtener la lista de productos paginada (sin packs)
$oProductos = ProductoPDO::listarProductos(false, $limit, $offset, '', '', 'all', null, null, '', true);
$listaProductos = array_map('mapProducto', $oProductos);

// Obtener la lista completa de packs para la pestaña específica
$oPacks = ProductoPDO::listarPacks();
$listaPacks = array_map('mapProducto', $oPacks);

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
    'packs'      => $listaPacks,
    'usuario'    => $_SESSION['usuarioActualTPV']->getNombre(),
    'ivaGeneral' => $ivaGeneralActual,
    'tipos_iva'  => $tiposIva,
    'categorias' => $listaCategorias,
    'paginacion' => [
        'actual' => $pag,
        'total'  => $totalPaginas,
        'limit'  => $limit,
        'totalRegistros' => $totalProductos
    ],
    'proveedores' => array_map(function ($p) {
        return [
            'id' => $p->getId(),
            'nombre' => $p->getNombre(),
            'aplica_re' => $p->getAplicaRe()
        ];
    }, $listaProveedores)
];



require_once $view['layout'];
