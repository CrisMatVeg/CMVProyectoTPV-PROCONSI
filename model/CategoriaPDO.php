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

    public static function agregar(string $codigo, string $nombre): bool
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

    /**
     * Asigna un tipo de IVA a una categoría y actualiza todos sus productos.
     */
    public static function asignarIVA(int $idCategoria, int $idTipoIva): bool
    {
        try {
            $db = DBPDO::getPDO();
            $db->beginTransaction();

            // 1. Actualizar el IVA por defecto en la categoría
            $sqlCat = "UPDATE categorias SET id_tipo_iva = :idIva WHERE id = :idCat";
            DBPDO::ejecutarConsulta($sqlCat, [
                ':idIva' => $idTipoIva,
                ':idCat' => $idCategoria
            ]);

            // 2. Obtener el porcentaje y código del nuevo IVA
            $sqlIva = "SELECT codigo, porcentaje FROM tipos_iva WHERE id = :idIva";
            $qIva = DBPDO::ejecutarConsulta($sqlIva, [':idIva' => $idTipoIva]);
            $ivaInfo = $qIva->fetch(PDO::FETCH_ASSOC);

            if (!$ivaInfo) {
                $db->rollBack();
                return false;
            }

            // 3. Actualizar masivamente todos los productos de esta categoría
            // Nota: usamos el código de categoría para el JOIN ya que productos.categoria guarda el código
            $sqlProd = "UPDATE productos p 
                       JOIN categorias c ON p.categoria = c.codigo
                       SET p.id_tipo_iva = :idIva, 
                           p.codigo_iva = :codIva,
                           p.iva = :porcentaje
                       WHERE c.id = :idCat";

            DBPDO::ejecutarConsulta($sqlProd, [
                ':idIva' => $idTipoIva,
                ':codIva' => $ivaInfo['codigo'],
                ':porcentaje' => $ivaInfo['porcentaje'],
                ':idCat' => $idCategoria
            ]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            return false;
        }
    }

    /**
     * Actualiza el nombre y código de una categoría.
     */
    public static function editar(int $id, string $codigo, string $nombre): bool
    {
        try {
            $db = DBPDO::getPDO();
            $db->beginTransaction();

            // 1. Obtener código antiguo para actualizar productos si el código cambia
            $sqlOld = "SELECT codigo FROM categorias WHERE id = :id";
            $qOld = DBPDO::ejecutarConsulta($sqlOld, [':id' => $id]);
            $oldCode = $qOld->fetchColumn();

            // 2. Actualizar la categoría
            $sql = "UPDATE categorias SET codigo = :codigo, nombre = :nombre WHERE id = :id";
            DBPDO::ejecutarConsulta($sql, [
                ':id' => $id,
                ':codigo' => strtolower(trim($codigo)),
                ':nombre' => trim($nombre)
            ]);

            // 3. Si el código cambió, actualizar los productos vinculados
            if ($oldCode && $oldCode !== strtolower(trim($codigo))) {
                $sqlProd = "UPDATE productos SET categoria = :newCode WHERE categoria = :oldCode";
                DBPDO::ejecutarConsulta($sqlProd, [
                    ':newCode' => strtolower(trim($codigo)),
                    ':oldCode' => $oldCode
                ]);
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            return false;
        }
    }
}
