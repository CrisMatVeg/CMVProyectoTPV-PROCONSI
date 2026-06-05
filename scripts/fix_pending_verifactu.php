<?php
/**
 * Script: fix_pending_verifactu.php
 * Regenera el XML y el Hash de todos los registros pendientes o con error técnico.
 */

require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/VentaPDO.php';

echo "Iniciando regeneración de registros VeriFactu...\n";

// Buscar registros que no se han enviado con éxito (Limitamos a los últimos 500 para evitar bloqueos)
$sql = "SELECT id, numero_ticket, estado_envio_aeat FROM ventas WHERE estado_envio_aeat != 'enviado' ORDER BY id ASC LIMIT 500";
$q = DBPDO::ejecutarConsulta($sql);
$ventas = $q->fetchAll(PDO::FETCH_ASSOC);

$total = count($ventas);
echo "Encontrados $total registros para revisar.\n";

foreach ($ventas as $index => $v) {
    $id = (int)$v['id'];
    $ticket = $v['numero_ticket'];
    
    echo "[$index/$total] Procesando Ticket $ticket (ID: $id)... ";
    
    try {
        // Obtenemos los datos de la venta para regenerar
        $sqlV = "SELECT * FROM ventas WHERE id = :id";
        $qV = DBPDO::ejecutarConsulta($sqlV, [':id' => $id]);
        $datos = $qV->fetch(PDO::FETCH_ASSOC);
        
        if (!$datos) {
            echo "ERROR: Venta no encontrada.\n";
            continue;
        }

        // Borrar entrada previa en la cola para evitar duplicados
        DBPDO::ejecutarConsulta("DELETE FROM cola_envios WHERE id_venta = :id", [':id' => $id]);

        // Regenerar el registro (esto recalcula Hash y crea nuevo XML)
        // Usamos los datos originales pero forzamos la fecha al formato ISO que acabamos de estandarizar
        $fechaIso = date('Y-m-d', strtotime($datos['fecha']));
        $esFactura = (bool)$datos['es_factura'];
        $tipo = ($esFactura ? 'F1' : 'F2');
        if (str_starts_with($ticket, 'A-')) $tipo = ($esFactura ? 'R1' : 'R5');
        
        // Llamada a procesarVeriFactu (que ahora usa el nuevo hash)
        // Nota: procesarVeriFactu necesita una conexión PDO activa
        $db = DBPDO::getPDO();
        
        // Refactor: Necesitamos llamar a procesarVeriFactu pero con los datos correctos
        // Para simplificar, usamos un método que acabo de añadir (o voy a añadir)
        
        VentaPDO::regenerarRegistroVeriFactu($id);
        
        echo "OK (XML y Hash regenerados).\n";
    } catch (Exception $e) {
        echo "FALLO: " . $e->getMessage() . "\n";
    }
}

echo "\nProceso finalizado. Intentando enviar cola...\n";
require_once __DIR__ . '/../model/AeatQueueService.php';
(new AeatQueueService())->procesarCola(true, 50);
echo "Envío completado.\n";
