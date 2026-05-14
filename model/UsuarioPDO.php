<?php

/**
 * Clase: UsuarioPDO
 * Gestiona la persistencia de los usuarios mediante DBPDO.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
require_once __DIR__ . '/DBPDO.php';
require_once __DIR__ . '/Usuario.php';

class UsuarioPDO
{

    /**
     * Valida el acceso de un usuario.
     * @param string $login Nombre de usuario (login)
     * @param string $password Contraseña en texto plano
     * @return Usuario|null Devuelve el objeto Usuario si es válido, null en caso contrario.
     */
    public static function validarUsuario($login, $password = null, $soloActivos = true)
    {
        if ($password != null) {
            // Usamos SHA2 de MySQL para compatibilidad con el script de creación
            $sql = "SELECT u.*, r.nombre as rol 
                    FROM usuarios u 
                    LEFT JOIN roles r ON u.id_rol = r.id 
                    WHERE u.login = :login AND u.password = SHA2(:password,256)";
            $consulta = DBPDO::ejecutarConsulta($sql, [':login' => $login, ':password' => $password]);
        } else {
            $sql = "SELECT u.*, r.nombre as rol 
                    FROM usuarios u 
                    LEFT JOIN roles r ON u.id_rol = r.id 
                    WHERE u.login = :login";
            $consulta = DBPDO::ejecutarConsulta($sql, [':login' => $login]);
        }

        $objetoResultado = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$objetoResultado) {
            return null;
        }

        if ($soloActivos && !$objetoResultado['activo']) {
            return null;
        }

        // Normalización de rol (Legacy compatibility)
        $rol = $objetoResultado['rol'] ?? '';
        if (is_null($rol)) $rol = ''; 
        if (strtolower($rol) === 'administrador') {
            $rol = 'admin';
        }

        return new Usuario(
            $objetoResultado['id'],
            $objetoResultado['nombre'],
            $objetoResultado['login'],
            $objetoResultado['password'],
            $rol,
            $objetoResultado['activo'],
            $objetoResultado['id_rol'] ?? null,
            $objetoResultado['email'] ?? null,
            $objetoResultado['idioma'] ?? 'es',
            $objetoResultado['theme_font'] ?? 'dm-mono'
        );
    }

    /**
     * Devuelve todos los usuarios del sistema.
     * @return array
     */
    public static function listarUsuarios(): array
    {
        $sql = "SELECT u.*, r.nombre as rol 
                FROM usuarios u 
                LEFT JOIN roles r ON u.id_rol = r.id 
                ORDER BY u.activo DESC, u.nombre ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        $usuarios = [];
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            // Normalización de rol (Legacy compatibility)
            $rol = $row['rol'] ?? '';
            if (strtolower($rol) === 'administrador') {
                $rol = 'admin';
            }

            $usuarios[] = new Usuario(
                $row['id'],
                $row['nombre'],
                $row['login'],
                $row['password'],
                $rol,
                $row['activo'],
                $row['id_rol'] ?? null,
                $row['email'] ?? null,
                $row['idioma'] ?? 'es',
                $row['theme_font'] ?? 'dm-mono'
            );
        }
        return $usuarios;
    }

    /**
     * Añade un nuevo usuario.
     */
    public static function agregarUsuario($nombre, $login, $password, $rol, $idRol = null, $email = null)
    {
        $sql = "INSERT INTO usuarios (nombre, login, password, id_rol, email, idioma) 
                VALUES (:nombre, :login, SHA2(:pass,256), :idrol, :email, 'es')";
        $params = [
            ':nombre' => $nombre,
            ':login'   => $login,
            ':pass'   => $password,
            ':idrol'  => $idRol,
            ':email'  => $email
        ];
        return DBPDO::ejecutarConsulta($sql, $params);
    }

    /**
     * Cambia el idioma del usuario.
     */
    public static function cambiarIdioma($id, $idioma)
    {
        $sql = "UPDATE usuarios SET idioma = :idioma WHERE id = :id";
        return DBPDO::ejecutarConsulta($sql, [':id' => $id, ':idioma' => $idioma]);
    }

    /**
     * Edita los datos básicos de un usuario.
     */
    public static function editarUsuario($id, $nombre, $email)
    {
        $sql = "UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id";
        return DBPDO::ejecutarConsulta($sql, [
            ':id' => $id,
            ':nombre' => $nombre,
            ':email' => $email
        ]);
    }

    /**
     * Actualiza solo el rol de un usuario.
     */
    /**
     * Actualiza el rol de un usuario.
     */
    public static function editarRol($id, $nuevoRol, $idRol = null)
    {
        $sql = "UPDATE usuarios SET id_rol = :idrol WHERE id = :id";
        return DBPDO::ejecutarConsulta($sql, [':idrol' => $idRol, ':id' => $id]);
    }

    /**
     * Cambia el estado Activo/Inactivo (Baja lógica).
     */
    public static function toggleEstatus(int $id): bool
    {
        $q = DBPDO::ejecutarConsulta("SELECT activo FROM usuarios WHERE id = :id", [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $nuevoEstado = $row['activo'] ? 0 : 1;

        DBPDO::ejecutarConsulta("UPDATE usuarios SET activo = :estado WHERE id = :id", [':id' => $id, ':estado' => $nuevoEstado]);
        return (bool)$nuevoEstado;
    }

    /**
     * Busca un usuario por su email.
     */
    public static function buscarPorEmail($email)
    {
        $sql = "SELECT * FROM usuarios WHERE email = :email";
        $consulta = DBPDO::ejecutarConsulta($sql, [':email' => $email]);
        $row = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new Usuario(
            $row['id'],
            $row['nombre'],
            $row['login'],
            $row['password'],
            '', // Rol no necesario aquí
            $row['activo'],
            $row['id_rol'],
            $row['email']
        );
    }

    /**
     * Guarda un token de recuperación.
     */
    public static function guardarTokenRecuperacion($id, $token, $expiracion)
    {
        $sql = "UPDATE usuarios SET token_recuperacion = :token, token_expiracion = :exp WHERE id = :id";
        return DBPDO::ejecutarConsulta($sql, [
            ':id' => $id,
            ':token' => $token,
            ':exp' => $expiracion
        ]);
    }

    /**
     * Valida un token de recuperación.
     */
    public static function validarToken($token)
    {
        $sql = "SELECT id FROM usuarios 
                WHERE token_recuperacion = :token 
                AND token_expiracion > NOW()";
        $consulta = DBPDO::ejecutarConsulta($sql, [':token' => $token]);
        $row = $consulta->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['id'] : null;
    }

    /**
     * Cambia la contraseña y limpia el token.
     */
    public static function cambiarPassword($id, $nuevaPassword)
    {
        $sql = "UPDATE usuarios 
                SET password = SHA2(:pass, 256), 
                    token_recuperacion = NULL, 
                    token_expiracion = NULL 
                WHERE id = :id";
        return DBPDO::ejecutarConsulta($sql, [
            ':id' => $id,
            ':pass' => $nuevaPassword
        ]);
    }
}
