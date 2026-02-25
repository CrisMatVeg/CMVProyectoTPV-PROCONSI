<?php
/**
 * Clase: UsuarioPDO
 * * Gestiona la persistencia de los usuarios mediante DBPDO.
 * * @package Modelos
 * @author Tu Nombre
 */
require_once 'DBPDO.php';
require_once 'Usuario.php';

class UsuarioPDO {

    /**
     * Valida el acceso de un usuario.
     * * @param string $username Nombre de usuario
     * @param string $password Contraseña en texto plano
     * @return Usuario|null Devuelve el objeto Usuario si es válido, null en caso contrario.
     */
    public static function validarUsuario($username, $password=null) {
        if($password!=null){
            $sql = "SELECT * FROM usuarios WHERE username = :usuario AND password = SHA2(:password,256)";
            $consulta = DBPDO::ejecutarConsulta($sql, [':usuario' => $username, ':password' => $password]);
        }else{
            $sql = "SELECT * FROM usuarios WHERE T01_username = :usuario";
            $consulta = DBPDO::ejecutarConsulta($sql, [':usuario' => $username]);
        }
        
        $objetoResultado = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$objetoResultado) {
            return null;
        }

        return new Usuario(
            $objetoResultado['id'],
            $objetoResultado['nombre_completo'],
            $objetoResultado['username'],
            $objetoResultado['password'],
            $objetoResultado['rol'],
            $objetoResultado['activo']
        );
    }

    /**
     * Busca un usuario por su nombre de usuario (para comprobar si existe).
     */
    public static function buscarPorCodigo($username) {
        $sql = "SELECT * FROM usuarios WHERE username = ?";
        $resultado = DBPDO::ejecutarConsulta($sql, [$username]);

        if ($resultado->rowCount() > 0) {
            $datos = $resultado->fetch(PDO::FETCH_OBJ);
            return new Usuario(
                $datos->id,
                $datos->nombre_completo,
                $datos->username,
                $datos->password,
                $datos->rol,
                $datos->activo
            );
        }
        return null;
    }
}