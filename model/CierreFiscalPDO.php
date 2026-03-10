<?php

/**
 * Clase: CierreFiscalPDO
 * Gestiona los informes de cierre de caja.
 */
require_once 'DBPDO.php';

class CierreFiscalPDO
{

    /**
     * Obtiene el resumen de ventas para el cierre actual.
     */
    public static function obtenerResumenParaCierre(): array
    {
        // Ventas no cerradas (num_z is null)
        $sql = "SELECT 
                    SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END) as total_efectivo,
                    SUM(CASE WHEN metodo_pago = 'tarjeta' THEN total ELSE 0 END) as total_tarjeta,
                    SUM(CASE WHEN metodo_pago = 'bizum' THEN total ELSE 0 END) as total_bizum,
                    SUM(CASE WHEN metodo_pago = 'financiado' THEN total ELSE 0 END) as total_financiado,
                    SUM(total) as total_general
                FROM ventas 
                WHERE num_z IS NULL";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un nuevo cierre fiscal.
     */
    public static function realizarCierre($idUsuario, $totalEfectivo, $totalTarjeta, $totalBizum, $totalFinanciado, $totalGeneral): int
    {
        $sql = "INSERT INTO cierres_fiscales (id_usuario, fecha, total_efectivo, total_tarjeta, total_bizum, total_financiado, total_general) 
                VALUES (:user, NOW(), :efe, :tar, :biz, :fin, :total)";
        $params = [
            ':user'  => $idUsuario,
            ':efe'   => $totalEfectivo,
            ':tar'   => $totalTarjeta,
            ':biz'   => $totalBizum,
            ':fin'   => $totalFinanciado,
            ':total' => $totalGeneral
        ];
        DBPDO::ejecutarConsulta($sql, $params);

        $q = DBPDO::ejecutarConsulta("SELECT id FROM cierres_fiscales ORDER BY id DESC LIMIT 1");
        $idZ = (int)$q->fetch(PDO::FETCH_ASSOC)['id'];

        // Marcamos las ventas con el ID del cierre
        $sqlUpdate = "UPDATE ventas SET num_z = :idZ WHERE num_z IS NULL";
        DBPDO::ejecutarConsulta($sqlUpdate, [':idZ' => $idZ]);

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
                    COALESCE(SUM(d.importe), 0) as deuda_generada
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
