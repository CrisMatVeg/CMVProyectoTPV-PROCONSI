<?php
/**
 * Clase: Producto
 * Entidad que representa un producto del sistema.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
class Producto {
    private $id;
    private $referencia;
    private $nombre;
    private $descripcion;
    private $precio_coste;
    private $precio_venta;
    private $iva;
    private $stock_actual;
    private $stock_minimo;
    private $meses_garantia;
    private $icono;
    private $categoria;
    private $variantes;
    private $activo;
    private $requiere_serial;

    public function __construct($id, $referencia, $nombre, $descripcion, $precio_coste, $precio_venta, $iva, $stock_actual, $stock_minimo, $meses_garantia, $icono, $categoria, $variantes, $activo, $requiere_serial = 0) {
        $this->id = $id;
        $this->referencia = $referencia;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->precio_coste = $precio_coste;
        $this->precio_venta = $precio_venta;
        $this->iva = $iva;
        $this->stock_actual = $stock_actual;
        $this->stock_minimo = $stock_minimo;
        $this->meses_garantia = $meses_garantia;
        $this->icono = $icono;
        $this->categoria = $categoria;
        $this->variantes = $variantes;
        $this->activo = $activo;
        $this->requiere_serial = $requiere_serial;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getReferencia() { return $this->referencia; }
    public function getNombre() { return $this->nombre; }
    public function getDescripcion() { return $this->descripcion; }
    public function getPrecioCoste() { return $this->precio_coste; }
    public function getPrecioVenta() { return $this->precio_venta; }
    public function getIva() { return $this->iva; }
    public function getStockActual() { return $this->stock_actual; }
    public function getStockMinimo() { return $this->stock_minimo; }
    public function getMesesGarantia() { return $this->meses_garantia; }
    public function getIcono() { return $this->icono; }
    public function getCategoria() { return $this->categoria; }
    public function getVariantes() { return $this->variantes; }
    public function getActivo() { return $this->activo; }
    public function getRequiereSerial() { return $this->requiere_serial; }
}
