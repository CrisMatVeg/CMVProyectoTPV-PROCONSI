<?php
require_once __DIR__ . '/DBPDO.php';

class ConfiguracionPDO
{

    /**
     * Métodos de seguridad para cifrar credenciales sensibles en la BD.
     */
    private static function encrypt($data) {
        if (empty($data) || strpos($data, 'ENC:') === 0) return $data;
        $key = hash('sha256', defined('DBNAME') ? DBNAME : 'tpv_electrobazar_secret', true);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return 'ENC:' . bin2hex($iv) . ':' . $encrypted;
    }

    private static function decrypt($data) {
        if (empty($data) || strpos($data, 'ENC:') !== 0) return $data;
        $parts = explode(':', $data);
        if (count($parts) === 3) {
            $iv = hex2bin($parts[1]);
            $key = hash('sha256', defined('DBNAME') ? DBNAME : 'tpv_electrobazar_secret', true);
            $decrypted = openssl_decrypt($parts[2], 'aes-256-cbc', $key, 0, $iv);
            return $decrypted !== false ? $decrypted : $data;
        }
        return $data;
    }

    /**
     * Obtiene todos los parámetros de configuración como un array asociativo [clave => valor]
     * @return array
     */
    public static function obtenerConfiguracion()
    {
        $config = [];
        $sql = "SELECT clave, valor FROM configuracion";
        $result = DBPDO::ejecutarConsulta($sql);

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $val = $row['valor'];
            if ($row['clave'] === 'smtp_pass') $val = self::decrypt($val);
            $config[$row['clave']] = $val;
        }

        return $config;
    }

    /**
     * Obtiene el valor de una configuración específica
     * @param string $clave
     * @return string|null
     */
    public static function obtenerValor($clave)
    {
        $sql = "SELECT valor FROM configuracion WHERE clave = :clave";
        $result = DBPDO::ejecutarConsulta($sql, [':clave' => $clave]);
        $row = $result->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return ($clave === 'smtp_pass') ? self::decrypt($row['valor']) : $row['valor'];
    }

    /**
     * Actualiza múltiples configuraciones a la vez
     * @param array $configuraciones Array asociativo [clave => valor]
     * @return boolean True si tuvo éxito
     */
    public static function guardarConfiguracion($configuraciones)
    {
        if (empty($configuraciones)) return true;

        $db = DBPDO::getPDO();
        $db->beginTransaction();

        try {
            // Usamos INSERT ... ON DUPLICATE KEY UPDATE para manejar claves que no existen todavía
            $sql = "INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor) 
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
            $stmt = $db->prepare($sql);
 
            foreach ($configuraciones as $clave => $valor) {
                // Solo guardar si la clave no está vacía
                if (!empty($clave)) {
                    $valGuardar = ($clave === 'smtp_pass') ? self::encrypt($valor) : $valor;
                    $stmt->execute([':valor' => $valGuardar, ':clave' => $clave]);
                }
            }
 
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error al guardar la configuración: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza una única configuración (Shorthand para guardarConfiguracion)
     */
    public static function actualizarValor($clave, $valor)
    {
        return self::guardarConfiguracion([$clave => $valor]);
    }
}
