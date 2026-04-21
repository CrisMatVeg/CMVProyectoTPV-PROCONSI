<?php

/**
 * Clase: AeatQueueService
 * Gestiona la cola de envíos a la AEAT para asegurar un comportamiento asíncrono.
 */

require_once __DIR__ . '/DBPDO.php';
require_once __DIR__ . '/AeatApi.php';
require_once dirname(__DIR__) . '/config/config.php';

class AeatQueueService
{
    private $maxIntentos = 5;

    /**
     * Encola un nuevo envío para una venta.
     */
    public function encolar(int $idVenta, string $xmlPath)
    {
        $sql = "INSERT INTO cola_envios (id_venta, xml_path, estado) 
                VALUES (:id_venta, :xml_path, 'pendiente') 
                ON DUPLICATE KEY UPDATE xml_path = VALUES(xml_path), estado = 'pendiente', intentos = 0";
        
        return DBPDO::ejecutarConsulta($sql, [
            ':id_venta' => $idVenta,
            ':xml_path' => $xmlPath
        ]);
    }

    /**
     * Registra un error de generación antes de encolar.
     */
    public function encolarError(int $idVenta, string $error)
    {
        $sql = "INSERT INTO cola_envios (id_venta, estado, ultimo_error) 
                VALUES (:id_venta, 'error', :error)
                ON DUPLICATE KEY UPDATE estado = 'error', ultimo_error = VALUES(ultimo_error)";
        
        return DBPDO::ejecutarConsulta($sql, [
            ':id_venta' => $idVenta,
            ':error' => $error
        ]);
    }

    /**
     * Procesa los envíos pendientes en la cola.
     * @return array Resumen del proceso [exitos => int, fallos => int]
     */
    public function procesarCola()
    {
        $api = new AeatApi();
        $sql = "SELECT * FROM cola_envios 
                WHERE estado = 'pendiente' 
                AND fecha_proximo_intento <= CURRENT_TIMESTAMP 
                ORDER BY creado_en ASC LIMIT 20";
        
        $result = DBPDO::ejecutarConsulta($sql);
        $resumen = ['exitos' => 0, 'fallos' => 0];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $xmlPath = $row['xml_path'];
            
            if (!file_exists($xmlPath)) {
                $this->marcarError($row['id'], "Archivo XML no encontrado: $xmlPath", true);
                $resumen['fallos']++;
                continue;
            }

            $xmlContent = file_get_contents($xmlPath);
            $respuesta = $api->enviarFactura($xmlContent);

            if ($respuesta['success']) {
                $this->marcarEnviado($row['id'], $row['id_venta'], $respuesta['raw_response'] ?? '');
                $resumen['exitos']++;
            } else {
                $esCritico = ($row['intentos'] + 1 >= $this->maxIntentos);
                $this->marcarError($row['id'], $respuesta['message'], $esCritico, $respuesta['raw_response'] ?? '');
                $resumen['fallos']++;
            }
        }

        return $resumen;
    }

    private function marcarEnviado(int $idCola, int $idVenta, string $respuestaAeat)
    {
        $sql = "UPDATE cola_envios 
                SET estado = 'enviado', 
                    intentos = intentos + 1,
                    respuesta_aeat = :resp,
                    fecha_envio = CURRENT_TIMESTAMP 
                WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':id' => $idCola,
            ':resp' => $respuestaAeat
        ]);

        // También actualizamos el estado en la tabla de ventas
        $sqlVenta = "UPDATE ventas SET estado_envio_aeat = 'enviado' WHERE id = :id_venta";
        DBPDO::ejecutarConsulta($sqlVenta, [':id_venta' => $idVenta]);
    }

    private function marcarError(int $idCola, string $error, bool $critico, string $respuestaAeat = '')
    {
        $estado = $critico ? 'error_critico' : 'pendiente';
        
        // Backoff exponencial simple: reintentar en 5 min, 15 min, 1h, 4h...
        $reintentos = [5, 15, 60, 240, 1440]; // minutos
        $sql = "SELECT intentos FROM cola_envios WHERE id = :id";
        $stmt = DBPDO::ejecutarConsulta($sql, [':id' => $idCola]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $idx = min($row['intentos'] ?? 0, count($reintentos) - 1);
        $minutos = $reintentos[$idx];

        $sql = "UPDATE cola_envios 
                SET estado = :estado, 
                    intentos = intentos + 1, 
                    ultimo_error = :error,
                    respuesta_aeat = :resp,
                    fecha_envio = CURRENT_TIMESTAMP,
                    fecha_proximo_intento = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL :minutos MINUTE)
                WHERE id = :id";
        
        DBPDO::ejecutarConsulta($sql, [
            ':estado' => $estado,
            ':error' => mb_strcut($error, 0, 1000), // Truncar si es muy largo
            ':resp' => $respuestaAeat,
            ':minutos' => $minutos,
            ':id' => $idCola
        ]);
    }

    /**
     * Obtiene estadísticas de la cola para el panel de control.
     */
    public function obtenerEstadisticas()
    {
        $sql = "SELECT 
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
                    SUM(CASE WHEN estado = 'error_critico' THEN 1 ELSE 0 END) as errores
                FROM cola_envios";
        $res = DBPDO::ejecutarConsulta($sql);
        return $res->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista los últimos movimientos de la cola.
     */
    public function listarUltimosMovimientos(int $limite = 20)
    {
        $limite = (int)$limite;
        $sql = "SELECT c.*, v.numero_ticket 
                FROM cola_envios c
                JOIN ventas v ON c.id_venta = v.id
                ORDER BY c.creado_en DESC LIMIT $limite";
        $res = DBPDO::ejecutarConsulta($sql);
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resetea un envío para que sea procesado de nuevo inmediatamente.
     */
    public function reintentarEnvio(int $idCola)
    {
        $sql = "UPDATE cola_envios 
                SET estado = 'pendiente', 
                    fecha_proximo_intento = CURRENT_TIMESTAMP 
                WHERE id = :id";
        return DBPDO::ejecutarConsulta($sql, [':id' => $idCola]);
    }

    public function enviarEspecifico(int $idVenta)
    {
        $api = new AeatApi();
        $sql = "SELECT id, id_venta, xml_path, intentos FROM cola_envios WHERE id_venta = :id_venta";
        $stmt = DBPDO::ejecutarConsulta($sql, [':id_venta' => $idVenta]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !file_exists($row['xml_path'])) return false;

        $xmlContent = file_get_contents($row['xml_path']);
        $respuesta = $api->enviarFactura($xmlContent);

        if ($respuesta['success']) {
            $this->marcarEnviado($row['id'], $row['id_venta'], $respuesta['raw_response'] ?? '');
            return true;
        } else {
            $esCritico = ($row['intentos'] + 1 >= $this->maxIntentos);
            $this->marcarError($row['id'], $respuesta['message'], $esCritico, $respuesta['raw_response'] ?? '');
            return false;
        }
    }
}
