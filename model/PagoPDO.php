<?php

/**
 * Clase: PagoPDO
 * Gestiona los pagos parciales de las ventas a crédito/a cuenta.
 */
require_once __DIR__ . '/DBPDO.php';

class PagoPDO
{
    /**
     * Registra un nuevo pago parcial para una venta.
     */
    public static function registrarPago(int $idVenta, float $importe, string $metodoPago, ?int $idUsuario = null, ?string $notas = null): int
    {
        $sql = "INSERT INTO pagos_venta (id_venta, importe, metodo_pago, id_usuario, notas)
                VALUES (:idVenta, :importe, :metodoPago, :idUsuario, :notas)";

        DBPDO::ejecutarConsulta($sql, [
            ':idVenta'    => $idVenta,
            ':importe'    => round($importe, 2),
            ':metodoPago' => $metodoPago,
            ':idUsuario'  => $idUsuario,
            ':notas'      => $notas
        ]);

        $q = DBPDO::ejecutarConsulta("SELECT id FROM pagos_venta ORDER BY id DESC LIMIT 1");
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return (int)$row['id'];
    }

    /**
     * Obtiene todos los pagos realizados para una venta específica.
     */
    public static function obtenerPagosPorVenta(int $idVenta): array
    {
        $sql = "SELECT p.*, u.nombre AS nombre_usuario 
                FROM pagos_venta p 
                LEFT JOIN usuarios u ON p.id_usuario = u.id 
                WHERE p.id_venta = :idVenta 
                ORDER BY p.fecha ASC";
        $q = DBPDO::ejecutarConsulta($sql, [':idVenta' => $idVenta]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
