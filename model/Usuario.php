<?php
/**
 * Clase: Usuario
 * * Entidad que representa a un usuario del sistema TPV.
 * * @package Modelos
 * @author Cristian Mateos Vega
 */
class Usuario {
    private $id;
    private $nombre;
    private $login;
    private $password;
    private $rol;
    private $activo;

    public function __construct($id, $nombre, $login, $password, $rol, $activo) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->login = $login;
        $this->password = $password;
        $this->rol = $rol;
        $this->activo = $activo;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getNombreCompleto() { return $this->nombre; } // Alias para compatibilidad
    public function getLogin() { return $this->login; }
    public function getUsername() { return $this->login; } // Alias para compatibilidad
    public function getPassword() { return $this->password; }
    public function getRol() { return $this->rol; }
    public function getActivo() { return $this->activo; }

    // Setters (opcional, para edición)
    public function setNombre($nombre) { $this->nombre = $nombre; }
    public function setRol($rol) { $this->rol = $rol; }
}