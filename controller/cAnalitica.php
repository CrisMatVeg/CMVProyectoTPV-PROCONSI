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

// Solo administradores
if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Navegación
if (isset($_REQUEST['volver']) || isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irTPV'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPrivado';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irMiPerfil'])) {
    $_SESSION['paginaEnCurso'] = 'MiPerfil';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irCierreCaja'])) {
    $_SESSION['paginaEnCurso'] = 'cierreCaja';
    header('Location: index.php');
    exit;
}

// Carga del modelo 
require_once 'model/VentaPDO.php';
require_once 'model/AnaliticaPDO.php';

// AJAX: Cargar componentes de forma asíncrona para optimizar rendimiento
if (isset($_GET['ajax'])) {
    $desde = $_GET['fechaDesde'] ?? date('Y-m-d', strtotime('-30 days'));
    $hasta = $_GET['fechaHasta'] ?? date('Y-m-d');
    $idUsuario = null; 
    $tipoDocumento = 'todos';

    try {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        switch ($_GET['ajax']) {
            case 'loadKPIs':
                $diffDias = (strtotime($hasta) - strtotime($desde)) / 86400;
                if ($diffDias > 30) {
                    echo json_encode(AnaliticaPDO::obtenerKPIsRapido($desde, $hasta));
                } else {
                    echo json_encode(VentaPDO::obtenerKPIs($desde, $hasta, $idUsuario, $tipoDocumento));
                }
                break;

            case 'loadCharts':
                $currentTs = strtotime($desde);
                $endTs = strtotime($hasta);
                $diffDays = ($endTs - $currentTs) / (60 * 60 * 24);

                $agrupacion = 'dia';
                if ($diffDays > 730) $agrupacion = 'año';
                elseif ($diffDays > 90) $agrupacion = 'mes';

                // Usar pre-calculados para rangos largos (> 30 días)
                if ($diffDays > 30) {
                    $data = [
                        'evolucion'    => AnaliticaPDO::obtenerEvolucionRapida($desde, $hasta, $agrupacion),
                        'categorias'   => AnaliticaPDO::obtenerCategoriasRapido($desde, $hasta),
                        'topProductos' => AnaliticaPDO::obtenerTopProductosRapido($desde, $hasta, 10),
                        'agrupacion'   => $agrupacion
                    ];
                } else {
                    $data = [
                        'evolucion'    => VentaPDO::obtenerMargenesDetallados($desde, $hasta, $idUsuario, $tipoDocumento, $agrupacion),
                        'categorias'   => VentaPDO::obtenerVentasPorCategoria($desde, $hasta, $idUsuario, $tipoDocumento),
                        'topProductos' => VentaPDO::obtenerTopProductos($desde, $hasta, 10, $idUsuario, $tipoDocumento),
                        'agrupacion'   => $agrupacion
                    ];

                    // Rellenar días vacíos para la evolución (Solo para rangos cortos <= 30 días con agrupación diaria)
                    if ($agrupacion === 'dia') {
                        $margenesPad = [];
                        $margenesMap = [];
                        foreach ($data['evolucion'] as $row) {
                            $margenesMap[$row['fecha']] = $row;
                        }
                        while ($currentTs <= $endTs) {
                            $dateYmd = date('Y-m-d', $currentTs);
                            $margenesPad[] = $margenesMap[$dateYmd] ?? [
                                'fecha' => $dateYmd,
                                'ingresos' => 0,
                                'costes' => 0,
                                'beneficio' => 0
                            ];
                            $currentTs = strtotime('+1 day', $currentTs);
                        }
                        $data['evolucion'] = $margenesPad;
                    }
                }
                echo json_encode($data);
                break;

            case 'loadIVA':
                $diffDias = (strtotime($hasta) - strtotime($desde)) / 86400;
                if ($diffDias > 30) {
                    echo json_encode(AnaliticaPDO::obtenerDesgloseIVARapido($desde, $hasta));
                } else {
                    echo json_encode(VentaPDO::obtenerDesgloseIVA($desde, $hasta, $idUsuario, $tipoDocumento));
                }
                break;

            case 'loadRanking':
                // Validar que las fechas tengan formato coherente antes de procesar
                if (!strtotime($desde) || !strtotime($hasta)) {
                    echo json_encode([]);
                    break;
                }

                $limit  = max(1, min(200, (int)($_GET['limit'] ?? 50)));
                $offset = max(0, (int)($_GET['offset'] ?? 0));
                
                $diffDias = (strtotime($hasta) - strtotime($desde)) / 86400;
                $results = [];

                if ($diffDias > 30) {
                    $results = AnaliticaPDO::obtenerRankingRapido($desde, $hasta, $limit, $offset);
                } else {
                    $results = VentaPDO::obtenerRankingCompletoProductos($desde, $hasta, $limit, $offset, $idUsuario, $tipoDocumento);
                }
                
                echo json_encode($results ?: []);
                break;

            case 'checkHealth':
                echo json_encode(['optimized' => VentaPDO::estanIndicesListos()]);
                break;

            case 'runOptimization':
                set_time_limit(300); 
                $step = $_GET['step'] ?? 'all';
                $success = false;
                if ($step === 'step1') {
                    try { DBPDO::ejecutarConsulta("ALTER TABLE ventas ADD INDEX idx_ventas_fecha (fecha)"); $success = true; } catch (Exception $e) { $success = true; }
                } elseif ($step === 'step2') {
                    try { DBPDO::ejecutarConsulta("ALTER TABLE ventas ADD INDEX idx_ventas_perf (estado, metodo_pago, fecha)"); $success = true; } catch (Exception $e) { $success = true; }
                } elseif ($step === 'step3') {
                    try { DBPDO::ejecutarConsulta("ALTER TABLE productos ADD INDEX idx_prod_cat_ref (categoria, referencia)"); $success = true; } catch (Exception $e) { $success = true; }
                } else {
                    $success = VentaPDO::optimizarIndices();
                }
                echo json_encode(['success' => $success]);
                break;
        }
    } catch (Throwable $e) {
        if (ob_get_length()) ob_clean();
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]);
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
