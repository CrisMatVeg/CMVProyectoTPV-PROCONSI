<?php

/**
 * API: exportarProductos.php
 * Exporta el catálogo de productos en formato CSV o JSON.
 * Incluye foto (base64) y variantes (JSON string).
 */
session_start();
if (!isset($_SESSION['usuarioActualTPV'])) {
    http_response_code(403);
    exit("No autorizado");
}

require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/ProductoPDO.php';

$format = $_GET['format'] ?? 'csv';
$productos = ProductoPDO::listarProductos(false); // Todos, incluyendo inactivos

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="productos_electrobazar_' . date('Ymd_His') . '.json"');

    $data = array_map(function ($p) {
        // El icono puede ser binario (blob de imagen) o un emoji. Lo convertimos a base64 si parece binario.
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
            'requiere_serial' => (int) $p->getRequiereSerial(),
            'codigo_iva'      => $p->getCodigoIva(),
            'variantes'       => $p->getVariantes(), // null o JSON string
            'icono'           => $icono,             // data:image/...;base64,... o emoji
        ];
    }, $productos);

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ── CSV ─────────────────────────────────────────────────────────────────────
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="productos_electrobazar_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');

// BOM para Excel (UTF-8)
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Cabecera del CSV
fputcsv($output, [
    'Referencia',
    'Nombre',
    'Descripción',
    'Categoría',
    'Precio Coste',
    'Precio Venta',
    'IVA (%)',
    'Stock Actual',
    'Stock Mínimo',
    'Garantía (meses)',
    'Activo (1/0)',
    'Requiere Serial (1/0)',
    'Código IVA',
    'Variantes (JSON)',
    'Foto (base64)',
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
        $p->getRequiereSerial() ? '1' : '0',
        $p->getCodigoIva(),
        $p->getVariantes() ?? '',  // JSON string o vacío
        $icono ?? '',              // data:image/...;base64,... o emoji o vacío
    ], ';');
}

fclose($output);
exit;
