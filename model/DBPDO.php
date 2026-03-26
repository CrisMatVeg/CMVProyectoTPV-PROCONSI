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
require_once __DIR__ . '/AppError.php';

class DBPDO
{
    private static $instancia = null;

    /**
     * Devuelve una conexión PDO activa (Patrón Singleton)
     *
     * @return PDO
     */
    public static function getPDO()
    {
        if (self::$instancia === null) {
            try {
                self::$instancia = new PDO(DSN, USERNAME, PASSWORD);
                self::$instancia->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                date_default_timezone_set('Europe/Madrid');
                self::$instancia->exec("SET time_zone = '+01:00'");
            } catch (PDOException $e) {
                // Manejo de error de conexión inicial
                die("Error de conexión a la base de datos: " . $e->getMessage());
            }
        }
        return self::$instancia;
    }

    /**
     * Ejecuta una consulta SQL y devuelve el PDOStatement.
     * No realiza fetch ni interpreta resultados.
     *
     * @param string $sentenciaSQL Consulta SQL a ejecutar
     * @param array|null $parametros Parámetros para consultas preparadas
     * @return PDOStatement Objeto PDOStatement ya ejecutado
     */
    public static function ejecutarConsulta($sentenciaSQL, $parametros = null)
    {
        try {
            $consulta = self::getPDO()->prepare($sentenciaSQL);
            $consulta->execute($parametros);
            return $consulta;
        } catch (PDOException $e) {
            // Si es una petición API o AJAX, relanzamos la excepción para que el controlador la maneje (p.ej. devolver JSON)
            if (strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
                throw $e;
            }

            // Guardar error en sesión y redirigir a la página de error (flujo web normal)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
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
