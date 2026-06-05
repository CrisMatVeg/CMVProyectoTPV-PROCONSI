<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: importarProductos.php
 * Importa productos desde un archivo CSV o JSON.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/ProductoPDO.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
        echo json_encode(['ok' => false, 'error' => 'Archivo no recibido']);
        exit;
    }

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
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($fh);
        fgetcsv($fh, 0, ';'); // Omitir cabecera

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
                'codigo_iva'      => $row[11] ?? 'GENERAL',
                'icono'           => $row[12] ?? '',
            ];
        }
        fclose($fh);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Formato no soportado. Usa CSV o JSON.']);
        exit;
    }

    $creados = 0; $actualizados = 0; $errores = 0;
    foreach ($productos as $data) {
        if (empty($data['referencia'])) { $errores++; continue; }
        try {
            if (!empty($data['icono']) && strpos($data['icono'], 'data:image') === 0) {
                $parts = explode(',', $data['icono'], 2);
                if (count($parts) === 2) $data['icono'] = base64_decode($parts[1]);
            }
            $existente = ProductoPDO::obtenerProductoPorReferencia($data['referencia']);
            if ($existente) {
                $updateData = array_merge($existente, $data);
                ProductoPDO::editarProducto($existente['id'], $updateData);
                $actualizados++;
            } else {
                ProductoPDO::agregarProducto($data);
                $creados++;
            }
        } catch (Exception $e) { $errores++; }
    }

    echo json_encode([
        'ok'      => true,
        'message' => 'Importación completada',
        'stats'   => ['creados' => $creados, 'actualizados' => $actualizados, 'errores' => $errores],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
