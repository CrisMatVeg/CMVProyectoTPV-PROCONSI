<?php
// ============================================================
// SEED gen_ventas.php
// Genera: 1.000.000 ventas + ~2.5M lineas + 1M pagos + ~2.5M movimientos_stock
// Con cadena de hashes VeriFactu SHA256 exacta (misma fórmula que VentaPDO.php)
// ============================================================
set_time_limit(0);
ini_set('memory_limit', '512M');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../../config/confDBPDO.php';

const NIF_EMISOR    = '99999910G';
const TOTAL_VENTAS  = 1_000_000;
const DIAS_TOTAL    = 730;  // 2024-01-01 → 2025-12-31
const VENTAS_POR_DIA = 1370;

$tmpDir = __DIR__ . '/../../scriptsDB/tmp';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

$fVentas = $tmpDir . '/ventas.csv';
$fLineas = $tmpDir . '/lineas_venta.csv';
$fPagos  = $tmpDir . '/pagos_venta.csv';
$fMovs   = $tmpDir . '/movimientos_stock_v.csv';

// ============================================================
// Conexión PDO
// ============================================================
try {
    $db = new PDO(DSN, USERNAME, PASSWORD, [
        PDO::MYSQL_ATTR_LOCAL_INFILE => true,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    ]);
    $db->exec("SET GLOBAL local_infile = 1");
    $db->exec("SET SESSION sql_mode = ''");
} catch (PDOException $e) {
    die("Error conexión: " . $e->getMessage() . "\n");
}

echo "[" . date('H:i:s') . "] Cargando productos en memoria...\n";

// ============================================================
// 1. Cargar productos (20K) en memoria
// ============================================================
$stmt = $db->query("SELECT id, nombre, referencia, precio_venta, precio_coste FROM productos WHERE activo=1 ORDER BY id");
$prods = $stmt->fetchAll(PDO::FETCH_NUM);  // [id, nombre, referencia, precio_venta, precio_coste]
$prodCount = count($prods);
if ($prodCount === 0) die("No hay productos cargados. Ejecuta gen_productos.php primero.\n");
echo "[" . date('H:i:s') . "] $prodCount productos cargados.\n";

// ============================================================
// 2. Mapeo día → [turno_id, cierre_id]
// ============================================================
// Cada entrada: [fecha_inicio, fecha_fin_inclusive, turno_id, cierre_id]
$turnoRanges = [
    ['2024-01-01', '2024-01-20',  1, 1], ['2024-01-21', '2024-02-09',  2, 1], ['2024-02-10', '2024-02-29',  3, 1],
    ['2024-03-01', '2024-03-20',  4, 2], ['2024-03-21', '2024-04-09',  5, 2], ['2024-04-10', '2024-04-30',  6, 2],
    ['2024-05-01', '2024-05-20',  7, 3], ['2024-05-21', '2024-06-09',  8, 3], ['2024-06-10', '2024-06-30',  9, 3],
    ['2024-07-01', '2024-07-21', 10, 4], ['2024-07-22', '2024-08-11', 11, 4], ['2024-08-12', '2024-08-31', 12, 4],
    ['2024-09-01', '2024-09-20', 13, 5], ['2024-09-21', '2024-10-10', 14, 5], ['2024-10-11', '2024-10-31', 15, 5],
    ['2024-11-01', '2024-11-20', 16, 6], ['2024-11-21', '2024-12-10', 17, 6], ['2024-12-11', '2024-12-31', 18, 6],
    ['2025-01-01', '2025-01-20', 19, 7], ['2025-01-21', '2025-02-09', 20, 7], ['2025-02-10', '2025-02-28', 21, 7],
    ['2025-03-01', '2025-03-20', 22, 8], ['2025-03-21', '2025-04-09', 23, 8], ['2025-04-10', '2025-04-30', 24, 8],
    ['2025-05-01', '2025-05-31', 25, 9], ['2025-06-01', '2025-06-30', 26, 9], ['2025-07-01', '2025-07-31', 27, 9],
    ['2025-08-01', '2025-09-20', 28,10], ['2025-09-21', '2025-11-09', 29,10], ['2025-11-10', '2025-12-31', 30,10],
];

