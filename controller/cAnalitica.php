<?php

/**
 * Controller: cAnalitica.php
 * Procesa los datos de analítica de ventas para el administrador.
 */

// Solo usuarios autenticados
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

// Solo administradores o gestores de analítica
if (!$_SESSION['usuarioActualTPV']->tienePermiso('ver_analitica')) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irCierreCaja'])) {
    $_SESSION['paginaEnCurso'] = 'cierreCaja';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['volver'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Carga del modelo 
require_once 'model/VentaPDO.php';
require_once 'model/AnaliticaPDO.php';

// AJAX: un único endpoint loadAll evita session staircase y reduce queries
if (isset($_GET['ajax'])) {
    @ini_set('display_errors', '0');
    session_write_close(); // Liberar lock de sesión; auth ya comprobado arriba
    set_time_limit(180);

    $desde = $_GET['fechaDesde'] ?? date('Y-m-d', strtotime('-30 days'));
    $hasta = $_GET['fechaHasta'] ?? date('Y-m-d');

    try {
        switch ($_GET['ajax']) {
            case 'loadAll':
                $diffDays   = (strtotime($hasta) - strtotime($desde)) / 86400;
                $agrupacion = 'dia';
                if ($diffDays > 730)     $agrupacion = 'año';
                elseif ($diffDays > 90)  $agrupacion = 'mes';

                // Caché de archivo para rangos > 30 días (los más lentos y menos volátiles).
                // Primera carga computa y guarda; recargas dentro del TTL sirven el JSON directo.
                $cacheFile = null;
                if ($diffDays > 30) {
                    $cacheDir  = dirname(__DIR__) . '/storage/analitica_cache/';
                    @mkdir($cacheDir, 0755, true);
                    $cacheFile = $cacheDir . md5("{$desde}:{$hasta}:{$agrupacion}") . '.json';
                    $ttl       = $diffDays > 365 ? 3600 : ($diffDays > 90 ? 600 : 300); // 1h para >1año, 10min para >90d, 5min resto
                    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
                        while (ob_get_level() > 0) @ob_end_clean();
                        header('Content-Type: application/json; charset=utf-8');
                        readfile($cacheFile);
                        break;
                    }
                }

                VentaPDO::asegurarIndices();
                $data = VentaPDO::obtenerTodoAnalitica($desde, $hasta, null, 'todos', $agrupacion);

                // Rellenar días vacíos solo en agrupación diaria
                if ($agrupacion === 'dia' && !empty($data['evolucion'])) {
                    $curTs = strtotime($desde);
                    $endTs = strtotime($hasta);
                    $pad   = [];
                    $mapa  = [];
                    foreach ($data['evolucion'] as $row) {
                        $mapa[$row['fecha']] = $row;
                    }
                    while ($curTs <= $endTs) {
                        $ymd   = date('Y-m-d', $curTs);
                        $pad[] = $mapa[$ymd] ?? ['fecha' => $ymd, 'ingresos' => 0, 'costes' => 0, 'beneficio' => 0];
                        $curTs = strtotime('+1 day', $curTs);
                    }
                    $data['evolucion'] = $pad;
                }

                while (ob_get_level() > 0) @ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                $jsonOut = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
                if ($cacheFile !== null) @file_put_contents($cacheFile, $jsonOut, LOCK_EX);
                echo $jsonOut;
                break;

            case 'runOptimization':
                set_time_limit(300);
                VentaPDO::asegurarIndices();
                VentaPDO::actualizarEstadisticas();
                while (ob_get_level() > 0) @ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
                break;
        }
    } catch (Throwable $e) {
        while (ob_get_level() > 0) @ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        $out = json_encode([
            'error'   => true,
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'file'    => basename($e->getFile()),
            'line'    => $e->getLine()
        ], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
        echo $out ?: '{"error":true,"message":"json_encode_failed"}';
    }
    exit;
}

// Filtros de fecha
$periodo       = $_REQUEST['periodo'] ?? 'mes';
$idCajero      = null;
$tipoDocumento = 'todos';

// Lógica de periodos (espejo de cHistorial.php)
switch ($periodo) {
    case 'hoy':
        $fechaDesdeRaw = date('Y-m-d');
        $fechaHastaRaw = date('Y-m-d');
        break;
    case 'semana':
        $fechaDesdeRaw = date('Y-m-d', strtotime('-7 days'));
        $fechaHastaRaw = date('Y-m-d');
        break;
    case 'mes':
        $fechaDesdeRaw = date('Y-m-d', strtotime('-30 days'));
        $fechaHastaRaw = date('Y-m-d');
        break;
    case 'todo':
        $fechaDesdeRaw = '2000-01-01';
        $fechaHastaRaw = date('Y-m-d');
        break;
    case 'personalizado':
    default:
        $fechaDesdeRaw = $_REQUEST['fechaDesde'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaHastaRaw = $_REQUEST['fechaHasta'] ?? date('Y-m-d');
        break;
}

$aErrores = [
    'fechaDesde' => validacionFormularios::validarFecha($fechaDesdeRaw, '2050-12-31', '2000-01-01', 0),
    'fechaHasta' => validacionFormularios::validarFecha($fechaHastaRaw, '2050-12-31', '2000-01-01', 0)
];

$fechaDesde = $aErrores['fechaDesde'] ? date('Y-m-d', strtotime('-30 days')) : $fechaDesdeRaw;
$fechaHasta = $aErrores['fechaHasta'] ? date('Y-m-d') : $fechaHastaRaw;

// Obtener datos iniciales mínimos
$avAnalitica = [
    'filtros' => [
        'periodo' => $periodo,
        'desde' => $fechaDesde,
        'hasta' => $fechaHasta,
        'cajero' => $idCajero,
        'tipoDocumento' => $tipoDocumento
    ],
    'aErrores' => $aErrores
];

// Cargar la vista
require_once $view['layout'];
