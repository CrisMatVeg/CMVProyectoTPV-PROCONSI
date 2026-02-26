<?php
/**
 * Clase: ProductoPDO
 * Gestiona la persistencia de los productos mediante DBPDO.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
require_once 'DBPDO.php';
require_once 'Producto.php';

class ProductoPDO {

    /**
     * Obtiene todos los productos activos de la base de datos.
     * @return Producto[]
     */
    public static function listarProductos(): array {
        $sql = "SELECT * FROM productos WHERE activo = 1";
        $consulta = DBPDO::ejecutarConsulta($sql);

        $productos = [];
        while ($registro = $consulta->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = new Producto(
                $registro['id'],
                $registro['nombre'],
                $registro['codigo'],
                $registro['precio'],
                $registro['icono'],
                $registro['categoria'],
                $registro['activo'],
                $registro['stock']
            );
        }
        return $productos;
    }

    /**
     * Añade un nuevo producto a la base de datos.
     * @param array $datos Campos: nombre, codigo, precio, icono, categoria
     * @return array El nuevo producto como array asociativo (con id asignado)
     */
    public static function añadirProducto(array $datos): array {
        $sql = "INSERT INTO productos (nombre, codigo, precio, icono, categoria, activo, stock)
                VALUES (:nombre, :codigo, :precio, :icono, :categoria, 1, :stock)";
        DBPDO::ejecutarConsulta($sql, [
            ':nombre'    => mb_substr(trim($datos['nombre']), 0, 100),
            ':codigo'    => mb_substr(trim($datos['codigo']), 0, 50),
            ':precio'    => round((float)$datos['precio'], 2),
            ':icono'     => mb_substr(trim($datos['icono']), 0, 10),
            ':categoria' => mb_substr(trim($datos['categoria']), 0, 50),
            ':stock'     => (int)($datos['stock'] ?? 0),
        ]);

        // Obtener el registro recién insertado
        $q = DBPDO::ejecutarConsulta(
            "SELECT * FROM productos ORDER BY id DESC LIMIT 1"
        );
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza los datos de un producto existente.
     * @param int   $id
     * @param array $datos Campos: nombre, codigo, precio, icono, categoria
     */
    public static function editarProducto(int $id, array $datos): void {
        $sql = "UPDATE productos
                SET nombre = :nombre, codigo = :codigo, precio = :precio,
                    icono = :icono, categoria = :categoria, stock = :stock
                WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':nombre'    => mb_substr(trim($datos['nombre']), 0, 100),
            ':codigo'    => mb_substr(trim($datos['codigo']), 0, 50),
            ':precio'    => round((float)$datos['precio'], 2),
            ':icono'     => mb_substr(trim($datos['icono']), 0, 10),
            ':categoria' => mb_substr(trim($datos['categoria']), 0, 50),
            ':stock'     => (int)($datos['stock'] ?? 0),
            ':id'        => $id,
        ]);
    }

    /**
     * Elimina un producto de la base de datos.
     * @param int $id
     */
    public static function eliminarProducto(int $id): void {
        $sql = "DELETE FROM productos WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [':id' => $id]);
    }

    /**
     * Alterna el estado activo/inactivo (baja/alta) de un producto.
     * @param int $id
     * @return bool Nuevo estado activo (true = activo, false = baja)
     */
    public static function toggleBaja(int $id): bool {
        // Primero consultamos el estado actual
        $q = DBPDO::ejecutarConsulta(
            "SELECT activo FROM productos WHERE id = :id",
            [':id' => $id]
        );
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $nuevoEstado = $row['activo'] ? 0 : 1;

        DBPDO::ejecutarConsulta(
            "UPDATE productos SET activo = :activo WHERE id = :id",
            [':activo' => $nuevoEstado, ':id' => $id]
        );
        return (bool)$nuevoEstado;
    }

    /**
     * Reduce el stock de un producto tras una venta.
     * @param int $id
     * @param int $cantidad
     */
    public static function reducirStock(int $id, int $cantidad): void {
        $sql = "UPDATE productos SET stock = stock - :cantidad WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':cantidad' => $cantidad,
            ':id'       => $id
        ]);
    }
}
