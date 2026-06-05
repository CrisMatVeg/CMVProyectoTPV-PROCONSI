<?php
declare(strict_types=1);
set_time_limit(0);
ini_set('memory_limit', '256M');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../../config/confDBPDO.php';

$db = new PDO(DSN, USERNAME, PASSWORD, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$db->exec("SET FOREIGN_KEY_CHECKS=0");
$db->exec("SET SESSION sql_mode=''");

// ============================================================
// CONFIGURACIÓN
// ============================================================

// Categorías por proveedor (id_proveedor => categorias)
$provCats = [
    1 => ['smartphones', 'tablets', 'televisores', 'wearables'],   // Samsung
    2 => ['televisores', 'audio', 'smarthome'],                     // LG
    3 => ['televisores', 'audio', 'smarthome'],                     // Philips
    4 => ['audio', 'gaming', 'camaras'],                            // Sony
    5 => ['smartphones', 'tablets', 'wearables'],                   // Huawei
];

// Fechas de los 2 albaranes por proveedor
$albaranFechas = [
    1 => ['2024-03-15', '2024-09-20'],
    2 => ['2024-04-10', '2024-10-05'],
    3 => ['2024-05-12', '2024-11-18'],
    4 => ['2024-06-20', '2024-12-05'],
    5 => ['2025-02-10', '2025-06-15'],
];

$LINEAS_POR_ALBARAN = 8;  // entre 5 y 15 según plan; usamos 8 fijo

// ============================================================
// 1. OBTENER PRODUCTOS POR PROVEEDOR
// ============================================================

$prodByProv = [];
foreach ($provCats as $provId => $cats) {
    $productos = [];
    foreach ($cats as $cat) {
        $stmt = $db->prepare(
            "SELECT id, precio_coste, precio_venta
             FROM productos
             WHERE categoria = ? AND id_proveedor = ? AND activo = 1
             ORDER BY RAND()
             LIMIT 20"
        );
        $stmt->execute([$cat, $provId]);
        foreach ($stmt->fetchAll() as $row) {
            $productos[] = $row;
        }
    }
    // mezclar y asegurar mínimo de líneas
    shuffle($productos);
    $prodByProv[$provId] = $productos;
}

// ============================================================
// 2. CONSTRUIR DATOS
// ============================================================

$facturaRows  = [];
$albaranRows  = [];
$lineaRows    = [];
$entradaRows  = [];
$movRows      = [];

$albId   = 1;
$factId  = 1;

foreach ($provCats as $provId => $cats) {

    $factTotal  = 0.0;
    $thisFact   = $factId;

    foreach ($albaranFechas[$provId] as $fecha) {

        // Estado: los 4 primeros proveedores = validado, Huawei = recibido
        $estado = ($provId <= 4) ? 'validado' : 'recibido';
        $hora   = $fecha . ' 10:00:00';

        // Selección de productos para este albarán
        $pool = $prodByProv[$provId];
        shuffle($pool);
        $seleccionados = array_slice($pool, 0, min($LINEAS_POR_ALBARAN, count($pool)));

        $albBase = 0.0;
        $albIva  = 0.0;

        foreach ($seleccionados as $prod) {
            $cantidad    = rand(10, 50);
            $coste       = (float)$prod['precio_coste'];
            $ivaPct      = 21.00;
            $rePct       = 0.00;
            $lineaBase   = round($coste * $cantidad, 4);
            $lineaIva    = round($lineaBase * 0.21, 4);

            $albBase += $lineaBase;
            $albIva  += $lineaIva;

            // stock_anterior desde la tabla actual
            $stStmt = $db->prepare("SELECT stock_actual FROM productos WHERE id = ?");
            $stStmt->execute([$prod['id']]);
            $stockAnt = (int)($stStmt->fetchColumn() ?: 0);
            $stockNvo = $stockAnt + $cantidad;

            $lineaRows[]  = [$albId, $prod['id'], $cantidad, $coste, $ivaPct, $rePct];

            $entradaRows[] = [
                $prod['id'],
                0,               // id_variante (sin variantes en seed)
                $cantidad,
                $coste,
                $coste,          // cmp_anterior ≈ precio_coste
                $coste,          // cmp_resultante (no cambia en seed)
                $stockAnt,
                $stockNvo,
                1,               // id_usuario admin
                'Entrada compra alb-' . sprintf('%03d', $albId),
                $hora,
            ];

            $movRows[] = [$prod['id'], 'compra', $cantidad, 1,
                          'Compra alb-' . sprintf('%03d', $albId), $hora];
        }

        $albBase  = round($albBase, 2);
        $albIva   = round($albIva,  2);
        $albTotal = $albBase + $albIva;

        $numAlb   = sprintf('ALB-%d-%03d', (int)substr($fecha, 0, 4), $albId);

        $albaranRows[] = [
            $albId, $provId, $numAlb, $fecha,
            $albBase, $albIva, 0.00, $albTotal,
            $estado, $thisFact,
        ];

        $factTotal += $albTotal;
        $albId++;
    }

    // Factura agrupa los 2 albaranes del proveedor
    $factFecha = $albaranFechas[$provId][1];  // fecha del 2º albarán
    $factVenc  = date('Y-m-d', strtotime($factFecha . ' +30 days'));
    $numFact   = sprintf('FPROV-%d-%03d', (int)substr($factFecha, 0, 4), $thisFact);
    $pagado    = ($provId <= 3) ? 1 : 0;

    $facturaRows[] = [
        $thisFact, $provId, $numFact, $factFecha,
        round($factTotal, 2), 'transferencia', $pagado, $factVenc,
    ];

    $factId++;
}

// ============================================================
// 3. INSERTAR EN BD
// ============================================================

// Limpiar tablas (en orden inverso de dependencia)
$db->exec("DELETE FROM movimientos_stock WHERE tipo_movimiento = 'compra'");
$db->exec("TRUNCATE TABLE entradas_stock");
$db->exec("TRUNCATE TABLE lineas_compra");
$db->exec("TRUNCATE TABLE albaranes_compra");
$db->exec("TRUNCATE TABLE facturas_compra_prov");

// facturas_compra_prov
$stF = $db->prepare("INSERT INTO facturas_compra_prov
    (id, proveedor_id, numero_factura, fecha_factura, total, metodo_pago, pagado, fecha_vencimiento)
    VALUES (?,?,?,?,?,?,?,?)");
foreach ($facturaRows as $row) {
    $stF->execute($row);
}
echo "✓ facturas_compra_prov: " . count($facturaRows) . "\n";

// albaranes_compra
$stA = $db->prepare("INSERT INTO albaranes_compra
    (id, proveedor_id, numero_albaran, fecha, base_imponible, iva_total, re_total, total, estado, factura_id)
    VALUES (?,?,?,?,?,?,?,?,?,?)");
foreach ($albaranRows as $row) {
    $stA->execute($row);
}
echo "✓ albaranes_compra: " . count($albaranRows) . "\n";

// lineas_compra
$stL = $db->prepare("INSERT INTO lineas_compra
    (albaran_id, producto_id, cantidad, precio_coste_neto, iva_pct, re_pct)
    VALUES (?,?,?,?,?,?)");
foreach ($lineaRows as $row) {
    $stL->execute($row);
}
echo "✓ lineas_compra: " . count($lineaRows) . "\n";

// entradas_stock
$stE = $db->prepare("INSERT INTO entradas_stock
    (id_producto, id_variante, cantidad, precio_coste,
     cmp_anterior, cmp_resultante, stock_anterior, stock_nuevo,
     id_usuario, notas, fecha)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)");
foreach ($entradaRows as $row) {
    $stE->execute($row);
}
echo "✓ entradas_stock: " . count($entradaRows) . "\n";

// movimientos_stock tipo='compra'
$stM = $db->prepare("INSERT INTO movimientos_stock
    (producto_id, tipo_movimiento, cantidad, usuario_id, notas, fecha)
    VALUES (?,?,?,?,?,?)");
foreach ($movRows as $row) {
    $stM->execute($row);
}
echo "✓ movimientos_stock (compra): " . count($movRows) . "\n";

// ============================================================
// 4. ACTUALIZAR STOCK POR ENTRADAS DE COMPRA
// ============================================================
$db->exec("
    UPDATE productos p
    JOIN (
        SELECT producto_id, SUM(cantidad) AS total_entrada
        FROM movimientos_stock
        WHERE tipo_movimiento = 'compra'
        GROUP BY producto_id
    ) ms ON ms.producto_id = p.id
    SET p.stock_actual = p.stock_actual + ms.total_entrada
    WHERE p.activo = 1
");
echo "✓ Stock actualizado con entradas de compra\n";

// ============================================================
// 5. CORRELATIVOS FINALES
// ============================================================
$db->exec("
    INSERT INTO correlativos (nombre, valor)
    VALUES
        ('ticket',         1000000),
        ('numero_factura', 150000),
        ('numero_albaran', 10)
    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
");
echo "✓ Correlativos actualizados\n";

$db->exec("SET FOREIGN_KEY_CHECKS=1");
echo "\n--- Carga de compras completada ---\n";
