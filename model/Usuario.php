<?php

/**
 * Clase: Usuario
 * * Entidad que representa a un usuario del sistema TPV.
 * * @package Modelos
 * @author Cristian Mateos Vega
 */
class Usuario
{
    private $id;
    private $nombre;
    private $login;
    private $password;
    private $rol; // Nombre del rol (legacy)
    private $idRol; // ID del nuevo sistema de roles
    private $activo;
    private $email;
    private $idioma;
    private $themeFont;

    public function __construct($id, $nombre, $login, $password, $rol, $activo, $idRol = null, $email = null, $idioma = 'es', $themeFont = 'dm-mono')
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->login = $login;
        $this->password = $password;
        $this->rol = $rol;
        $this->idRol = $idRol;
        $this->activo = $activo;
        $this->email = $email;
        $this->idioma = $idioma;
        $this->themeFont = $themeFont;
    }

    // Getters
    public function getThemeFont()
    {
        return $this->themeFont;
    }
    public function getIdioma()
    {

        return $this->idioma;
    }
    public function getId()
    {
        return $this->id;
    }
    public function getNombre()
    {
        return $this->nombre;
    }
    public function getNombreCompleto()
    {
        return $this->nombre;
    } // Alias para compatibilidad
    public function getLogin()
    {
        return $this->login;
    }
    public function getUsername()
    {
        return $this->login;
    } // Alias para compatibilidad
    public function getPassword()
    {
        return $this->password;
    }
    public function getRol()
    {
        return $this->rol;
    }
    public function getIdRol()
    {
        return $this->idRol;
    }
    public function getActivo()
    {
        return $this->activo;
    }
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Verifica si el usuario tiene un permiso específico por clave.
     */
    public function tienePermiso($permisoClave)
    {
        // Por seguridad, si es admin total por legacy, permitimos todo
        if ($this->rol === 'admin') return true;

        if (!isset($_SESSION['permisos_usuario'])) {
            require_once __DIR__ . '/RolPDO.php';
            $_SESSION['permisos_usuario'] = RolPDO::obtenerPermisosRol($this->idRol);
        }

        return in_array($permisoClave, $_SESSION['permisos_usuario'] ?? []);
    }

    // Setters (opcional, para edición)
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }
    public function setRol($rol)
    {
        $this->rol = $rol;
    }
    public function setNombreCompleto($nombre)
    {
        $this->nombre = $nombre;
    }
    public function setIdRol($id)
    {
        $this->idRol = $id;
    }
    public function setIdioma($idioma)
    {
        $this->idioma = $idioma;
    }
}
