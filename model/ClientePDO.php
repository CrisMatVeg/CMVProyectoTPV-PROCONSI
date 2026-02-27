<?php
/**
 * Clase: ClientePDO
 * Gestiona la persistencia de clientes socios.
 * @package Modelos
 */
require_once 'DBPDO.php';
require_once 'Cliente.php';

class ClientePDO {

    public static function buscarClientePorNif(string $nif): ?Cliente {
        $sql = "SELECT * FROM clientes WHERE nif = :nif";
        $q = DBPDO::ejecutarConsulta($sql, [':nif' => $nif]);
        $res = $q->fetch(PDO::FETCH_ASSOC);
        if (!$res) return null;

        return new Cliente($res['id'], $res['nombre'], $res['nif'], $res['email'], $res['telefono'], $res['es_socio'], $res['fecha_alta']);
    }

    public static function listarClientes(): array {
        $sql = "SELECT * FROM clientes ORDER BY nombre ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        $clientes = [];
        while ($res = $q->fetch(PDO::FETCH_ASSOC)) {
            $clientes[] = new Cliente($res['id'], $res['nombre'], $res['nif'], $res['email'], $res['telefono'], $res['es_socio'], $res['fecha_alta']);
        }
        return $clientes;
    }

    public static function registrarCliente(array $datos): int {
        $sql = "INSERT INTO clientes (nombre, nif, email, telefono, es_socio) 
                VALUES (:nombre, :nif, :email, :telefono, :es_socio)";
        DBPDO::ejecutarConsulta($sql, [
            ':nombre'   => $datos['nombre'],
            ':nif'      => $datos['nif'] ?? null,
            ':email'    => $datos['email'] ?? null,
            ':telefono' => $datos['telefono'] ?? null,
            ':es_socio' => ($datos['es_socio'] ?? false) ? 1 : 0
        ]);
        $q = DBPDO::ejecutarConsulta("SELECT id FROM clientes ORDER BY id DESC LIMIT 1");
        return (int)$q->fetch(PDO::FETCH_ASSOC)['id'];
    }
}
