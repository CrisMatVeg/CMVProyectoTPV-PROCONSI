<?php

/**
 * Clase: NumeroSeriePDO
 * Gestiona el control de cada unidad física individual mediante números de serie.
 */
require_once __DIR__ . '/DBPDO.php';

class NumeroSeriePDO
{

    /**
     * Lista todos los números de serie de un producto específico.
     */
    public static function listarPorProducto(int $idProducto): array
    {
        $sql = "SELECT * FROM numeros_serie WHERE id_producto = :id ORDER BY numero_serie ASC";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idProducto]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un nuevo número de serie para un producto.
     */
    public static function añadirNumeroSerie(int $idProducto, string $numeroSerie): bool
    {
        $sql = "INSERT INTO numeros_serie (id_producto, numero_serie, estado) VALUES (:id, :ns, 'disponible')";
        return (bool)DBPDO::ejecutarConsulta($sql, [':id' => $idProducto, ':ns' => trim($numeroSerie)]);
    }

    /**
     * Cambia el estado de un número de serie.
     */
    public static function cambiarEstado(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE numeros_serie SET estado = :estado WHERE id = :id";
        return (bool)DBPDO::ejecutarConsulta($sql, [':estado' => $nuevoEstado, ':id' => $id]);
    }

    /**
     * Obtiene los números de serie disponibles para un producto.
     */
    public static function obtenerDisponibles(int $idProducto): array
    {
        $sql = "SELECT id, numero_serie FROM numeros_serie WHERE id_producto = :id AND estado = 'disponible'";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idProducto]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina un número de serie (solo si está disponible o por error de entrada).
     */
    public static function eliminar(int $id): bool
    {
        $sql = "DELETE FROM numeros_serie WHERE id = :id";
        return (bool)DBPDO::ejecutarConsulta($sql, [':id' => $id]);
    }
}
