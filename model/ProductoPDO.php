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
     * Obtiene los productos de la base de datos.
     * @param bool $soloActivos Si es true, solo devuelve productos con activo=1. Default true.
     * @return Producto[]
     */
    public static function listarProductos(bool $soloActivos = true): array {
        $sql = "SELECT * FROM productos" . ($soloActivos ? " WHERE activo = 1" : "");
        $consulta = DBPDO::ejecutarConsulta($sql);

        $productos = [];
        while ($registro = $consulta->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = new Producto(
                $registro['id'],
                $registro['referencia'],
                $registro['nombre'],
                $registro['descripcion'],
                $registro['precio_coste'],
                $registro['precio_venta'],
                $registro['iva'],
                $registro['stock_actual'],
                $registro['stock_minimo'],
                $registro['meses_garantia'],
                $registro['icono'],
                $registro['categoria'],
                $registro['variantes'],
                $registro['activo'],
                $registro['requiere_serial'] ?? 0
            );
        }
        return $productos;
    }

    /**
     * Añade un nuevo producto a la base de datos.
     * @param array $datos
     * @return array El nuevo producto como array asociativo
     */
    public static function añadirProducto(array $datos): array {
        $iconoDato = $datos['icono'] ?? '';
        if (strpos($iconoDato, 'data:image') === 0) {
            $parts = explode(',', $iconoDato);
            $iconoDato = base64_decode($parts[1]);
        }

        $sql = "INSERT INTO productos (referencia, nombre, descripcion, precio_coste, precio_venta, iva, stock_actual, stock_minimo, meses_garantia, icono, categoria, variantes, activo, requiere_serial)
                VALUES (:referencia, :nombre, :descripcion, :precio_coste, :precio_venta, :iva, :stock_actual, :stock_minimo, :meses_garantia, :icono, :categoria, :variantes, 1, :requiere_serial)";
        
        DBPDO::ejecutarConsulta($sql, [
            ':referencia'     => mb_substr(trim($datos['referencia']), 0, 50),
            ':nombre'         => mb_substr(trim($datos['nombre']), 0, 100),
            ':descripcion'    => $datos['descripcion'] ?? '',
            ':precio_coste'   => round((float)($datos['precio_coste'] ?? 0), 2),
            ':precio_venta'   => round((float)($datos['precio_venta'] ?? 0), 2),
            ':iva'            => round((float)($datos['iva'] ?? 21), 2),
            ':stock_actual'   => (int)($datos['stock_actual'] ?? 0),
            ':stock_minimo'   => (int)($datos['stock_minimo'] ?? 0),
            ':meses_garantia' => (int)($datos['meses_garantia'] ?? 24),
            ':icono'          => $iconoDato,
            ':categoria'      => mb_substr(trim($datos['categoria']), 0, 50),
            ':variantes'      => isset($datos['variantes']) ? json_encode($datos['variantes']) : null,
            ':requiere_serial' => (int)($datos['requiere_serial'] ?? 0)
        ]);

        $q = DBPDO::ejecutarConsulta("SELECT * FROM productos ORDER BY id DESC LIMIT 1");
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza los datos de un producto existente.
     */
    public static function editarProducto(int $id, array $datos): void {
        $iconoDato = $datos['icono'] ?? '';
        if (strpos($iconoDato, 'data:image') === 0) {
            $parts = explode(',', $iconoDato);
            $iconoDato = base64_decode($parts[1]);
        }

        $sql = "UPDATE productos
                SET referencia = :referencia, nombre = :nombre, descripcion = :descripcion, 
                    precio_coste = :precio_coste, precio_venta = :precio_venta, iva = :iva, 
                    stock_actual = :stock_actual, stock_minimo = :stock_minimo, 
                    meses_garantia = :meses_garantia, icono = :icono, categoria = :categoria,
                    variantes = :variantes, requiere_serial = :requiere_serial
                WHERE id = :id";
        
        DBPDO::ejecutarConsulta($sql, [
            ':referencia'     => mb_substr(trim($datos['referencia']), 0, 50),
            ':nombre'         => mb_substr(trim($datos['nombre']), 0, 100),
            ':descripcion'    => $datos['descripcion'] ?? '',
            ':precio_coste'   => round((float)($datos['precio_coste'] ?? 0), 2),
            ':precio_venta'   => round((float)($datos['precio_venta'] ?? 0), 2),
            ':iva'            => round((float)($datos['iva'] ?? 21), 2),
            ':stock_actual'   => (int)($datos['stock_actual'] ?? 0),
            ':stock_minimo'   => (int)($datos['stock_minimo'] ?? 0),
            ':meses_garantia' => (int)($datos['meses_garantia'] ?? 24),
            ':icono'          => $iconoDato,
            ':categoria'      => mb_substr(trim($datos['categoria']), 0, 50),
            ':variantes'      => isset($datos['variantes']) ? json_encode($datos['variantes']) : null,
            ':requiere_serial' => (int)($datos['requiere_serial'] ?? 0),
            ':id'             => $id,
        ]);
    }

    public static function eliminarProducto(int $id): void {
        DBPDO::ejecutarConsulta("DELETE FROM productos WHERE id = :id", [':id' => $id]);
    }

    public static function toggleBaja(int $id): bool {
        $q = DBPDO::ejecutarConsulta("SELECT activo FROM productos WHERE id = :id", [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $nuevoEstado = $row['activo'] ? 0 : 1;

        DBPDO::ejecutarConsulta("UPDATE productos SET activo = :activo WHERE id = :id", [':activo' => $nuevoEstado, ':id' => $id]);
        return (bool)$nuevoEstado;
    }

    public static function reducirStock(int $id, int $cantidad): void {
        $sql = "UPDATE productos SET stock_actual = stock_actual - :cantidad WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [':cantidad' => $cantidad, ':id' => $id]);
    }

    public static function aumentarStock(int $id, int $cantidad): void {
        $sql = "UPDATE productos SET stock_actual = stock_actual + :cantidad WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [':cantidad' => $cantidad, ':id' => $id]);
    }

    public static function obtenerProductoPorId(int $id): ?array {
        $q = DBPDO::ejecutarConsulta("SELECT * FROM productos WHERE id = :id", [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
