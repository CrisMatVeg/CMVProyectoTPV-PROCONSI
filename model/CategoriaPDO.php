<?php

/**
 * Clase: CategoriaPDO
 * Gestiona las categorías de productos.
 * @package Modelos
 */
require_once __DIR__ . '/DBPDO.php';

class CategoriaPDO
{
    public static function listarTodas(): array
    {
        $sql = "SELECT * FROM categorias ORDER BY nombre ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function añadir(string $codigo, string $nombre): bool
    {
        $sql = "INSERT INTO categorias (codigo, nombre) VALUES (:codigo, :nombre)";
        try {
            DBPDO::ejecutarConsulta($sql, [
                ':codigo' => strtolower(trim($codigo)),
                ':nombre' => trim($nombre)
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function eliminar(int $id): bool
    {
        $sql = "DELETE FROM categorias WHERE id = :id";
        try {
            DBPDO::ejecutarConsulta($sql, [':id' => $id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
