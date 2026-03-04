<?php

/**
 * API: importarProductos.php
 * Importa productos desde un archivo CSV o JSON.
 * Soporta foto (base64) y variantes (JSON string).
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuarioActualTPV'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    echo json_encode(['ok' => false, 'error' => 'Archivo no recibido']);
    exit;
}

require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/ProductoPDO.php';

$file = $_FILES['file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$productos = [];

if ($ext === 'json') {
    $content  = file_get_contents($file['tmp_name']);
    $productos = json_decode($content, true);
    if (!is_array($productos)) {
        echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
        exit;
    }
} elseif ($ext === 'csv') {
    $fh = fopen($file['tmp_name'], 'r');

    // Eliminar BOM si existe
    $bom = fread($fh, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($fh);
    }

    // Omitir cabecera
    fgetcsv($fh, 0, ';');

    while (($row = fgetcsv($fh, 0, ';')) !== false) {
        if (count($row) < 2 || empty($row[0])) continue;

        $productos[] = [
            'referencia'      => $row[0],
            'nombre'          => $row[1],
            'descripcion'     => $row[2] ?? '',
            'categoria'       => $row[3] ?? '',
            'precio_coste'    => str_replace(',', '.', $row[4] ?? '0'),
            'precio_venta'    => str_replace(',', '.', $row[5] ?? '0'),
            'iva'             => str_replace(',', '.', $row[6] ?? '21'),
            'stock_actual'    => $row[7] ?? 0,
            'stock_minimo'    => $row[8] ?? 0,
            'meses_garantia'  => $row[9] ?? 24,
            'activo'          => $row[10] ?? 1,
            'requiere_serial' => $row[11] ?? 0,
            'codigo_iva'      => $row[12] ?? 'GENERAL',
            'variantes'       => $row[13] ?? null,  // JSON string
            'icono'           => $row[14] ?? '',    // data:image/...;base64,... o emoji
        ];
    }
    fclose($fh);
} else {
    echo json_encode(['ok' => false, 'error' => 'Formato no soportado. Usa CSV o JSON.']);
    exit;
}

$creados     = 0;
$actualizados = 0;
$errores     = 0;

foreach ($productos as $data) {
    if (empty($data['referencia'])) {
        $errores++;
        continue;
    }

    try {
        // Normalizar variantes: las pasamos tal cual al modelo, 
        // que ya se encarga de normalizarlas con self::normalizarVariantes()
        if (isset($data['variantes']) && is_string($data['variantes'])) {
            // Si es un string "null" o similar, lo limpiamos
            if ($data['variantes'] === 'null' || $data['variantes'] === '[]' || $data['variantes'] === '{}') {
                $data['variantes'] = null;
            }
        }

        // Si el icono viene como data URI (base64), convertir a binario para almacenar como antes
        if (!empty($data['icono']) && strpos($data['icono'], 'data:image') === 0) {
            $parts = explode(',', $data['icono'], 2);
            if (count($parts) === 2) {
                $data['icono'] = base64_decode($parts[1]);
            }
        }

        $existente = ProductoPDO::obtenerProductoPorReferencia($data['referencia']);

        if ($existente) {
            // Mergeamos: los campos del CSV/JSON sobreescriben los existentes
            $updateData = array_merge($existente, $data);
            ProductoPDO::editarProducto($existente['id'], $updateData);
            $actualizados++;
        } else {
            ProductoPDO::añadirProducto($data);
            $creados++;
        }
    } catch (Exception $e) {
        $errores++;
    }
}

echo json_encode([
    'ok'      => true,
    'message' => 'Importación completada',
    'stats'   => [
        'creados'      => $creados,
        'actualizados' => $actualizados,
        'errores'      => $errores,
    ],
]);
