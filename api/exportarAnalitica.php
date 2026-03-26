<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: exportarAnalitica.php
 * Genera reportes de analítica en diferentes formatos (CSV, JSON)
 */

ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/VentaPDO.php';

    // session_start(); // Handled by csrf_check.php

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? 'csv';
    $fechaDesde = $_POST['fechaDesde'] ?? $_GET['fechaDesde'] ?? date('Y-m-d', strtotime('-30 days'));
    $fechaHasta = $_POST['fechaHasta'] ?? $_GET['fechaHasta'] ?? date('Y-m-d');

    if (!strtotime($fechaDesde) || !strtotime($fechaHasta)) {
        echo json_encode(['ok' => false, 'error' => 'Fechas inválidas']);
        exit;
    }

    // KPIs
    $sql = "SELECT 
                COUNT(DISTINCT numero_ticket) as total_tickets,
                SUM(total) as total_ventas,
                SUM(total - base_imponible) as iva_recaudado,
                SUM(base_imponible) as base_total
            FROM ventas 
            WHERE DATE(fecha) BETWEEN :desde AND :hasta";
    
    $q = DBPDO::ejecutarConsulta($sql, [':desde' => $fechaDesde, ':hasta' => $fechaHasta]);
    $kpis = $q->fetch(PDO::FETCH_ASSOC);

    // Top Products
    $sqlTop = "SELECT 
                lv.nombre_producto,
                lv.codigo_producto,
                SUM(lv.cantidad) as unidades,
                SUM(lv.total_linea) as total_recaudado
            FROM lineas_venta lv
            JOIN ventas v ON lv.id_venta = v.id
            WHERE DATE(v.fecha) BETWEEN :desde AND :hasta
            GROUP BY lv.nombre_producto, lv.codigo_producto
            ORDER BY unidades DESC
            LIMIT 10";
    
    $qTop = DBPDO::ejecutarConsulta($sqlTop, [':desde' => $fechaDesde, ':hasta' => $fechaHasta]);
    $topProductos = $qTop->fetchAll(PDO::FETCH_ASSOC);

    // IVA Breakdown
    $sqlIva = "SELECT 
                ROUND(lv.iva_aplicado) as pct,
                SUM(lv.total_linea / (1 + lv.iva_aplicado/100)) as base,
                SUM(lv.total_linea - (lv.total_linea / (1 + lv.iva_aplicado/100))) as cuota,
                SUM(lv.total_linea) as total
            FROM lineas_venta lv
            JOIN ventas v ON lv.id_venta = v.id
            WHERE DATE(v.fecha) BETWEEN :desde AND :hasta
            GROUP BY ROUND(lv.iva_aplicado)
            ORDER BY pct DESC";
    
    $qIva = DBPDO::ejecutarConsulta($sqlIva, [':desde' => $fechaDesde, ':hasta' => $fechaHasta]);
    $desgloseIva = $qIva->fetchAll(PDO::FETCH_ASSOC);

    if ($action === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'periodo' => ['desde' => $fechaDesde, 'hasta' => $fechaHasta],
            'kpis' => $kpis,
            'topProductos' => $topProductos,
            'desgloseIva' => $desgloseIva,
            'generado' => date('Y-m-d H:i:s')
        ]);
    } else {
        // CSV format
        $csv = "REPORTE DE ANALÍTICA DE VENTAS\n";
        $csv .= "Período: {$fechaDesde} a {$fechaHasta}\n";
        $csv .= "Generado: " . date('d/m/Y H:i:s') . "\n\n";

        $csv .= "KPIs PRINCIPALES\n";
        $csv .= "Concepto,Valor\n";
        $csv .= "Total Ventas," . number_format($kpis['total_ventas'] ?? 0, 2, '.', '') . "\n";
        $csv .= "Base Imponible," . number_format($kpis['base_total'] ?? 0, 2, '.', '') . "\n";
        $csv .= "IVA Recaudado," . number_format($kpis['iva_recaudado'] ?? 0, 2, '.', '') . "\n";
        $csv .= "Número de Tickets," . ($kpis['total_tickets'] ?? 0) . "\n\n";

        $csv .= "TOP 10 PRODUCTOS MÁS VENDIDOS\n";
        $csv .= "Posición,Producto,Código,Unidades,Ingresos\n";
        foreach ($topProductos as $idx => $p) {
            $csv .= ($idx + 1) . ",\"" . addslashes($p['nombre_producto']) . "\",\"" . $p['codigo_producto'] . "\"," . $p['unidades'] . "," . number_format($p['total_recaudado'], 2, '.', '') . "\n";
        }

        $csv .= "\n";

        $csv .= "DESGLOSE DE IVA (FISCAL)\n";
        $csv .= "Tipo IVA,Base Imponible,Cuota IVA,Total Recaudado\n";
        $totalBase = 0; $totalCuota = 0; $totalFinal = 0;
        foreach ($desgloseIva as $iva) {
            $csv .= $iva['pct'] . "%," . number_format($iva['base'], 2, '.', '') . "," . number_format($iva['cuota'], 2, '.', '') . "," . number_format($iva['total'], 2, '.', '') . "\n";
            $totalBase += $iva['base'];
            $totalCuota += $iva['cuota'];
            $totalFinal += $iva['total'];
        }
        $csv .= "TOTAL,". number_format($totalBase, 2, '.', '') . "," . number_format($totalCuota, 2, '.', '') . "," . number_format($totalFinal, 2, '.', '') . "\n";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="analitica_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo $csv;
        exit;
    }

} catch (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
