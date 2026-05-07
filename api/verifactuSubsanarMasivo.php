<?php
/**
 * API: verifactuSubsanarMasivo.php
 * Procesa TODAS las incidencias de VeriFactu (Técnicas y Subsanaciones)
 * de forma masiva en el servidor, sin depender de la paginación de la UI.
 */

header('Content-Type: application/json');
set_time_limit(0); // Tiempo ilimitado para el proceso masivo
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/VentaPDO.php';
require_once __DIR__ . '/../model/AeatQueueService.php';

try {
    $db = DBPDO::getPDO();
    
    // 1. Obtener todas las ventas con incidencias
    // - error_critico: Rechazo total por la AEAT (requiere Alta Correctiva)
    // - subsanacion_pendiente: Aceptada con errores (requiere Subsanación S1)
    $sql = "SELECT v.id, v.nombre_cliente, v.nif_cliente, v.estado_envio_aeat, c.ultimo_error as aeat_error 
            FROM ventas v
            LEFT JOIN cola_envios c ON c.id = (SELECT MAX(id) FROM cola_envios WHERE id_venta = v.id)
            WHERE v.estado_envio_aeat IN ('error_critico', 'subsanacion_pendiente')
            ORDER BY v.id ASC"; // Importante: Orden cronológico
    
    $stmt = $db->query($sql);
    $incidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = count($incidencias);
    $procesados = 0;
    $errores = 0;
    $detalles = [];

    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);
    $logFile = $logDir . '/remediation.log';
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] INICIO PROCESO MASIVO: $total incidencias\n", FILE_APPEND);

    foreach ($incidencias as $inc) {
        $id = (int)$inc['id'];
        try {
            $st = $inc['estado_envio_aeat'];
            $err = $inc['aeat_error'] ?? '';
            
            file_put_contents($logFile, "  - Procesando ID $id ($st)... ", FILE_APPEND);

            // 1.1 FILTRO DE SEGURIDAD: Si ya se marcó como enviado (por otro proceso o reintento), saltar.
            $currentStatus = DBPDO::ejecutarConsulta("SELECT estado_envio_aeat FROM ventas WHERE id = ?", [$id])->fetchColumn();
            if ($currentStatus === 'enviado') {
                $procesados++;
                file_put_contents($logFile, "SALTADO (Ya enviado)\n", FILE_APPEND);
                continue;
            }

            // 1.2 FILTRO POR ANTIGÜEDAD (Registros previos al inicio de VeriFactu)
            $fechaVenta = DBPDO::ejecutarConsulta("SELECT fecha FROM ventas WHERE id = ?", [$id])->fetchColumn();
            if ($fechaVenta && strtotime($fechaVenta) < strtotime('2024-10-28')) {
                DBPDO::ejecutarConsulta("UPDATE ventas SET estado_envio_aeat = 'enviado' WHERE id = ?", [$id]);
                $procesados++;
                file_put_contents($logFile, "SALTADO (Antiguo)\n", FILE_APPEND);
                continue;
            }

            // Determinar si es un error técnico (archivo perdido) o fiscal
            $isLocalError = (strpos($err, 'Archivo XML no encontrado') !== false);
            $isFiscalError = (strpos($err, '1142') !== false);

            // 2. Ejecutar acción: Priorizar Subsanación Real para corregir la matemática
            if ($isFiscalError || !$isLocalError) {
                $rechazo = ($st === 'error_critico');
                VentaPDO::subsanarVenta($id, $inc['nombre_cliente'] ?? 'Cliente Contado', $inc['nif_cliente'] ?? '', $rechazo);
                file_put_contents($logFile, "SUBSANADO (Fiscal)\n", FILE_APPEND);
            } else {
                VentaPDO::regenerarVentaAlta($id);
                file_put_contents($logFile, "REGENERADO (Técnico)\n", FILE_APPEND);
            }
            
            $procesados++;

        } catch (Exception $e) {
            $errores++;
            $detalles[] = "ID $id: " . $e->getMessage();
            file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] FIN PROCESO: $procesados OK, $errores ERR\n\n", FILE_APPEND);

    // NOTA: No procesamos la cola aquí para evitar timeouts masivos. 
    // Las facturas quedan en 'pendiente' y se enviarán por el proceso de cola normal.

    echo json_encode([
        'ok' => true,
        'total' => $total,
        'procesados' => $procesados,
        'errores' => $errores,
        'detalles_errores' => $detalles,
        'msg' => "Se han preparado $procesados incidencias para su envío."
    ]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error crítico: ' . $e->getMessage()]);
}
