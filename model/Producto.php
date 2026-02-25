<?php
/**
 * Clase: Producto
 * * Entidad que representa un producto del sistema.
 * * @package Modelos
 * @author Cristian Mateos Vega
 */
class Producto {
    private $id;
    private $nombre;
    private $codigo;
    private $precio;
    private $icono;
    private $categoria;
    private $activo;

    public function __construct($id, $nombre, $codigo, $precio, $icono, $categoria, $activo) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->codigo = $codigo;
        $this->precio = $precio;
        $this->icono = $icono;
        $this->categoria = $categoria;
        $this->activo = $activo;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getCodigo() { return $this->codigo; }
    public function getPrecio() { return $this->precio; }
    public function getIcono() { return $this->icono; }
    public function getCategoria() { return $this->categoria; }
    public function getActivo() { return $this->activo; }
}