$baseDate = new DateTimeImmutable('2024-01-01');
$t0       = mktime(0,0,0,1,1,2024);
$dayInfo  = [];  // índice = días desde 2024-01-01

foreach ($turnoRanges as [$ini, $fin, $turnoId, $cierreId]) {
    $dIni = (int)$baseDate->diff(new DateTimeImmutable($ini))->days;
    $dFin = (int)$baseDate->diff(new DateTimeImmutable($fin))->days;
    for ($d = $dIni; $d <= $dFin; $d++) {
        $dayInfo[$d] = [$turnoId, $cierreId];
    }
}

// ============================================================
// 3. Helpers
// ============================================================
function formatTicketNumber(int $numero, int $ts, bool $esFactura): string
{
    $prefix   = $esFactura ? 'F' : 'T';
    // Formato: día sin cero + mes sin cero + año 2 dígitos → ej. "12526" para 12/5/2026
    $datePart = date('j', $ts) . date('n', $ts) . date('y', $ts);
    return "{$prefix}-{$datePart}-{$numero}";
}

function spainIso8601(int $ts): string
{
    $m  = (int)date('n', $ts);
    $tz = ($m >= 4 && $m <= 10) ? '+02:00' : '+01:00';
    return date('Y-m-d\TH:i:s', $ts) . $tz;
}

function calcHash(string $serie, int $tsExp, float $cuota, float $total, string $prevHash, int $tsGen): string
{
    $cadena = 'IDEmisorFactura=' . NIF_EMISOR
            . '&NumSerieFactura=' . $serie
            . '&FechaExpedicionFactura=' . date('d-m-Y', $tsExp)
            . '&TipoFactura=F1'
            . '&CuotaTotal=' . number_format($cuota, 2, '.', '')
            . '&ImporteTotal=' . number_format($total, 2, '.', '')
            . '&Huella=' . strtoupper($prevHash)
            . '&FechaHoraHusoGenRegistro=' . spainIso8601($tsGen);
    return strtoupper(hash('sha256', $cadena));
}

function pickLineas(): int
{
    // 20% → 1, 40% → 2, 30% → 3, 10% → 4
    $r = rand(1, 10);
    if ($r <= 2)  return 1;
    if ($r <= 6)  return 2;
    if ($r <= 9)  return 3;
    return 4;
}

function pickCantidad(): int
{
    // 70%→1, 20%→2, 10%→3
    $r = rand(1, 10);
    if ($r <= 7) return 1;
    if ($r <= 9) return 2;
    return 3;
}

// ============================================================
// 4. Abrir CSVs
// ============================================================
$hV = fopen($fVentas, 'wb');
$hL = fopen($fLineas, 'wb');
$hP = fopen($fPagos,  'wb');
$hM = fopen($fMovs,   'wb');

// Cabeceras (IGNORE 1 LINES en LOAD DATA)
fwrite($hV, "numero_ticket,fecha,id_usuario,id_cliente,tipo_cliente,metodo_pago,subtotal,descuento_pct,descuento_amt,base_imponible,iva_pct,iva_amt,total,efectivo_recibido,estado,es_factura,num_z,id_turno,hash_actual,hash_anterior,estado_envio_aeat\n");
fwrite($hL, "id_venta,id_producto,nombre_producto,codigo_producto,precio_unitario,precio_base_snapshot,precio_coste_unitario,iva_aplicado,cantidad,meses_garantia,total_linea\n");
fwrite($hP, "id_venta,fecha,importe,metodo_pago,id_usuario,id_turno\n");
fwrite($hM, "producto_id,tipo_movimiento,cantidad,usuario_id,fecha\n");

