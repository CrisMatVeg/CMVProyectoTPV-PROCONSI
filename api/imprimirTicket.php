<?php
// api/imprimirTicket.php
// Envía un ticket a una impresora térmica ESC/POS (si está disponible)

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

try {
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/VentaPDO.php';

session_start();
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $numTicket = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($numTicket <= 0) {
        throw new Exception('Número de ticket faltante');
    }

    // Cargar autoload de Composer sólo si existe (para no romper en entornos sin librería)
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new Exception('Soporte ESC/POS no instalado (falta vendor/autoload.php).');
    }
    require_once $autoloadPath;

    // Importar clases de la librería ESC/POS
    $printerClass  = '\Mike42\Escpos\Printer';
    $connectorClass = '\Mike42\Escpos\PrintConnectors\WindowsPrintConnector';

    /** @var array<string,mixed> $venta */
    $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    // TODO: Cambia "EPSON" por el nombre compartido real de tu impresora en Windows.
    $connector = new $connectorClass("EPSON");
    $printer   = new $printerClass($connector);

    // Encabezado
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setEmphasis(true);
    $printer->text("ElectroBazar\n");
    $printer->setEmphasis(false);
    $printer->text("C/ Tecnología 24, 28001 Madrid\n");
    $printer->text("NIF: B87654321\n");
    $printer->feed();

    // Datos ticket
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("Ticket: #" . str_pad($venta['numero_ticket'], 4, '0', STR_PAD_LEFT) . "\n");
    $printer->text("Fecha : " . $venta['fecha'] . "\n");
    $printer->text("Pago  : " . ucfirst($venta['metodo_pago']) . "\n");
    $printer->text(str_repeat('-', 32) . "\n");

    // Líneas de producto
    foreach ($venta['lineas'] as $l) {
        $nombre = $l['nombre_producto'] ?: $l['codigo_producto'] ?: ('ID ' . $l['id_producto']);
        $nombreLinea = mb_substr($nombre, 0, 22);
        $qty    = (int)$l['cantidad'];
        $precio = number_format($l['precio_unitario'], 2, ',', '');
        $total  = number_format($l['total_linea'], 2, ',', '');

        $printer->text($nombreLinea . "\n");
        $printer->text(
            str_pad($qty . " x " . $precio, 20) .
            str_pad($total, 12, ' ', STR_PAD_LEFT) . "\n"
        );

        if (!empty($l['numeros_serie'])) {
            $printer->text("  SN: " . $l['numeros_serie'] . "\n");
        }
        if (!empty($l['devuelta'])) {
            $printer->text("  ** DEVUELTO **\n");
        }
    }

    $printer->text(str_repeat('-', 32) . "\n");

    // Totales
    $printer->setEmphasis(true);
    $printer->text("Subtotal : " . number_format($venta['subtotal'], 2, ',', '') . " €\n");
    if (isset($venta['descuento_amt']) && (float)$venta['descuento_amt'] > 0) {
        $printer->text("Descuento: -" . number_format($venta['descuento_amt'], 2, ',', '') . " €\n");
    }
    $printer->text("IVA      : " . number_format($venta['iva_amt'], 2, ',', '') . " €\n");
    $printer->text("TOTAL    : " . number_format($venta['total'], 2, ',', '') . " €\n");
    $printer->setEmphasis(false);

    $printer->feed(2);
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("Gracias por su compra\n");
    $printer->feed(3);
    $printer->cut();
    $printer->close();

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

