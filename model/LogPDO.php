<?php
/**
 * Modelo para gestionar los logs del sistema
 * 
 * @author Antigravity
 */
class LogPDO {

    /**
     * Añade un nuevo log al sistema
     * 
     * @param string $accion El tipo de acción (LOGIN, VENTA, etc)
     * @param string $descripcion Descripción legible de la acción
     * @param mixed $detalles Datos adicionales en formato array u objeto
     * @return bool
     */
    public static function addLog($accion, $descripcion, $detalles = null) {
        try {
            // Obtener datos del usuario de la sesión si existen
            $idUsuario = null;
            $nombreUsuario = 'Sistema/Anónimo';

            if (isset($_SESSION['usuarioActualTPV'])) {
                $idUsuario = $_SESSION['usuarioActualTPV']->getId();
                $nombreUsuario = $_SESSION['usuarioActualTPV']->getNombre();
            }

            $detallesJson = $detalles ? json_encode($detalles, JSON_UNESCAPED_UNICODE) : null; // columna nullable en BD

            $sql = "INSERT INTO logs_sistema (id_usuario, nombre_usuario, accion, descripcion, detalles_json) 
                    VALUES (:id_usuario, :nombre_usuario, :accion, :descripcion, :detalles_json)";
            
            $resultado = DBPDO::ejecutarConsulta($sql, [
                ':id_usuario' => $idUsuario,
                ':nombre_usuario' => $nombreUsuario,
                ':accion' => $accion,
                ':descripcion' => $descripcion,
                ':detalles_json' => $detallesJson
            ]);

            return $resultado->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Obtiene los logs filtrados por fecha
     * 
     * @param string $fechaInicio Fecha de inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha de fin (YYYY-MM-DD)
     * @return array
     */
    public static function getLogs($fechaInicio = null, $fechaFin = null) {
        try {
            $sql = "SELECT id, fecha_hora, nombre_usuario, accion, descripcion, detalles_json 
                    FROM logs_sistema WHERE 1=1";
            $parametros = [];

            if ($fechaInicio) {
                $sql .= " AND fecha_hora >= :fechaInicio";
                $parametros[':fechaInicio'] = $fechaInicio . " 00:00:00";
            }

            if ($fechaFin) {
                $sql .= " AND fecha_hora <= :fechaFin";
                $parametros[':fechaFin'] = $fechaFin . " 23:59:59";
            }

            $sql .= " ORDER BY fecha_hora DESC LIMIT 5000";

            $resultado = DBPDO::ejecutarConsulta($sql, $parametros);
            return $resultado->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Exporta los logs a un archivo CSV
     */
    public static function exportToCSV($logs) {
        $filename = "logs_sistema_" . date("Ymd_His") . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeceras
        fputcsv($output, ['ID', 'Fecha/Hora', 'Usuario', 'Acción', 'Descripción', 'Detalles']);
        
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['fecha_hora'],
                $log['nombre_usuario'],
                $log['accion'],
                $log['descripcion'],
                $log['detalles_json']
            ]);
        }
        
        fclose($output);
        exit;
    }
}