$metodos   = ['efectivo','efectivo','efectivo','efectivo','efectivo','efectivo','efectivo','tarjeta','tarjeta','bizum'];
$estados   = array_fill(0,95,'completada') + array_fill(95,3,'anulada') + array_fill(98,2,'devuelta');
$cajeros   = range(4, 20);

$prevHash  = str_repeat('0', 64);
$ventaId   = 1;     // espejo del auto_increment id en ventas
$ticket    = 1;
$ventasTot = 0;
$lineasTot = 0;
$msLocal   = 0;

echo "[" . date('H:i:s') . "] Generando CSVs...\n";

// ============================================================
// 5. Bucle principal
// ============================================================
for ($day = 0; $day < DIAS_TOTAL && $ventasTot < TOTAL_VENTAS; $day++) {
    $dayTs = $t0 + $day * 86400;

    [$turnoId, $cierreId] = $dayInfo[$day] ?? [1, 1];

    // Ventas para este día
    $remaining    = TOTAL_VENTAS - $ventasTot;
    $ventasHoy    = min(VENTAS_POR_DIA, $remaining);

    for ($v = 0; $v < $ventasHoy; $v++) {
        $ts = $dayTs + rand(28800, 79200);   // 8:00 → 22:00
        $fecha = date('Y-m-d H:i:s', $ts);

        // Usuario
        $idUsuario = ($v % 50 === 0) ? rand(1, 3) : $cajeros[($ventasTot) % 17];

        // Cliente
        if ($ventasTot % 20 === 0) {
            $idCliente   = 'NULL';
            $tipoCliente = 'particular';
        } else {
            $idCliente   = rand(1, 400000);
            $tipoCliente = ($idCliente % 10 === 0) ? 'empresa' : 'particular';
        }

        // Método pago
        $metodoPago = $metodos[$ventasTot % 10];

        // Estado y factura
        $idx10  = $ventasTot % 100;
        $estado = $estados[$idx10] ?? 'completada';
        $esFact = ($idx10 < 15) ? 1 : 0;

        // Generar líneas de venta
        $nLineas = pickLineas();
        $subtotal = 0.0;
        $lineasData = [];

        for ($l = 0; $l < $nLineas; $l++) {
            $prod = $prods[($ventasTot * 7 + $l * 3) % $prodCount];
            [$pId, $pNom, $pRef, $pVenta, $pCoste] = $prod;

            $pvp  = round((float)$pVenta, 2);
            $pBase = round($pvp / 1.21, 2);
            $pCst = round((float)$pCoste, 2);
            $qty  = pickCantidad();
            $totalLinea = round($pvp * $qty, 2);

            $lineasData[] = [$pId, $pNom, $pRef, $pvp, $pBase, $pCst, $qty, $totalLinea];
            $subtotal += $totalLinea;
        }

        $subtotal      = round($subtotal, 2);
        $baseImponible = round($subtotal / 1.21, 2);
        $ivaAmt        = round($subtotal - $baseImponible, 2);
        $total         = $subtotal;

        $efectivoRec = ($metodoPago === 'efectivo') ? (ceil($total * 2) / 2) : 0.00;

        // Hash VeriFactu
        $serie    = formatTicketNumber($ticket, $ts, (bool)$esFact);
        $hashAct  = calcHash($serie, $ts, $ivaAmt, $total, $prevHash, $ts + rand(1, 5));
        $hashPrev = $prevHash;
        $prevHash = $hashAct;

        // Escritura fila ventas
        fputcsv($hV, [
            $ticket, $fecha, $idUsuario, $idCliente, $tipoCliente, $metodoPago,
            number_format($subtotal,2,'.',''), '0.00', '0.00',
            number_format($baseImponible,2,'.',''), '21.00',
            number_format($ivaAmt,2,'.',''),
            number_format($total,2,'.',''),
            number_format($efectivoRec,2,'.',''),
            $estado, $esFact, $cierreId, $turnoId,
            $hashAct, $hashPrev, 'pendiente'
        ]);

        // Escritura lineas
        foreach ($lineasData as [$pId, $pNom, $pRef, $pvp, $pBase, $pCst, $qty, $totalLinea]) {
            fputcsv($hL, [
                $ventaId, $pId, $pNom, $pRef,
                number_format($pvp,2,'.',''),
                number_format($pBase,2,'.',''),
                number_format($pCst,2,'.',''),
                '21.00', $qty, 24,
                number_format($totalLinea,2,'.','')
            ]);
            // Movimiento stock
            fputcsv($hM, [$pId, 'venta', -$qty, $idUsuario, $fecha]);
            $lineasTot++;
        }

        // Pago
        fputcsv($hP, [$ventaId, $fecha, number_format($total,2,'.',''), $metodoPago, $idUsuario, $turnoId]);

        $ventaId++;
        $ticket++;
        $ventasTot++;
    }

    // Progreso cada 100K
    if ($ventasTot % 100000 === 0) {
        echo "[" . date('H:i:s') . "] $ventasTot ventas / $lineasTot lineas generadas...\n";
    }
}

