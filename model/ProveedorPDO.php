<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/Proveedor.php';

class ProveedorPDO
{

    public static function listarTodos($soloActivos = true)
    {
        try {
            $sql = "SELECT * FROM proveedores";
            if ($soloActivos) {
                $sql .= " WHERE activo = 1";
            }
            $sql .= " ORDER BY nombre ASC";

            $stmt = DBPDO::ejecutarConsulta($sql);

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
                    $row['condiciones_pago'],
                    $row['plazo_entrega'],
                    $row['vencimiento_dias'],
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
            $stmt = DBPDO::ejecutarConsulta("SELECT * FROM proveedores WHERE id = ?", [$id]);

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
                    $row['condiciones_pago'],
                    $row['plazo_entrega'],
                    $row['vencimiento_dias'],
                    $row['fecha_alta']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Error en ProveedorPDO::buscarPorId: " . $e->getMessage());
            return null;
        }
    }

    public static function añadirProveedor($cif_nif, $nombre, $direccion, $telefono, $email, $aplica_re, $notas, $condiciones_pago = null, $plazo_entrega = null, $vencimiento_dias = 0)
    {
        try {
            $db = DBPDO::getPDO();

            $sql = "INSERT INTO proveedores (cif_nif, nombre, direccion, telefono, email, aplica_re, notas, condiciones_pago, plazo_entrega, vencimiento_dias) 
                    VALUES (:cif, :nombre, :direccion, :telefono, :email, :re, :notas, :cond_pago, :plazo, :venc)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':cif' => $cif_nif,
                ':nombre' => $nombre,
                ':direccion' => $direccion,
                ':telefono' => $telefono,
                ':email' => $email,
                ':re' => $aplica_re ? 1 : 0,
                ':notas' => $notas,
                ':cond_pago' => $condiciones_pago,
                ':plazo' => $plazo_entrega,
                ':venc' => $vencimiento_dias
            ]);

            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en ProveedorPDO::añadirProveedor: " . $e->getMessage());
            return false;
        }
    }

    public static function editarProveedor($id, $cif_nif, $nombre, $direccion, $telefono, $email, $aplica_re, $notas, $activo, $condiciones_pago = null, $plazo_entrega = null, $vencimiento_dias = 0)
    {
        try {

            $sql = "UPDATE proveedores SET 
                    cif_nif = :cif, 
                    nombre = :nombre, 
                    direccion = :direccion, 
                    telefono = :telefono, 
                    email = :email, 
                    aplica_re = :re, 
                    notas = :notas,
                    activo = :activo,
                    condiciones_pago = :cond_pago,
                    plazo_entrega = :plazo,
                    vencimiento_dias = :venc
                    WHERE id = :id";

            $stmt = DBPDO::ejecutarConsulta($sql, [
                ':id' => $id,
                ':cif' => $cif_nif,
                ':nombre' => $nombre,
                ':direccion' => $direccion,
                ':telefono' => $telefono,
                ':email' => $email,
                ':re' => $aplica_re ? 1 : 0,
                ':notas' => $notas,
                ':activo' => $activo ? 1 : 0,
                ':cond_pago' => $condiciones_pago,
                ':plazo' => $plazo_entrega,
                ':venc' => $vencimiento_dias
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error en ProveedorPDO::editarProveedor: " . $e->getMessage());
            return false;
        }
    }

    public static function borrarProveedor($id)
    {
        try {
            $stmt = DBPDO::ejecutarConsulta("DELETE FROM proveedores WHERE id = ?", [$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error en ProveedorPDO::borrarProveedor: " . $e->getMessage());
            return false;
        }
    }
}
