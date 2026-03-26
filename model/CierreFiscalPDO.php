<?php

/**
 * Clase: CierreFiscalPDO
 * Gestiona los informes de cierre de caja.
 */
require_once __DIR__ . '/DBPDO.php';

class CierreFiscalPDO
{

    /**
     * Obtiene el resumen de ventas para el cierre actual.
     */
    public static function obtenerResumenParaCierre(): array
    {
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) as total_efectivo,
                COALESCE(SUM(CASE WHEN metodo_pago = 'tarjeta' THEN total ELSE 0 END), 0) as total_tarjeta,
                COALESCE(SUM(CASE WHEN metodo_pago = 'bizum' THEN total ELSE 0 END), 0) as total_bizum,
                COALESCE(SUM(CASE WHEN metodo_pago = 'a_cuenta' THEN total ELSE 0 END), 0) as total_a_cuenta,
                COALESCE(SUM(CASE WHEN metodo_pago != 'a_cuenta' THEN total ELSE 0 END), 0) as total_general
            FROM ventas 
            WHERE num_z IS NULL";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un nuevo cierre fiscal.
     */
    public static function realizarCierre($idUsuario, $totalEfectivo, $totalTarjeta, $totalBizum, $totalGeneral, $idTurno = null): int
    {
        $sql = "INSERT INTO cierres_fiscales (id_usuario, fecha, total_efectivo, total_tarjeta, total_bizum, total_general) 
                VALUES (:user, NOW(), :efe, :tar, :biz, :total)";
        $params = [
            ':user'  => $idUsuario,
            ':efe'   => $totalEfectivo,
            ':tar'   => $totalTarjeta,
            ':biz'   => $totalBizum,
            ':total' => $totalGeneral
        ];
        DBPDO::ejecutarConsulta($sql, $params);

        $idZ = (int)DBPDO::getPDO()->lastInsertId();

        // 1. Marcamos TODAS las ventas pendientes con el ID del cierre
        $sqlUpdateVentas = "UPDATE ventas SET num_z = :idZ WHERE num_z IS NULL";
        DBPDO::ejecutarConsulta($sqlUpdateVentas, [':idZ' => $idZ]);

        // 2. Si se pasó un ID de turno específico (el que se acaba de cerrar), lo marcamos
        if ($idTurno) {
            $sqlUpdateTurnos = "UPDATE caja_turnos SET num_z = :idZ WHERE id = :idT";
            DBPDO::ejecutarConsulta($sqlUpdateTurnos, [':idZ' => $idZ, ':idT' => $idTurno]);
        }

        // 3. Marcamos todos los demás turnos cerrados que estaban sin Z
        $sqlUpdateResto = "UPDATE caja_turnos SET num_z = :idZ WHERE num_z IS NULL AND estado = 'cerrado'";
        DBPDO::ejecutarConsulta($sqlUpdateResto, [':idZ' => $idZ]);

        return $idZ;
    }

    /**
     * Lista históricos de cierres.
     */
    public static function listarCierres(): array
    {
        $sql = "SELECT 
                cf.*,
                u.nombre as nombre_usuario,
                COUNT(DISTINCT v.id) as num_tickets,
                COALESCE(MIN(v.fecha), cf.fecha) as primera_venta,
                COALESCE(MAX(v.fecha), cf.fecha) as ultima_venta,
                COALESCE(SUM(d.importe), 0) as deuda_generada,
                COALESCE(SUM(CASE WHEN v.metodo_pago = 'a_cuenta' THEN v.total ELSE 0 END), 0) as total_a_cuenta
            FROM cierres_fiscales cf
            JOIN usuarios u ON cf.id_usuario = u.id
            LEFT JOIN ventas v ON v.num_z = cf.id
            LEFT JOIN caja_deudas d ON d.id_cierre_fiscal = cf.id
            GROUP BY cf.id
            ORDER BY cf.fecha DESC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
