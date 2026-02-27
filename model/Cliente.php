<?php
/**
 * Clase: Cliente
 * Entidad que representa a un cliente o socio del sistema.
 * @package Modelos
 */
class Cliente {
    private $id;
    private $nombre;
    private $nif;
    private $email;
    private $telefono;
    private $esSocio;
    private $fechaAlta;

    public function __construct($id, $nombre, $nif, $email, $telefono, $esSocio, $fechaAlta) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->nif = $nif;
        $this->email = $email;
        $this->telefono = $telefono;
        $this->esSocio = $esSocio;
        $this->fechaAlta = $fechaAlta;
    }

    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getNif() { return $this->nif; }
    public function getEmail() { return $this->email; }
    public function getTelefono() { return $this->telefono; }
    public function getEsSocio() { return $this->esSocio; }
    public function getFechaAlta() { return $this->fechaAlta; }
}