fclose($hV); fclose($hL); fclose($hP); fclose($hM);
echo "[" . date('H:i:s') . "] CSVs generados: $ventasTot ventas, $lineasTot lineas.\n";

// ============================================================
// 6. LOAD DATA LOCAL INFILE
// ============================================================
function toMysqlPath(string $path): string {
    return str_replace('\\', '/', realpath($path));
}

function loadCsv(PDO $db, string $sql, string $label): void {
    echo "[" . date('H:i:s') . "] LOAD DATA: $label...\n";
    try {
        $db->exec($sql);
        echo "[" . date('H:i:s') . "] $label cargado OK.\n";
    } catch (PDOException $e) {
        die("Error LOAD $label: " . $e->getMessage() . "\n");
    }
}

$pV = toMysqlPath($fVentas);
$pL = toMysqlPath($fLineas);
$pP = toMysqlPath($fPagos);
$pM = toMysqlPath($fMovs);

// Deshabilitar checks FK para carga masiva
$db->exec("SET FOREIGN_KEY_CHECKS = 0");
$db->exec("SET UNIQUE_CHECKS = 0");
$db->exec("SET AUTOCOMMIT = 0");

loadCsv($db,
    "LOAD DATA LOCAL INFILE '{$pV}'
     INTO TABLE ventas
     FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
     LINES TERMINATED BY '\\n'
     IGNORE 1 LINES
     (numero_ticket, fecha, id_usuario, @id_cli, tipo_cliente, metodo_pago,
      subtotal, descuento_pct, descuento_amt, base_imponible, iva_pct,
      iva_amt, total, efectivo_recibido, estado, es_factura,
      num_z, id_turno, hash_actual, hash_anterior, estado_envio_aeat)
     SET id_cliente = IFNULL(NULLIF(@id_cli,'NULL'), 0),
         nombre_cliente = NULL, nif_cliente = NULL,
         aeat_id_type = NULL, aeat_codigo_pais = NULL,
         descuento_label = NULL, pagado_a_cuenta = 0,
         fecha_limite_pago = NULL, tipo_documento = 'venta',
         id_venta_origen = 0, fecha_hora_gen_fiscal = NULL,
         codigo_qr = NULL, qr_verifactu = NULL, comentarios = NULL,
         puntos_ganados = 0, puntos_canjeados = 0,
         puntos_descuento_amt = 0, id_venta_sustituida = 0,
         tipo_rectificativa = NULL, total_aeat = total",
    'ventas'
);

$db->exec("COMMIT");

