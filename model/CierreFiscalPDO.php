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
        // [NUEVO] El resumen ahora se basa en los pagos REALES realizados (pagos_venta)
        // para incluir abonos a cuenta y pagos parciales en el reporte del día.
        // Un pago se incluye en el cierre si su turno asociado todavía no tiene num_z.
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN p.metodo_pago = 'efectivo' THEN p.importe ELSE 0 END), 0) as total_efectivo,
                COALESCE(SUM(CASE WHEN p.metodo_pago = 'tarjeta' THEN p.importe ELSE 0 END), 0) as total_tarjeta,
                COALESCE(SUM(CASE WHEN p.metodo_pago = 'bizum' THEN p.importe ELSE 0 END), 0) as total_bizum,
                0 as total_a_cuenta,
                COALESCE(SUM(p.importe), 0) as total_general
            FROM pagos_venta p
            JOIN caja_turnos t ON p.id_turno = t.id
            WHERE t.num_z IS NULL";
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

        // 4. Recalcular analítica del día
        try {
            require_once __DIR__ . '/AnaliticaPDO.php';
            AnaliticaPDO::recalcularDia(date('Y-m-d'));
        } catch (Exception $e) {
            // No bloqueamos el cierre si falla la analítica, pero lo registramos
            error_log("Error al recalcular analítica en cierre: " . $e->getMessage());
        }

        return $idZ;
    }

    /**
     * Lista históricos de cierres.
     */
    public static function listarCierres(string $desde, string $hasta, string $ordenPor = 'fecha', string $ordenDir = 'DESC', int $limit = 50, int $offset = 0): array
    {
        // Whitelist
        $cols = ['fecha', 'total_general', 'id'];
        $ordenPor = in_array($ordenPor, $cols) ? $ordenPor : 'fecha';
        $ordenDir = strtoupper($ordenDir) === 'ASC' ? 'ASC' : 'DESC';

        // Técnica: Late Row Lookup. Primero obtenemos los IDs de los cierres que cumplen los filtros.
        $sql = "SELECT 
                cf.*,
                u.nombre as nombre_usuario,
                COUNT(DISTINCT v.id) as num_tickets,
                COALESCE(MIN(v.fecha), cf.fecha) as primera_venta,
                COALESCE(MAX(v.fecha), cf.fecha) as ultima_venta,
                COALESCE(SUM(d.importe), 0) as deuda_generada,
                COALESCE(SUM(CASE WHEN v.metodo_pago = 'a_cuenta' THEN v.total ELSE 0 END), 0) as total_a_cuenta
            FROM (
                SELECT id 
                FROM cierres_fiscales 
                WHERE fecha >= :desde AND fecha <= :hasta
                ORDER BY $ordenPor $ordenDir
                LIMIT :limit OFFSET :offset
            ) AS sub
            JOIN cierres_fiscales cf ON cf.id = sub.id
            JOIN usuarios u ON cf.id_usuario = u.id
            LEFT JOIN ventas v ON v.num_z = cf.id
            LEFT JOIN caja_deudas d ON d.id_cierre_fiscal = cf.id
            GROUP BY cf.id
            ORDER BY cf.$ordenPor $ordenDir";
            
        $params = [
            ':desde' => $desde . ' 00:00:00',
            ':hasta' => $hasta . ' 23:59:59'
        ];

        $sql = str_replace([':limit', ':offset'], [(int)$limit, (int)$offset], $sql);

        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de cierres para paginación.
     */
    public static function contarCierres(string $desde, string $hasta): int
    {
        $sql = "SELECT COUNT(*) as total FROM cierres_fiscales WHERE fecha >= :desde AND fecha <= :hasta";
        $params = [
            ':desde' => $desde . ' 00:00:00',
            ':hasta' => $hasta . ' 23:59:59'
        ];
        $q = DBPDO::ejecutarConsulta($sql, $params);
        $res = $q->fetch(PDO::FETCH_ASSOC);
        return (int)($res['total'] ?? 0);
    }
}
