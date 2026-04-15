<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '1G');

require_once 'config/confDBPDO.php';

$numVentas = 1_000_000;
$csvVentas  = __DIR__ . '/ventas_stress.csv';
$csvLineas  = __DIR__ . '/lineas_stress.csv';

// ── Datos de referencia ──────────────────────────────────────────────────────
$metodosPago   = ['efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'mixto'];
$estados       = ['completada', 'devuelta', 'anulada', 'pendiente_pago', 'parcialmente_devuelta'];
$pesoEstados   = [70, 8, 5, 10, 7]; // % de probabilidad de cada estado
$tiposCliente  = ['particular', 'empresa'];
$nombresCliente= ['Carlos García','María López','Juan Pérez','Ana Martínez',
                  'Pedro Sánchez','Lucía Fernández','Miguel Torres','Sara Gómez'];
$productos     = [
    ['nombre' => 'Laptop Pro 15',        'codigo' => 'LAP-001', 'precio' => 899.99, 'coste' => 550.00, 'iva' => 21.00],
    ['nombre' => 'Ratón Inalámbrico',    'codigo' => 'RAT-002', 'precio' => 29.99,  'coste' => 10.00,  'iva' => 21.00],
    ['nombre' => 'Teclado Mecánico',     'codigo' => 'TEC-003', 'precio' => 79.99,  'coste' => 35.00,  'iva' => 21.00],
    ['nombre' => 'Monitor 27"',          'codigo' => 'MON-004', 'precio' => 349.99, 'coste' => 200.00, 'iva' => 21.00],
    ['nombre' => 'Auriculares BT',       'codigo' => 'AUR-005', 'precio' => 59.99,  'coste' => 22.00,  'iva' => 21.00],
    ['nombre' => 'Disco SSD 1TB',        'codigo' => 'SSD-006', 'precio' => 89.99,  'coste' => 45.00,  'iva' => 21.00],
    ['nombre' => 'Webcam HD',            'codigo' => 'CAM-007', 'precio' => 49.99,  'coste' => 18.00,  'iva' => 21.00],
    ['nombre' => 'Hub USB-C',            'codigo' => 'HUB-008', 'precio' => 39.99,  'coste' => 14.00,  'iva' => 21.00],
    ['nombre' => 'Tablet 10"',           'codigo' => 'TAB-009', 'precio' => 249.99, 'coste' => 130.00, 'iva' => 21.00],
    ['nombre' => 'Cable HDMI 2m',        'codigo' => 'CAB-010', 'precio' => 12.99,  'coste' => 3.00,   'iva' => 21.00],
];

// ── Helper: elegir estado con peso ───────────────────────────────────────────
function weightedRandom(array $items, array $weights): string {
    $rand = rand(1, 100);
    $cumulative = 0;
    foreach ($items as $i => $item) {
        $cumulative += $weights[$i];
        if ($rand <= $cumulative) return $item;
    }
    return $items[0];
}

echo "Iniciando generación de datos...\n";

try {
    // ── Contar antes ─────────────────────────────────────────────────────────
    $pdoPre     = new PDO(DSN, USERNAME, PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $cntVentas  = $pdoPre->query("SELECT COUNT(*) FROM ventas")->fetchColumn();
    $cntLineas  = $pdoPre->query("SELECT COUNT(*) FROM lineas_venta")->fetchColumn();
    $maxId      = (int)$pdoPre->query("SELECT COALESCE(MAX(id),0) FROM ventas")->fetchColumn();
    echo "Ventas antes: $cntVentas | Líneas antes: $cntLineas\n";
    echo "Último ID de ventas: $maxId (los nuevos empezarán desde " . ($maxId + 1) . ")\n";

    // ── Abrir CSVs ───────────────────────────────────────────────────────────
    $fVentas = fopen($csvVentas, 'w');
    $fLineas  = fopen($csvLineas, 'w');
    if (!$fVentas || !$fLineas) throw new Exception("No se pudieron crear los CSV");

    $numZ       = 1;
    $ventasPorZ = 200; // cada 200 ventas cambia el num_z (simula cierre de caja)

    echo "Generando CSVs...\n";
    $genStart = microtime(true);

    for ($i = 1; $i <= $numVentas; $i++) {
        $ventaId     = $maxId + $i;
        $numTicket   = $ventaId;
        $fecha       = date('Y-m-d H:i:s', rand(strtotime('2020-01-01'), strtotime('2025-12-31')));
        $idUsuario   = rand(1, 5);
        $idCliente   = rand(0, 1) ? rand(1, 400000) : ''; // algunos sin cliente
        $tipoCliente = $tiposCliente[array_rand($tiposCliente)];
        $nomCliente  = $nombresCliente[array_rand($nombresCliente)];
        $nifCliente  = '';
        $metodoPago  = $metodosPago[array_rand($metodosPago)];
        $estado      = weightedRandom($estados, $pesoEstados);
        $descPct     = [0, 0, 0, 5, 10, 15][rand(0, 5)]; // mayoría sin descuento
        $esFact      = rand(0, 10) > 8 ? 1 : 0;
        $numZVal     = $numZ;
        $idTurno     = rand(1, 3);
        $tipoDoc     = 'venta';
        $idVentaOrig = '';

        // ── Líneas de esta venta (1 a 4 productos) ───────────────────────────
        $numLineas  = rand(1, 4);
        $subtotal   = 0.0;

        for ($l = 0; $l < $numLineas; $l++) {
            $prod       = $productos[array_rand($productos)];
            $cantidad   = rand(1, 5);
            $precioUnit = $prod['precio'];
            $precioBase = $prod['precio'];
            $precioCoste= $prod['coste'];
            $ivaApl     = $prod['iva'];
            $totalLinea = round($precioUnit * $cantidad, 2);
            $subtotal  += $totalLinea;

            $devuelta      = ($estado === 'devuelta') ? 1 : 0;
            $motivoDev     = '';
            $fechaDev      = '';
            $metodoReemb   = '';
            if ($devuelta) {
                $motivoDev   = 'Producto defectuoso';
                $fechaDev    = date('Y-m-d H:i:s', strtotime($fecha) + rand(86400, 604800));
                $metodoReemb = ['efectivo', 'vale', 'reemplazo', 'otros'][rand(0, 3)];
            }

            fputcsv($fLineas, [
                $ventaId,
                $prod['codigo'] !== '' ? rand(1, 20) : '', // id_producto aproximado
                $prod['nombre'],
                $prod['codigo'],
                $precioUnit,
                $precioBase,
                $precioCoste,
                $ivaApl,
                $cantidad,
                24,           // meses_garantia
                $totalLinea,
                $devuelta,
                $motivoDev,
                $fechaDev,
                $metodoReemb,
                ''            // numero_serie
            ]);
        }

        // ── Calcular totales de la venta ─────────────────────────────────────
        $subtotal    = round($subtotal, 2);
        $descAmt     = round($subtotal * $descPct / 100, 2);
        $descLabel   = $descPct > 0 ? "Descuento {$descPct}%" : '';
        $baseImp     = round($subtotal - $descAmt, 2);
        $ivaPct      = 21.00;
        $ivaAmt      = round($baseImp * $ivaPct / 100, 2);
        $total       = round($baseImp + $ivaAmt, 2);
        $efectRec    = in_array($metodoPago, ['efectivo', 'mixto']) ? $total + rand(0, 20) : 0;
        $pagCuenta   = $metodoPago === 'a_cuenta' ? round($total * 0.5, 2) : 0;
        $fechaLimPago= $metodoPago === 'a_cuenta'
                        ? date('Y-m-d', strtotime($fecha) + 2592000)
                        : '';
        $puntosGan   = (int)($total / 10);
        $puntosCanjeados = rand(0, 1) ? rand(0, min(500, $puntosGan)) : 0;
        $puntosDescAmt   = round($puntosCanjeados * 0.01, 2);

        if ($i % $ventasPorZ === 0) $numZ++;

        fputcsv($fVentas, [
            $numTicket, $fecha, $idUsuario, $idCliente, $tipoCliente,
            $nomCliente, $nifCliente, $metodoPago, $subtotal, $descPct,
            $descAmt, $descLabel, $baseImp, $ivaPct, $ivaAmt, $total,
            $efectRec, $estado, $pagCuenta, $fechaLimPago, $esFact,
            '', // comentarios
            $numZVal, $idTurno, $tipoDoc, $idVentaOrig,
            $puntosGan, $puntosCanjeados, $puntosDescAmt
        ]);

        if ($i % 100000 === 0) {
            echo "  Generadas $i ventas...\n";
        }
    }

    fclose($fVentas);
    fclose($fLineas);
    $genEnd = microtime(true);
    echo "CSVs generados en " . round($genEnd - $genStart, 2) . " segundos.\n";

    // ── Conectar y cargar ─────────────────────────────────────────────────────
    $pdo = new PDO(DSN, USERNAME, PASSWORD, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_LOCAL_INFILE => true
    ]);
    $pdo->exec("SET GLOBAL local_infile = 1");
    $pdo->exec("SET foreign_key_checks = 0"); // más rápido al insertar

    $csvV = str_replace('\\', '/', $csvVentas);
    $csvL = str_replace('\\', '/', $csvLineas);

    // ── Cargar ventas ─────────────────────────────────────────────────────────
    echo "Cargando ventas...\n";
    $t = microtime(true);
    $pdo->exec("
        LOAD DATA LOCAL INFILE '$csvV'
        INTO TABLE ventas
        FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
        LINES TERMINATED BY '\n'
        (numero_ticket, fecha, id_usuario, @id_cliente, tipo_cliente,
         nombre_cliente, nif_cliente, metodo_pago, subtotal, descuento_pct,
         descuento_amt, descuento_label, base_imponible, iva_pct, iva_amt, total,
         efectivo_recibido, estado, pagado_a_cuenta, @fecha_limite_pago, es_factura,
         comentarios, num_z, id_turno, tipo_documento, @id_venta_origen,
         puntos_ganados, puntos_canjeados, puntos_descuento_amt)
        SET
            id_cliente       = NULLIF(@id_cliente, ''),
            fecha_limite_pago= NULLIF(@fecha_limite_pago, ''),
            id_venta_origen  = NULLIF(@id_venta_origen, '')
    ");
    echo "Ventas cargadas en " . round(microtime(true) - $t, 2) . " segundos.\n";

    // ── Cargar líneas ─────────────────────────────────────────────────────────
    echo "Cargando líneas de venta...\n";
    $t = microtime(true);
    $pdo->exec("
        LOAD DATA LOCAL INFILE '$csvL'
        INTO TABLE lineas_venta
        FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
        LINES TERMINATED BY '\n'
        (id_venta, @id_producto, nombre_producto, codigo_producto,
         precio_unitario, precio_base_snapshot, precio_coste_unitario,
         iva_aplicado, cantidad, meses_garantia, total_linea,
         devuelta, @motivo_devolucion, @fecha_devolucion,
         @metodo_reembolso, @numero_serie)
        SET
            id_producto       = NULLIF(@id_producto, ''),
            motivo_devolucion = NULLIF(@motivo_devolucion, ''),
            fecha_devolucion  = NULLIF(@fecha_devolucion, ''),
            metodo_reembolso  = NULLIF(@metodo_reembolso, ''),
            numero_serie      = NULLIF(@numero_serie, '')
    ");
    echo "Líneas cargadas en " . round(microtime(true) - $t, 2) . " segundos.\n";

    $pdo->exec("SET foreign_key_checks = 1");

    // ── Verificación ──────────────────────────────────────────────────────────
    $totalVentas = $pdo->query("SELECT COUNT(*) FROM ventas")->fetchColumn();
    $totalLineas = $pdo->query("SELECT COUNT(*) FROM lineas_venta")->fetchColumn();
    echo "\n=== RESULTADO ===\n";
    echo "Total ventas en BD:        $totalVentas (antes: $cntVentas)\n";
    echo "Total líneas en BD:        $totalLineas (antes: $cntLineas)\n";
    echo "Ventas nuevas:             " . ($totalVentas - $cntVentas) . "\n";
    echo "Líneas nuevas:             " . ($totalLineas - $cntLineas) . "\n";

    // ── Limpieza ──────────────────────────────────────────────────────────────
    unlink($csvVentas);
    unlink($csvLineas);
    echo "CSVs temporales eliminados.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}