loadCsv($db,
    "LOAD DATA LOCAL INFILE '{$pL}'
     INTO TABLE lineas_venta
     FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
     LINES TERMINATED BY '\\n'
     IGNORE 1 LINES
     (id_venta, id_producto, nombre_producto, codigo_producto,
      precio_unitario, precio_base_snapshot, precio_coste_unitario,
      iva_aplicado, cantidad, meses_garantia, total_linea)
     SET devuelta = 0, motivo_devolucion = NULL,
         fecha_devolucion = NULL, metodo_reembolso = NULL, numero_serie = NULL",
    'lineas_venta'
);

$db->exec("COMMIT");

loadCsv($db,
    "LOAD DATA LOCAL INFILE '{$pP}'
     INTO TABLE pagos_venta
     FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
     LINES TERMINATED BY '\\n'
     IGNORE 1 LINES
     (id_venta, fecha, importe, metodo_pago, id_usuario, id_turno)
     SET notas = NULL",
    'pagos_venta'
);

$db->exec("COMMIT");

loadCsv($db,
    "LOAD DATA LOCAL INFILE '{$pM}'
     INTO TABLE movimientos_stock
     FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
     LINES TERMINATED BY '\\n'
     IGNORE 1 LINES
     (producto_id, tipo_movimiento, cantidad, usuario_id, fecha)
     SET notas = 'Venta seed'",
    'movimientos_stock'
);

$db->exec("COMMIT");

// Restaurar checks
$db->exec("SET FOREIGN_KEY_CHECKS = 1");
$db->exec("SET UNIQUE_CHECKS = 1");
$db->exec("SET AUTOCOMMIT = 1");

// ============================================================
// 7. UPDATE stock_actual en productos
// ============================================================
echo "[" . date('H:i:s') . "] Actualizando stock_actual en productos...\n";
$db->exec("
    UPDATE productos p
    LEFT JOIN (
        SELECT producto_id, SUM(ABS(cantidad)) AS total_vendido
        FROM movimientos_stock
        WHERE tipo_movimiento = 'venta'
        GROUP BY producto_id
    ) ms ON ms.producto_id = p.id
    SET p.stock_actual = GREATEST(0, p.stock_actual - COALESCE(ms.total_vendido, 0))
    WHERE p.activo = 1
");
echo "[" . date('H:i:s') . "] Stock actualizado.\n";

// ============================================================
// 8. Verificación rápida
// ============================================================
$counts = $db->query("
    SELECT
      (SELECT COUNT(*) FROM ventas)           AS ventas,
      (SELECT COUNT(*) FROM lineas_venta)     AS lineas,
      (SELECT COUNT(*) FROM pagos_venta)      AS pagos,
      (SELECT COUNT(*) FROM movimientos_stock WHERE tipo_movimiento='venta') AS movs
")->fetch(PDO::FETCH_ASSOC);

echo "\n===== VERIFICACIÓN =====\n";
echo "ventas:          " . number_format($counts['ventas'])  . "\n";
echo "lineas_venta:    " . number_format($counts['lineas'])  . "\n";
echo "pagos_venta:     " . number_format($counts['pagos'])   . "\n";
echo "movimientos:     " . number_format($counts['movs'])    . "\n";

// Muestra los primeros 3 hashes para verificar cadena
$hashes = $db->query("SELECT numero_ticket, hash_anterior, hash_actual FROM ventas ORDER BY id LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
echo "\nPrimeros 3 hashes (cadena VeriFactu):\n";
foreach ($hashes as $h) {
    echo "  T#{$h['numero_ticket']}  prev=" . substr($h['hash_anterior'],0,16) . "...  act=" . substr($h['hash_actual'],0,16) . "...\n";
}

// ============================================================
// 9. Limpieza de CSVs temporales
// ============================================================
unlink($fVentas); unlink($fLineas); unlink($fPagos); unlink($fMovs);
echo "\n[" . date('H:i:s') . "] CSVs eliminados. ¡Seed de ventas completo!\n";
