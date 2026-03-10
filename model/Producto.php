<?php

/**
 * Clase: Producto
 * Entidad que representa un producto del sistema.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
class Producto
{
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
    private $atributos;
    private $activo;
    private $codigo_iva;
    private $es_pack;
    private $precio_proveedor;
    private $aplica_re;
    private $id_proveedor;

    public function __construct($id, $referencia, $nombre, $descripcion, $precio_coste, $precio_venta, $iva, $stock_actual, $stock_minimo, $meses_garantia, $icono, $categoria, $variantes, $atributos, $activo, $codigo_iva = 'GENERAL', $es_pack = 0, $precio_proveedor = 0, $aplica_re = 0, $id_proveedor = null)
    {
        $this->id = $id;
        $this->referencia = $referencia;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->precio_coste = (float)$precio_coste;
        $this->precio_venta = (float)$precio_venta;
        $this->iva = (float)$iva;
        $this->stock_actual = (int)$stock_actual;
        $this->stock_minimo = (int)$stock_minimo;
        $this->meses_garantia = (int)$meses_garantia;
        $this->icono = $icono;
        $this->categoria = $categoria;
        $this->variantes = $variantes;
        $this->atributos = $atributos;
        $this->activo = $activo;
        $this->codigo_iva = $codigo_iva;
        $this->es_pack = (int)$es_pack;
        $this->precio_proveedor = (float)$precio_proveedor;
        $this->aplica_re = (int)$aplica_re;
        $this->id_proveedor = $id_proveedor;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }
    public function getReferencia()
    {
        return $this->referencia;
    }
    public function getNombre()
    {
        return $this->nombre;
    }
    public function getDescripcion()
    {
        return $this->descripcion;
    }
    public function getPrecioCoste()
    {
        return $this->precio_coste;
    }
    public function getPrecioVenta()
    {
        return $this->precio_venta;
    }
    public function getIva()
    {
        return $this->iva;
    }
    public function getStockActual()
    {
        return $this->stock_actual;
    }
    public function getStockMinimo()
    {
        return $this->stock_minimo;
    }
    public function getMesesGarantia()
    {
        return $this->meses_garantia;
    }
    public function getIcono()
    {
        return $this->icono;
    }
    public function getCategoria()
    {
        return $this->categoria;
    }
    public function getVariantes()
    {
        return $this->variantes;
    }
    public function getAtributos()
    {
        return $this->atributos;
    }
    public function getActivo()
    {
        return $this->activo;
    }
    public function getCodigoIva()
    {
        return $this->codigo_iva;
    }
    public function getEsPack()
    {
        return $this->es_pack;
    }
    public function getPrecioProveedor()
    {
        return $this->precio_proveedor;
    }
    public function getAplicaRE()
    {
        return $this->aplica_re;
    }

    public function getIdProveedor()
    {
        return $this->id_proveedor;
    }

    public function isPack(): bool
    {
        return (bool)$this->es_pack;
    }
}
