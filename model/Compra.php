<?php

class Compra
{
    private $id;
    private $proveedor_id;
    private $numero_factura;
    private $fecha;
    private $base_imponible;
    private $iva_total;
    private $re_total;
    private $total;
    private $fecha_registro;
    private $lineas;

    public function __construct($id, $proveedor_id, $numero_factura, $fecha, $base_imponible, $iva_total, $re_total, $total, $fecha_registro, $lineas = [])
    {
        $this->id = $id;
        $this->proveedor_id = $proveedor_id;
        $this->numero_factura = $numero_factura;
        $this->fecha = $fecha;
        $this->base_imponible = $base_imponible;
        $this->iva_total = $iva_total;
        $this->re_total = $re_total;
        $this->total = $total;
        $this->fecha_registro = $fecha_registro;
        $this->lineas = $lineas;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getProveedorId()
    {
        return $this->proveedor_id;
    }
    public function getNumeroFactura()
    {
        return $this->numero_factura;
    }
    public function getFecha()
    {
        return $this->fecha;
    }
    public function getBaseImponible()
    {
        return $this->base_imponible;
    }
    public function getIvaTotal()
    {
        return $this->iva_total;
    }
    public function getReTotal()
    {
        return $this->re_total;
    }
    public function getTotal()
    {
        return $this->total;
    }
    public function getFechaRegistro()
    {
        return $this->fecha_registro;
    }
    public function getLineas()
    {
        return $this->lineas;
    }
}

class CompraLinea
{
    private $id;
    private $factura_id;
    private $producto_id;
    private $cantidad;
    private $precio_coste_neto;
    private $iva_pct;
    private $re_pct;

    public function __construct($id, $factura_id, $producto_id, $cantidad, $precio_coste_neto, $iva_pct, $re_pct)
    {
        $this->id = $id;
        $this->factura_id = $factura_id;
        $this->producto_id = $producto_id;
        $this->cantidad = $cantidad;
        $this->precio_coste_neto = $precio_coste_neto;
        $this->iva_pct = $iva_pct;
        $this->re_pct = $re_pct;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getFacturaId()
    {
        return $this->factura_id;
    }
    public function getProductoId()
    {
        return $this->producto_id;
    }
    public function getCantidad()
    {
        return $this->cantidad;
    }
    public function getPrecioCosteNeto()
    {
        return $this->precio_coste_neto;
    }
    public function getIvaPct()
    {
        return $this->iva_pct;
    }
    public function getRePct()
    {
        return $this->re_pct;
    }
}
