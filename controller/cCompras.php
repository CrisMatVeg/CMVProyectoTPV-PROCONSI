<?php
require_once 'model/CompraPDO.php';
require_once 'model/ProveedorPDO.php';
require_once 'model/ProductoPDO.php';

// Verificar sesión
if (!isset($_SESSION['usuarioActualTPV'])) {
    header('Location: index.php?pagina=login');
    exit;
}

$avInicioPrivado['proveedores'] = [];
$proveedores = ProveedorPDO::listarTodos(true);
foreach ($proveedores as $p) {
    $avInicioPrivado['proveedores'][] = [
        'id' => $p->getId(),
        'nombre' => $p->getNombre(),
        'aplica_re' => $p->getAplicaRe()
    ];
}

$avInicioPrivado['productos'] = [];
$productos = ProductoPDO::listarProductos(true);
foreach ($productos as $p) {
    $avInicioPrivado['productos'][] = [
        'id' => $p->getId(),
        'nombre' => $p->getNombre(),
        'referencia' => $p->getReferencia(),
        'iva' => $p->getIVA()
    ];
}

$avInicioPrivado['albaranes_pendientes'] = CompraPDO::listarAlbaranes(true);
$avInicioPrivado['historico_albaranes'] = CompraPDO::listarAlbaranes(false);
$avInicioPrivado['historico_facturas'] = CompraPDO::listarFacturas();

$avInicioPrivado['nombre_completo'] = $_SESSION['usuarioActualTPV']->getNombre();
$avInicioPrivado['esAdmin'] = ($_SESSION['usuarioActualTPV']->getRol() === 'admin');

// Carga la vista layout principal
require_once $view["layout"];
