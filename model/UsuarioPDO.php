<?php
/**
 * Clase: UsuarioPDO
 * Gestiona la persistencia de los usuarios mediante DBPDO.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
require_once 'DBPDO.php';
require_once 'Usuario.php';

class UsuarioPDO {

    /**
     * Valida el acceso de un usuario.
     * @param string $login Nombre de usuario (login)
     * @param string $password Contraseña en texto plano
     * @return Usuario|null Devuelve el objeto Usuario si es válido, null en caso contrario.
     */
    public static function validarUsuario($login, $password=null) {
        if($password!=null){
            // Usamos SHA2 de MySQL para compatibilidad con el script de creación
            $sql = "SELECT * FROM usuarios WHERE login = :login AND password = SHA2(:password,256)";
            $consulta = DBPDO::ejecutarConsulta($sql, [':login' => $login, ':password' => $password]);
        }else{
            $sql = "SELECT * FROM usuarios WHERE login = :login";
            $consulta = DBPDO::ejecutarConsulta($sql, [':login' => $login]);
        }
        
        $objetoResultado = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$objetoResultado || !$objetoResultado['activo']) {
            return null; // No loguear si está inactivo
        }

        return new Usuario(
            $objetoResultado['id'],
            $objetoResultado['nombre'],
            $objetoResultado['login'],
            $objetoResultado['password'],
            $objetoResultado['rol'],
            $objetoResultado['activo']
        );
    }

    /**
     * Devuelve todos los usuarios del sistema.
     * @return array
     */
    public static function listarUsuarios(): array {
        $sql = "SELECT * FROM usuarios ORDER BY activo DESC, nombre ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        $usuarios = [];
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $usuarios[] = new Usuario(
                $row['id'],
                $row['nombre'],
                $row['login'],
                $row['password'],
                $row['rol'],
                $row['activo']
            );
        }
        return $usuarios;
    }

    /**
     * Añade un nuevo usuario.
     */
    public static function añadirUsuario($nombre, $login, $password, $rol) {
        $sql = "INSERT INTO usuarios (nombre, login, password, rol) 
                VALUES (:nombre, :login, SHA2(:pass,256), :rol)";
        $params = [
            ':nombre' => $nombre,
            ':login'   => $login,
            ':pass'   => $password,
            ':rol'    => $rol
        ];
        return DBPDO::ejecutarConsulta($sql, $params);
    }

    /**
     * Actualiza solo el rol de un usuario.
     */
    public static function editarRol($id, $nuevoRol) {
        $sql = "UPDATE usuarios SET rol = :rol WHERE id = :id";
        return DBPDO::ejecutarConsulta($sql, [':rol' => $nuevoRol, ':id' => $id]);
    }

    /**
     * Cambia el estado Activo/Inactivo (Baja lógica).
     */
    public static function toggleEstatus($id) {
        $sql = "SELECT activo FROM usuarios WHERE id = :id";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $nuevoEstado = $row['activo'] ? 0 : 1;

        $sqlToggle = "UPDATE usuarios SET activo = :estado WHERE id = :id";
        return DBPDO::ejecutarConsulta($sqlToggle, [':id' => $id, ':estado' => $nuevoEstado]);
    }
}