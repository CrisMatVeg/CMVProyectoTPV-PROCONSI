<?php

class Proveedor
{
    private $id;
    private $cif_nif;
    private $nombre;
    private $direccion;
    private $telefono;
    private $email;
    private $aplica_re;
    private $notas;
    private $activo;
    private $vencimiento_dias;
    private $fecha_alta;

    public function __construct($id, $cif_nif, $nombre, $direccion, $telefono, $email, $aplica_re, $notas, $activo, $vencimiento_dias, $fecha_alta)
    {
        $this->id = $id;
        $this->cif_nif = $cif_nif;
        $this->nombre = $nombre;
        $this->direccion = $direccion;
        $this->telefono = $telefono;
        $this->email = $email;
        $this->aplica_re = $aplica_re;
        $this->notas = $notas;
        $this->activo = $activo;
        $this->vencimiento_dias = $vencimiento_dias;
        $this->fecha_alta = $fecha_alta;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getCifNif()
    {
        return $this->cif_nif;
    }
    public function getNombre()
    {
        return $this->nombre;
    }
    public function getDireccion()
    {
        return $this->direccion;
    }
    public function getTelefono()
    {
        return $this->telefono;
    }
    public function getEmail()
    {
        return $this->email;
    }
    public function getAplicaRe()
    {
        return $this->aplica_re;
    }
    public function getNotas()
    {
        return $this->notas;
    }
    public function getActivo()
    {
        return $this->activo;
    }
    public function getVencimientoDias()
    {
        return $this->vencimiento_dias;
    }
    public function getFechaAlta()
    {
        return $this->fecha_alta;
    }
}
