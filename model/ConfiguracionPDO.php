<?php
require_once 'DBPDO.php';

class ConfiguracionPDO
{

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
            $config[$row['clave']] = $row['valor'];
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

        return $row ? $row['valor'] : null;
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
            $sql = "UPDATE configuracion SET valor = :valor WHERE clave = :clave";
            $stmt = $db->prepare($sql);

            foreach ($configuraciones as $clave => $valor) {
                $stmt->execute([':valor' => $valor, ':clave' => $clave]);
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error al guardar la configuración: " . $e->getMessage());
            return false;
        }
    }
}
