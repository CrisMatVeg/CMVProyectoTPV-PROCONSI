<?php

/**
 * API: Estadísticas VeriFactu
 * Devuelve el estado de la cola de envíos para el panel administrativo.
 */

require_once __DIR__ . '/../model/Usuario.php';
session_start();
require_once __DIR__ . '/../model/AeatQueueService.php';

header('Content-Type: application/json');

try {
    // Seguridad: Solo admin
    if (!isset($_SESSION['usuarioActualTPV']) || $_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $service = new AeatQueueService();
    $accion = $_GET['accion'] ?? 'stats';

    switch ($accion) {
        case 'stats':
            $stats = $service->obtenerEstadisticas();
            $items = $service->listarUltimosMovimientos(20);
            echo json_encode(['ok' => true, 'stats' => $stats, 'items' => $items]);
            break;

        case 'resumen':
            $resumen = $service->obtenerResumenEstado();
            echo json_encode(['ok' => true, 'resumen' => $resumen]);
            break;

        case 'retry':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $service->reintentarEnvio($id);
                echo json_encode(['ok' => true]);
            } else {
                echo json_encode(['ok' => false, 'error' => 'ID no válido']);
            }
            break;

        case 'queue_data':
            // Devuelve stats + cola completa para el polling en tiempo real del log viewer
            $stats  = $service->obtenerEstadisticas();
            $resumen = $service->obtenerResumenEstado();
            $queue  = $service->listarColaPendiente();
            $logs   = $service->listarUltimosMovimientos(50);
            echo json_encode([
                'ok'     => true,
                'stats'  => $stats,
                'resumen'=> $resumen,
                'queue'  => $queue,
                'logs'   => $logs,
                'ts'     => time()
            ]);
            break;

        case 'heartbeat':
            // Procesa la cola si hay lotes listos (respetando el timer de 60s)
            $resultado = $service->procesarCola();
            $resumen   = $service->obtenerResumenEstado();
            echo json_encode(['ok' => true, 'procesado' => $resultado, 'resumen' => $resumen]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}

