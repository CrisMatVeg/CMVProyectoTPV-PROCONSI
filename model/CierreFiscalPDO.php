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
        // Busca pagos reales en turnos que aún no tienen cierre Z asignado.
        // num_z = 0 equivale a NULL en BD con NOT NULL sin default.
        $sql = "SELECT
                COALESCE(SUM(CASE WHEN p.metodo_pago = 'efectivo' THEN p.importe ELSE 0 END), 0) as total_efectivo,
                COALESCE(SUM(CASE WHEN p.metodo_pago = 'tarjeta' THEN p.importe ELSE 0 END), 0) as total_tarjeta,
                COALESCE(SUM(CASE WHEN p.metodo_pago = 'bizum' THEN p.importe ELSE 0 END), 0) as total_bizum,
                0 as total_a_cuenta,
                COALESCE(SUM(p.importe), 0) as total_general
            FROM pagos_venta p
            JOIN caja_turnos t ON p.id_turno = t.id
            JOIN ventas v ON v.id = p.id_venta
            WHERE (t.num_z IS NULL OR t.num_z = 0)
              AND v.estado NOT IN ('anulada')";
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

        // 1. Marcamos TODAS las ventas pendientes con el ID del cierre.
        // num_z = 0 equivale a "sin cierre" cuando la BD tiene INT NOT NULL sin default.
        $sqlUpdateVentas = "UPDATE ventas SET num_z = :idZ WHERE num_z IS NULL OR num_z = 0";
        DBPDO::ejecutarConsulta($sqlUpdateVentas, [':idZ' => $idZ]);

        // 2. Si se pasó un ID de turno específico (el que se acaba de cerrar), lo marcamos
        if ($idTurno) {
            $sqlUpdateTurnos = "UPDATE caja_turnos SET num_z = :idZ WHERE id = :idT";
            DBPDO::ejecutarConsulta($sqlUpdateTurnos, [':idZ' => $idZ, ':idT' => $idTurno]);
        }

        // 3. Marcamos todos los demás turnos cerrados que estaban sin Z
        $sqlUpdateResto = "UPDATE caja_turnos SET num_z = :idZ WHERE (num_z IS NULL OR num_z = 0) AND estado = 'cerrado'";
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

        // Pre-agregamos ventas y deudas por cierre ANTES del join para evitar BNL full-scan.
        $sql = "SELECT
                cf.*,
                u.nombre as nombre_usuario,
                COALESCE(vagg.num_tickets, 0) as num_tickets,
                COALESCE(vagg.primera_venta, cf.fecha) as primera_venta,
                COALESCE(vagg.ultima_venta, cf.fecha) as ultima_venta,
                COALESCE(vagg.total_a_cuenta, 0) as total_a_cuenta,
                COALESCE(dagg.deuda_generada, 0) as deuda_generada
            FROM (
                SELECT id
                FROM cierres_fiscales
                WHERE fecha >= :desde AND fecha <= :hasta
                ORDER BY $ordenPor $ordenDir
                LIMIT :limit OFFSET :offset
            ) AS sub
            JOIN cierres_fiscales cf ON cf.id = sub.id
            JOIN usuarios u ON cf.id_usuario = u.id
            LEFT JOIN (
                SELECT
                    num_z,
                    COUNT(*) as num_tickets,
                    MIN(fecha) as primera_venta,
                    MAX(fecha) as ultima_venta,
                    SUM(CASE WHEN metodo_pago = 'a_cuenta' THEN total ELSE 0 END) as total_a_cuenta
                FROM ventas
                WHERE estado NOT IN ('anulada') AND num_z > 0
                GROUP BY num_z
            ) vagg ON vagg.num_z = cf.id
            LEFT JOIN (
                SELECT id_cierre_fiscal, SUM(importe) as deuda_generada
                FROM caja_deudas
                GROUP BY id_cierre_fiscal
            ) dagg ON dagg.id_cierre_fiscal = cf.id
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
    /**
     * Obtiene un cierre específico por su ID con detalles de tickets y deudas.
     */
    public static function obtenerCierrePorId(int $id): ?array
    {
        $sql = "SELECT
                cf.*,
                u.nombre as nombre_usuario,
                COALESCE(vagg.num_tickets, 0) as num_tickets,
                COALESCE(vagg.primera_venta, cf.fecha) as primera_venta,
                COALESCE(vagg.ultima_venta, cf.fecha) as ultima_venta,
                COALESCE(vagg.total_a_cuenta, 0) as total_a_cuenta,
                COALESCE(dagg.deuda_generada, 0) as deuda_generada
            FROM cierres_fiscales cf
            JOIN usuarios u ON cf.id_usuario = u.id
            LEFT JOIN (
                SELECT
                    num_z,
                    COUNT(*) as num_tickets,
                    MIN(fecha) as primera_venta,
                    MAX(fecha) as ultima_venta,
                    SUM(CASE WHEN metodo_pago = 'a_cuenta' THEN total ELSE 0 END) as total_a_cuenta
                FROM ventas
                WHERE estado NOT IN ('anulada') AND num_z = :id_v
                GROUP BY num_z
            ) vagg ON vagg.num_z = cf.id
            LEFT JOIN (
                SELECT id_cierre_fiscal, SUM(importe) as deuda_generada
                FROM caja_deudas
                WHERE id_cierre_fiscal = :id_d
                GROUP BY id_cierre_fiscal
            ) dagg ON dagg.id_cierre_fiscal = cf.id
            WHERE cf.id = :id";
        
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $id, ':id_v' => $id, ':id_d' => $id]);
        $res = $q->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }
}
