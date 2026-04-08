<?php

/**
 * Clase: Producto
 * Entidad que representa un producto del sistema.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
class Producto implements JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return [
            'id'             => $this->id,
            'referencia'     => $this->referencia,
            'nombre'         => $this->nombre,
            'descripcion'    => $this->descripcion,
            'precio_coste'   => $this->precio_coste,
            'precio_venta'   => $this->precio_venta,
            'stock_actual'   => $this->stock_actual,
            'stock_minimo'   => $this->stock_minimo,
            'meses_garantia' => $this->meses_garantia,
            'icono'          => $this->icono,
            'categoria'      => $this->categoria,
            'atributos'      => $this->atributos,
            'activo'         => $this->activo,
            'codigo_iva'     => $this->codigo_iva,
            'es_pack'        => $this->es_pack,
            'aplica_re'      => $this->aplica_re,
            'id_proveedor'   => $this->id_proveedor,
            'iva'            => $this->iva,
            'margen'         => $this->margen,
            'precio_proveedor' => $this->precio_proveedor,
        ];
    }
    private $id;
    private $referencia;
    private $nombre;
    private $descripcion;
    private $precio_coste;
    private $precio_venta;
    private $stock_actual;
    private $stock_minimo;
    private $meses_garantia;
    private $icono;
    private $categoria;
    private $atributos;
    private $activo;
    private $codigo_iva;
    private $es_pack;
    private $aplica_re;
    private $id_proveedor;
    private $iva;
    private $margen;
    private $precio_proveedor;

    public function __construct($id, $referencia, $nombre, $descripcion, $precio_coste, $precio_venta, $stock_actual, $stock_minimo, $meses_garantia, $icono, $categoria, $atributos, $activo, $codigo_iva = 'GENERAL', $es_pack = 0, $aplica_re = 0, $id_proveedor = null, $iva = 21.00, $margen = 0.00, $precio_proveedor = 0.0000)
    {
        $this->id = $id;
        $this->referencia = $referencia;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->precio_coste = (float)$precio_coste;
        $this->precio_venta = (float)$precio_venta;
        $this->stock_actual = (int)$stock_actual;
        $this->stock_minimo = (int)$stock_minimo;
        $this->meses_garantia = (int)$meses_garantia;
        $this->icono = $icono;
        $this->categoria = $categoria;
        $this->atributos = $atributos;
        $this->activo = $activo;
        $this->codigo_iva = $codigo_iva;
        $this->es_pack = (int)$es_pack;
        $this->aplica_re = (int)$aplica_re;
        $this->id_proveedor = $id_proveedor;
        $this->iva = (float)$iva;
        $this->margen = (float)$margen;
        $this->precio_proveedor = (float)$precio_proveedor;
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
    public function getIva()
    {
        return $this->iva;
    }
    public function getEsPack()
    {
        return $this->es_pack;
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

    public function getPrecioProveedor()
    {
        return $this->precio_proveedor;
    }

    public function getMargen()
    {
        return $this->margen;
    }
}
