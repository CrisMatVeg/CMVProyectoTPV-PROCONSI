<?php
/**
 * Clase BackupPDO
 * 
 * Gestiona la exportación de la base de datos a formato SQL.
 */
class BackupPDO {
    
    /**
     * Genera un volcado SQL de la base de datos y lo envía al navegador para descarga.
     */
    public static function descargarBackup() {
        require_once __DIR__ . '/../config/confDBPDO.php';
        
        $host = HOST;
        $db = DBNAME;
        $user = USERNAME;
        $pass = PASSWORD;
        $filename = "backup_" . $db . "_" . date("Y-m-d_H-i-s") . ".sql";
        
        // Usamos mysqldump disponible en el PATH del sistema para entornos de producción
        $mysqldumpPath = 'mysqldump';        
        // Construir comando
        // Nota: Si hay contraseña, se añade -p, pero si está vacía no se pone nada tras -p
        $auth = "-u $user";
        if (!empty($pass)) {
            $auth .= " -p$pass";
        }
        
        $command = "\"$mysqldumpPath\" $auth --host=$host $db";
        
        // Configurar cabeceras para descarga
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Ejecutar comando y enviar salida directamente
        // Usamos passthru para evitar cargar todo el dump en memoria PHP
        passthru($command, $returnVar);
        
        if ($returnVar !== 0) {
            // Si hubo error, podrías querer manejarlo, pero en flujo de descarga es difícil
            // después de haber enviado cabeceras.
        }
    }
}
