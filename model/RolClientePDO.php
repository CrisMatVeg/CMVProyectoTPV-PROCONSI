<?php

/**
 * Clase: RolClientePDO
 * Gestiona los roles personalizables para los clientes en la tabla roles_cliente
 */
require_once __DIR__ . '/DBPDO.php';

class RolClientePDO
{
    /**
     * Obtiene la lista de todos los roles de cliente registrados.
     */
    public static function listarRoles(): array
    {
        $sql = "SELECT * FROM roles_cliente ORDER BY nombre ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Añade un nuevo rol de cliente.
     */
    public static function añadirRol(string $nombre): int
    {
        $nombre = strtolower(trim($nombre));
        $sql = "INSERT INTO roles_cliente (nombre) VALUES (:n)";
        DBPDO::ejecutarConsulta($sql, [':n' => $nombre]);

        $q = DBPDO::ejecutarConsulta("SELECT id FROM roles_cliente ORDER BY id DESC LIMIT 1");
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return (int)$row['id'];
    }
}
