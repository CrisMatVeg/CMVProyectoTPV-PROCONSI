<?php

/**
 * Clase: RolPDO
 * Gestiona la persistencia de roles y sus permisos.
 * @package Modelos
 */
require_once __DIR__ . '/DBPDO.php';

class RolPDO
{

    /**
     * Devuelve todos los roles disponibles.
     */
    public static function listarRoles(): array
    {
        $sql = "SELECT * FROM roles ORDER BY nombre ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve las claves de los permisos asociados a un rol.
     */
    public static function obtenerPermisosRol(?int $idRol): array
    {
        if (!$idRol) return [];

        $sql = "SELECT p.clave 
                FROM permisos p
                JOIN rol_permisos rp ON p.id = rp.id_permiso
                WHERE rp.id_rol = :idrol";

        $q = DBPDO::ejecutarConsulta($sql, [':idrol' => $idRol]);
        $permisos = [];
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $permisos[] = $row['clave'];
        }
        return $permisos;
    }

    /**
     * Devuelve todos los permisos del sistema.
     */
    public static function listarPermisos(): array
    {
        $sql = "SELECT * FROM permisos ORDER BY descripcion ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo rol.
     */
    public static function añadirRol(string $nombre, string $descripcion): int
    {
        $sql = "INSERT INTO roles (nombre, descripcion) VALUES (:nom, :des)";
        DBPDO::ejecutarConsulta($sql, [':nom' => $nombre, ':des' => $descripcion]);

        $q = DBPDO::ejecutarConsulta("SELECT id FROM roles ORDER BY id DESC LIMIT 1");
        return (int)$q->fetch(PDO::FETCH_ASSOC)['id'];
    }

    /**
     * Asigna un conjunto de permisos a un rol (reemplaza los existentes).
     */
    public static function asignarPermisos(int $idRol, array $permisosIds): void
    {
        // Borrar actuales
        DBPDO::ejecutarConsulta("DELETE FROM rol_permisos WHERE id_rol = :id", [':id' => $idRol]);

        // Insertar nuevos
        foreach ($permisosIds as $idPermiso) {
            DBPDO::ejecutarConsulta("INSERT INTO rol_permisos (id_rol, id_permiso) VALUES (:idr, :idp)", [
                ':idr' => $idRol,
                ':idp' => $idPermiso
            ]);
        }
    }
}
