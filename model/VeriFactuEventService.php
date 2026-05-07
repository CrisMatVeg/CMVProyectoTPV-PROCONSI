<?php

/**
 * Clase: VeriFactuEventService
 * Gestiona el Registro de Eventos exigido por el Reglamento VeriFactu (Art. 12).
 * Registra eventos técnicos del sistema con integridad.
 */

require_once __DIR__ . '/DBPDO.php';

class VeriFactuEventService
{
    // Tipos de evento reglamentarios
    const EVENTO_INICIO_SISTEMA    = 'INSTALACION_INICIO';
    const EVENTO_CIERRE_SISTEMA    = 'INSTALACION_CIERRE';
    const EVENTO_ERROR_ALMACEN     = 'ERROR_ALMACENAMIENTO';
    const EVENTO_ERROR_ENCADENAR   = 'ERROR_ENCADENAMIENTO';
    const EVENTO_BACKUP_RESTORE    = 'RESTAURACION_COPIA_SEGURIDAD';
    const EVENTO_CONFIG_CHANGE     = 'CAMBIO_CONFIGURACION_FISCAL';
    const EVENTO_ACCESO_NO_AUTOR   = 'ACCESO_NO_AUTORIZADO';

    /**
     * Registra un evento técnico en el log de VeriFactu.
     * 
     * @param string $tipo Tipo de evento (usar constantes)
     * @param string $descripcion Detalle legible
     * @param array|null $detalles Datos técnicos adicionales
     */
    public static function registrarEvento(string $tipo, string $descripcion, array $detalles = null): bool
    {
        try {
            $detallesJson = $detalles ? json_encode($detalles, JSON_UNESCAPED_UNICODE) : null;

            // En una implementación avanzada, aquí calcularíamos un hash encadenado 
            // de eventos, similar a las facturas. Por ahora, aseguramos la persistencia.
            
            $sql = "INSERT INTO verifactu_eventos (tipo_evento, descripcion, detalles_json)
                    VALUES (:tipo, :desc, :detalles)";
            
            DBPDO::ejecutarConsulta($sql, [
                ':tipo'     => $tipo,
                ':desc'     => $descripcion,
                ':detalles' => $detallesJson
            ]);

            return true;
        } catch (Exception $e) {
            // Si falla el log de eventos, es un error crítico de almacenamiento
            error_log("CRITICAL: Fallo al registrar evento VeriFactu ($tipo): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper para registrar el inicio del sistema.
     */
    public static function logStartup()
    {
        self::registrarEvento(self::EVENTO_INICIO_SISTEMA, "El sistema TPV ha sido iniciado.");
    }

    /**
     * Helper para registrar cambios en la configuración.
     */
    public static function logConfigChange(string $campo, $valorAnterior, $valorNuevo)
    {
        self::registrarEvento(self::EVENTO_CONFIG_CHANGE, "Cambio en configuración fiscal: $campo", [
            'campo' => $campo,
            'old'   => $valorAnterior,
            'new'   => $valorNuevo
        ]);
    }
}
