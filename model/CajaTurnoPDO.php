<?php
/**
 * Clase: CajaTurnoPDO
 * Gestiona la apertura/cierre de caja y retiradas de efectivo.
 */

require_once 'DBPDO.php';

class CajaTurnoPDO {
    public static function obtenerTurnoAbierto(): ?array {
        $q = DBPDO::ejecutarConsulta(
            "SELECT * FROM caja_turnos WHERE estado = 'abierto' ORDER BY id DESC LIMIT 1"
        );
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function abrirTurno(int $idUsuario, float $fondoInicial): int {
        if ($fondoInicial < 0) {
            $fondoInicial = 0;
        }
        DBPDO::ejecutarConsulta(
            "INSERT INTO caja_turnos (id_usuario_apertura, fondo_inicial) VALUES (:u, :f)",
            [':u' => $idUsuario, ':f' => $fondoInicial]
        );
        $q = DBPDO::ejecutarConsulta("SELECT id FROM caja_turnos ORDER BY id DESC LIMIT 1");
        return (int)$q->fetch(PDO::FETCH_ASSOC)['id'];
    }

    public static function registrarRetiro(int $idTurno, int $idUsuario, float $importe, string $concepto = ''): void {
        if ($importe <= 0) {
            return;
        }
        DBPDO::ejecutarConsulta(
            "INSERT INTO caja_movimientos (id_turno, id_usuario, tipo, importe, concepto) VALUES (:t, :u, 'retiro', :imp, :c)",
            [':t' => $idTurno, ':u' => $idUsuario, ':imp' => $importe, ':c' => $concepto]
        );
        DBPDO::ejecutarConsulta(
            "UPDATE caja_turnos SET total_retirado = total_retirado + :imp WHERE id = :t",
            [':imp' => $importe, ':t' => $idTurno]
        );
    }

    public static function cerrarTurno(
        int $idTurno,
        int $idUsuarioCierre,
        float $efectivoReal,
        float $fondoSiguiente
    ): void {
        DBPDO::ejecutarConsulta(
            "UPDATE caja_turnos
             SET fecha_cierre = NOW(),
                 id_usuario_cierre = :u,
                 efectivo_real = :real,
                 fondo_siguiente_turno = :fondo,
                 estado = 'cerrado'
             WHERE id = :id",
            [
                ':u'     => $idUsuarioCierre,
                ':real'  => $efectivoReal,
                ':fondo' => $fondoSiguiente,
                ':id'    => $idTurno,
            ]
        );
    }

    public static function listarRetiros(int $idTurno): array {
        try {
            $sql = "SELECT m.*, u.nombre as nombre_usuario
                    FROM caja_movimientos m
                    LEFT JOIN usuarios u ON m.id_usuario = u.id
                    WHERE m.id_turno = :t
                    ORDER BY m.created_at ASC";
            $q = DBPDO::ejecutarConsulta($sql, [':t' => $idTurno]);
            return $q->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Compatibilidad con BD antiguas sin columna id_usuario
            $sql = "SELECT m.*, NULL as nombre_usuario
                    FROM caja_movimientos m
                    WHERE m.id_turno = :t
                    ORDER BY m.created_at ASC";
            $q = DBPDO::ejecutarConsulta($sql, [':t' => $idTurno]);
            return $q->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Calcula el efectivo teórico disponible en caja para el turno abierto actual.
     * Fórmula aproximada:
     *   fondo_inicial
     * + ventas en efectivo (tickets completados desde la apertura)
     * - retiradas registradas
     * - devoluciones parciales de líneas (sobre tickets aún completados)
     */
    public static function obtenerEfectivoDisponible(): float {
        $turno = self::obtenerTurnoAbierto();
        if (!$turno) {
            return 0.0;
        }

        $fondoInicial   = (float)$turno['fondo_inicial'];
        $fechaApertura  = $turno['fecha_apertura'];
        $idTurno        = (int)$turno['id'];

        // Ventas en efectivo de tickets completados desde la apertura
        $qVentas = DBPDO::ejecutarConsulta(
            "SELECT COALESCE(SUM(total), 0) AS total_efectivo
             FROM ventas
             WHERE estado = 'completada'
               AND metodo_pago = 'efectivo'
               AND fecha >= :apertura",
            [':apertura' => $fechaApertura]
        );
        $rowVentas = $qVentas->fetch(PDO::FETCH_ASSOC);
        $totalEfectivoVentas = (float)($rowVentas['total_efectivo'] ?? 0);

        // Retiradas registradas en el turno
        $qRet = DBPDO::ejecutarConsulta(
            "SELECT COALESCE(SUM(importe), 0) AS total_retirado
             FROM caja_movimientos
             WHERE id_turno = :t AND tipo = 'retiro'",
            [':t' => $idTurno]
        );
        $rowRet = $qRet->fetch(PDO::FETCH_ASSOC);
        $totalRetirado = (float)($rowRet['total_retirado'] ?? 0);

        // Devoluciones parciales: líneas devueltas de tickets que siguen 'completados'
        $qDev = DBPDO::ejecutarConsulta(
            "SELECT COALESCE(SUM(lv.total_linea), 0) AS total_devuelto
             FROM lineas_venta lv
             JOIN ventas v ON lv.id_venta = v.id
             WHERE lv.devuelta = 1
               AND v.estado = 'completada'
               AND v.fecha >= :apertura",
            [':apertura' => $fechaApertura]
        );
        $rowDev = $qDev->fetch(PDO::FETCH_ASSOC);
        $totalDevueltoParcial = (float)($rowDev['total_devuelto'] ?? 0);

        $efectivo = $fondoInicial + $totalEfectivoVentas - $totalRetirado - $totalDevueltoParcial;
        return $efectivo > 0 ? $efectivo : 0.0;
    }
}

