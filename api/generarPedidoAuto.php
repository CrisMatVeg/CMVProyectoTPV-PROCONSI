<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: generarPedidoAuto.php
 * Genera albaranes de compra automáticos para productos bajo stock mínimo.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/ProductoPDO.php';
    require_once __DIR__ . '/../model/ProveedorPDO.php';
    require_once __DIR__ . '/../model/TipoIVAPDO.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV']) || $_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        throw new Exception('No autorizado');
    }

    $sqlProd = "SELECT p.id, p.id_proveedor, p.nombre, p.referencia, p.stock_actual, p.stock_minimo, p.activo
                FROM productos p WHERE p.es_pack = 0 AND p.activo = 1 AND p.id_proveedor IS NOT NULL AND p.stock_actual <= p.stock_minimo";
    $stmtProd = DBPDO::ejecutarConsulta($sqlProd);
    $bajoStock = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    $omitidosInactivos = 0; $omitidosSinProveedor = 0; $validos = [];
    foreach ($bajoStock as $p) {
        if ($p['activo'] == 0) { $omitidosInactivos++; continue; }
        if (empty($p['id_proveedor'])) { $omitidosSinProveedor++; continue; }
        $validos[] = ['id' => $p['id'], 'nombre' => $p['nombre'], 'referencia' => $p['referencia'] ?? '', 'id_proveedor' => $p['id_proveedor'], 'stock_actual' => (int)$p['stock_actual'], 'stock_minimo' => (int)$p['stock_minimo']];
    }

    if (empty($validos)) {
        echo json_encode(['ok' => true, 'mensaje' => 'No se han podido generar pedidos.', 'total_bajo_stock' => count($bajoStock), 'omitidos_inactivos' => $omitidosInactivos, 'omitidos_sin_proveedor' => $omitidosSinProveedor, 'datos_pedido' => []]);
        exit;
    }

    $porProveedor = [];
    foreach ($validos as $p) {
        $provId = $p['id_proveedor'];
        if (!isset($porProveedor[$provId])) {
            $oProv = ProveedorPDO::buscarPorId($provId);
            $porProveedor[$provId] = ['proveedor_id' => $provId, 'proveedor_nombre' => $oProv ? $oProv->getNombre() : 'Proveedor Desconocido', 'aplica_re' => $oProv ? $oProv->getAplicaRe() : false, 'productos' => []];
        }
        $prodInfo = ProductoPDO::obtenerProductoPorId($p['id']);
        $tipoIva = TipoIVAPDO::obtenerVigentePorCodigo($prodInfo['codigo_iva'] ?? 'GENERAL', date('Y-m-d'));
        $pctIva = (float)($tipoIva['porcentaje'] ?? 21);
        $rePct = 0;
        if ($pctIva >= 21) $rePct = 5.2; elseif ($pctIva >= 10) $rePct = 1.4; elseif ($pctIva >= 4) $rePct = 0.5;
        $cantidadSugerida = max(($p['stock_minimo'] * 2), 10);
        $costeActual = (float)($prodInfo['precio_coste'] ?? 0);
        $precioNeto = 0;
        if ($costeActual > 0) {
            $pctRE = $rePct; // Siempre aplicado
            $divisor = 1 + ($pctIva / 100) + ($pctRE / 100); $precioNeto = round($costeActual / $divisor, 4);
        }
        $porProveedor[$provId]['productos'][] = ['id' => $p['id'], 'nombre' => $p['nombre'] . ' (' . ($p['referencia'] ?: 'S/R') . ')', 'referencia' => $p['referencia'] ?? '', 'cantidad' => $cantidadSugerida, 'precio_coste_neto' => $precioNeto, 'iva_pct' => $pctIva, 're_pct' => $rePct];
    }

    echo json_encode(['ok' => true, 'datos_pedido' => array_values($porProveedor), 'total_bajo_stock' => count($bajoStock), 'omitidos_inactivos' => $omitidosInactivos, 'omitidos_sin_proveedor' => $omitidosSinProveedor]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
