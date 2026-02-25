<?php
/**
 * Clase: Usuario
 * * Entidad que representa a un usuario del sistema TPV.
 * * @package Modelos
 * @author Cristian Mateos Vega
 */
class Usuario {
    private $id;
    private $nombreCompleto;
    private $username;
    private $password;
    private $rol;
    private $activo;

    public function __construct($id, $nombreCompleto, $username, $password, $rol, $activo) {
        $this->id = $id;
        $this->nombreCompleto = $nombreCompleto;
        $this->username = $username;
        $this->password = $password;
        $this->rol = $rol;
        $this->activo = $activo;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNombreCompleto() { return $this->nombreCompleto; }
    public function getUsername() { return $this->username; }
    public function getPassword() { return $this->password; }
    public function getRol() { return $this->rol; }
    public function getActivo() { return $this->activo; }

    // Setters (opcional, para edición)
    public function setNombreCompleto($nombre) { $this->nombreCompleto = $nombre; }
    public function setRol($rol) { $this->rol = $rol; }
}