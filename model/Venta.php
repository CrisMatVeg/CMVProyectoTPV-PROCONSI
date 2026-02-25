<?php
/**
 * Clase: Venta
 * Entidad que representa una venta realizada en el TPV.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
class Venta {
    private $id;
    private $numeroTicket;
    private $tipoCliente;
    private $nombreCliente;
    private $nifCliente;
    private $metodoPago;
    private $subtotal;
    private $descuentoPct;
    private $descuentoAmt;
    private $baseImponible;
    private $ivaPct;
    private $ivaAmt;
    private $total;
    private $idCajero;
    private $creadoEn;
    private $lineas; // array de lineas_venta

    public function __construct($id, $numeroTicket, $tipoCliente, $nombreCliente,
                                $nifCliente, $metodoPago, $subtotal, $descuentoPct,
                                $descuentoAmt, $baseImponible, $ivaPct, $ivaAmt,
                                $total, $idCajero, $creadoEn, $lineas = []) {
        $this->id = $id;
        $this->numeroTicket = $numeroTicket;
        $this->tipoCliente = $tipoCliente;
        $this->nombreCliente = $nombreCliente;
        $this->nifCliente = $nifCliente;
        $this->metodoPago = $metodoPago;
        $this->subtotal = $subtotal;
        $this->descuentoPct = $descuentoPct;
        $this->descuentoAmt = $descuentoAmt;
        $this->baseImponible = $baseImponible;
        $this->ivaPct = $ivaPct;
        $this->ivaAmt = $ivaAmt;
        $this->total = $total;
        $this->idCajero = $idCajero;
        $this->creadoEn = $creadoEn;
        $this->lineas = $lineas;
    }

    // Getters
    public function getId()            { return $this->id; }
    public function getNumeroTicket()  { return $this->numeroTicket; }
    public function getTipoCliente()   { return $this->tipoCliente; }
    public function getNombreCliente() { return $this->nombreCliente; }
    public function getNifCliente()    { return $this->nifCliente; }
    public function getMetodoPago()    { return $this->metodoPago; }
    public function getSubtotal()      { return $this->subtotal; }
    public function getDescuentoPct()  { return $this->descuentoPct; }
    public function getDescuentoAmt()  { return $this->descuentoAmt; }
    public function getBaseImponible() { return $this->baseImponible; }
    public function getIvaPct()        { return $this->ivaPct; }
    public function getIvaAmt()        { return $this->ivaAmt; }
    public function getTotal()         { return $this->total; }
    public function getIdCajero()      { return $this->idCajero; }
    public function getCreadoEn()      { return $this->creadoEn; }
    public function getLineas()        { return $this->lineas; }
}
