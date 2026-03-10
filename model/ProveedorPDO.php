<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/Proveedor.php';

class ProveedorPDO
{

    public static function listarTodos($soloActivos = true)
    {
        try {
            $db = new PDO(DSN, USERNAME, PASSWORD);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT * FROM proveedores";
            if ($soloActivos) {
                $sql .= " WHERE activo = 1";
            }
            $sql .= " ORDER BY nombre ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute();

            $proveedores = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $proveedores[] = new Proveedor(
                    $row['id'],
                    $row['cif_nif'],
                    $row['nombre'],
                    $row['direccion'],
                    $row['telefono'],
                    $row['email'],
                    (bool)$row['aplica_re'],
                    $row['notas'],
                    (bool)$row['activo'],
                    $row['fecha_alta']
                );
            }
            return $proveedores;
        } catch (PDOException $e) {
            error_log("Error en ProveedorPDO::listarTodos: " . $e->getMessage());
            return [];
        }
    }

    public static function buscarPorId($id)
    {
        try {
            $db = new PDO(DSN, USERNAME, PASSWORD);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("SELECT * FROM proveedores WHERE id = ?");
            $stmt->execute([$id]);

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new Proveedor(
                    $row['id'],
                    $row['cif_nif'],
                    $row['nombre'],
                    $row['direccion'],
                    $row['telefono'],
                    $row['email'],
                    (bool)$row['aplica_re'],
                    $row['notas'],
                    (bool)$row['activo'],
                    $row['fecha_alta']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Error en ProveedorPDO::buscarPorId: " . $e->getMessage());
            return null;
        }
    }

    public static function añadirProveedor($cif_nif, $nombre, $direccion, $telefono, $email, $aplica_re, $notas)
    {
        try {
            $db = new PDO(DSN, USERNAME, PASSWORD);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "INSERT INTO proveedores (cif_nif, nombre, direccion, telefono, email, aplica_re, notas) 
                    VALUES (:cif, :nombre, :direccion, :telefono, :email, :re, :notas)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':cif' => $cif_nif,
                ':nombre' => $nombre,
                ':direccion' => $direccion,
                ':telefono' => $telefono,
                ':email' => $email,
                ':re' => $aplica_re ? 1 : 0,
                ':notas' => $notas
            ]);

            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en ProveedorPDO::añadirProveedor: " . $e->getMessage());
            return false;
        }
    }

    public static function editarProveedor($id, $cif_nif, $nombre, $direccion, $telefono, $email, $aplica_re, $notas, $activo)
    {
        try {
            $db = new PDO(DSN, USERNAME, PASSWORD);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "UPDATE proveedores SET 
                    cif_nif = :cif, 
                    nombre = :nombre, 
                    direccion = :direccion, 
                    telefono = :telefono, 
                    email = :email, 
                    aplica_re = :re, 
                    notas = :notas,
                    activo = :activo
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':cif' => $cif_nif,
                ':nombre' => $nombre,
                ':direccion' => $direccion,
                ':telefono' => $telefono,
                ':email' => $email,
                ':re' => $aplica_re ? 1 : 0,
                ':notas' => $notas,
                ':activo' => $activo ? 1 : 0
            ]);
        } catch (PDOException $e) {
            error_log("Error en ProveedorPDO::editarProveedor: " . $e->getMessage());
            return false;
        }
    }

    public static function borrarProveedor($id)
    {
        try {
            $db = new PDO(DSN, USERNAME, PASSWORD);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("DELETE FROM proveedores WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error en ProveedorPDO::borrarProveedor: " . $e->getMessage());
            return false;
        }
    }
}
