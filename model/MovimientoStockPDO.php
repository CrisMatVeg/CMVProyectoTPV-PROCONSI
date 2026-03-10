<?php

/**
 * Clase: MovimientoStockPDO
 *
 * Gestiona el registro y consulta del log de movimientos de stock.
 */
require_once __DIR__ . '/DBPDO.php';

class MovimientoStockPDO
{
    /**
     * Registra un nuevo movimiento en el historial de stock.
     *
     * @param int $producto_id ID del producto
     * @param string $tipo_movimiento ('venta', 'devolucion', 'compra', 'ajuste', 'inicial')
     * @param int $cantidad Unidades que entran (>0) o salen (<0)
     * @param int|null $usuario_id ID del usuario responsable
     * @param string|null $notas Notas adicionales
     * @param PDO|null $db Conexión PDO opcional para transacciones
     * @return bool
     */
    public static function registrarMovimiento(
        int $producto_id,
        string $tipo_movimiento,
        int $cantidad,
        ?int $usuario_id = null,
        ?string $notas = null,
        ?PDO $db = null
    ) {
        $sql = "INSERT INTO movimientos_stock (producto_id, tipo_movimiento, cantidad, usuario_id, notas)
                VALUES (:producto_id, :tipo_movimiento, :cantidad, :usuario_id, :notas)";

        $params = [
            ':producto_id' => $producto_id,
            ':tipo_movimiento' => $tipo_movimiento,
            ':cantidad' => $cantidad,
            ':usuario_id' => $usuario_id,
            ':notas' => $notas
        ];

        try {
            if ($db) {
                $stmt = $db->prepare($sql);
                return $stmt->execute($params);
            } else {
                DBPDO::ejecutarConsulta($sql, $params);
                return true;
            }
        } catch (Exception $e) {
            error_log("Error al registrar movimiento de stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el historial de movimientos de un producto.
     *
     * @param int $producto_id ID del producto
     * @return array
     */
    public static function obtenerHistorialPorProducto(int $producto_id)
    {
        $sql = "SELECT m.*, u.nombre as nombre_usuario 
                FROM movimientos_stock m
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                WHERE m.producto_id = :producto_id
                ORDER BY m.fecha DESC";

        try {
            $resultado = DBPDO::ejecutarConsulta($sql, [':producto_id' => $producto_id]);
            return $resultado->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener historial de stock: " . $e->getMessage());
            return [];
        }
    }
}
