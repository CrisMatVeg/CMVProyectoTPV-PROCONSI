<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: exportarProductos.php
 * Exporta el catálogo de productos en formato CSV o JSON.
 */

ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/ProductoPDO.php';

    // session_start(); // Handled by csrf_check.php

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(403);
        exit("No autorizado");
    }

    $format = $_GET['format'] ?? 'csv';
    $productos = ProductoPDO::listarProductos(false); // Todos, incluyendo inactivos

    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="productos_electrobazar_' . date('Ymd_His') . '.json"');

        $data = array_map(function ($p) {
            $icono = $p->getIcono();
            if ($icono && !mb_check_encoding($icono, 'UTF-8')) {
                $icono = 'data:image/jpeg;base64,' . base64_encode($icono);
            }

            return [
                'referencia'      => $p->getReferencia(),
                'nombre'          => $p->getNombre(),
                'descripcion'     => $p->getDescripcion(),
                'categoria'       => $p->getCategoria(),
                'precio_coste'    => (float) $p->getPrecioCoste(),
                'precio_venta'    => (float) $p->getPrecioVenta(),
                'iva'             => (float) $p->getIva(),
                'stock_actual'    => (int) $p->getStockActual(),
                'stock_minimo'    => (int) $p->getStockMinimo(),
                'meses_garantia'  => (int) $p->getMesesGarantia(),
                'activo'          => (int) $p->getActivo(),
                'codigo_iva'      => $p->getCodigoIva(),
                'icono'           => $icono,
            ];
        }, $productos);

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="productos_electrobazar_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

    fputcsv($output, [
        'Referencia', 'Nombre', 'Descripción', 'Categoría', 'Precio Coste', 
        'Precio Venta', 'IVA (%)', 'Stock Actual', 'Stock Mínimo', 
        'Garantía (meses)', 'Activo (1/0)', 'Código IVA', 'Foto (base64)'
    ], ';');

    foreach ($productos as $p) {
        $icono = $p->getIcono();
        if ($icono && !mb_check_encoding($icono, 'UTF-8')) {
            $icono = 'data:image/jpeg;base64,' . base64_encode($icono);
        }

        fputcsv($output, [
            $p->getReferencia(),
            $p->getNombre(),
            $p->getDescripcion(),
            $p->getCategoria(),
            str_replace('.', ',', $p->getPrecioCoste()),
            str_replace('.', ',', $p->getPrecioVenta()),
            str_replace('.', ',', $p->getIva()),
            $p->getStockActual(),
            $p->getStockMinimo(),
            $p->getMesesGarantia(),
            $p->getActivo() ? '1' : '0',
            $p->getCodigoIva(),
            $icono ?? '',
        ], ';');
    }

    fclose($output);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
