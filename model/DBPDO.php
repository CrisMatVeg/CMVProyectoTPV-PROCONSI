<?php
/**
 * Clase: DBPDO
 *
 * Proporciona métodos estáticos para ejecutar consultas PDO en la base de datos.
 *
 * @package Modelos
 * @author Cristian Mateos
 * @version 1.0
 */

require_once __DIR__ . '/../config/confDBPDO.php';

class DBPDO {

    /**
     * Ejecuta una consulta SQL y devuelve el PDOStatement.
     * No realiza fetch ni interpreta resultados.
     *
     * @param string $sql Consulta SQL a ejecutar
     * @param array|null $parametros Parámetros para consultas preparadas
     * @return PDOStatement Objeto PDOStatement ya ejecutado
     */
    public static function ejecutarConsulta($sql, $parametros = null) {
        try {
            $conexion = new PDO(DSN, USERNAME, PASSWORD);
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            date_default_timezone_set('Europe/Madrid');
            $conexion->exec("SET time_zone = '+01:00'");
            
            $consulta = $conexion->prepare($sql);

            // Ejecutar con o sin parámetros
            if ($parametros != null) {
                $consulta->execute($parametros);
            } else {
                $consulta->execute();
            }

            return $consulta;

        } catch (PDOException $e) {
            // Guardar error en sesión y redirigir a la página de error
            $_SESSION['paginaAnterior'] = $_SESSION['paginaEnCurso'] ?? '';
            $_SESSION['paginaEnCurso'] = 'error';
            $_SESSION['error'] = new AppError(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $_SESSION['paginaAnterior']
            );
            header('Location: index.php');
            exit;
        }
    }
}
?>