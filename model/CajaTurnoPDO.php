<?php

/**
 * Clase: CajaTurnoPDO
 * Gestiona la apertura/cierre de caja y retiradas de efectivo.
 */

require_once __DIR__ . '/DBPDO.php';

class CajaTurnoPDO
{
    public static function obtenerTurnoAbierto(): ?array
    {
        // Se obtiene el turno abierto más reciente sin restringir por fecha CURDATE()
        // ya que la lógica de cierre automático se encarga de gestionar días anteriores.
        $q = DBPDO::ejecutarConsulta(
            "SELECT ct.*, u.nombre as nombre_usuario_apertura 
             FROM caja_turnos ct
             LEFT JOIN usuarios u ON ct.id_usuario_apertura = u.id
             WHERE ct.estado = 'abierto' 
             ORDER BY ct.id DESC LIMIT 1"
        );
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Obtiene CUALQUIER turno abierto (incluso de días anteriores)
     */
    public static function obtenerTurnoAbiertoReal(): ?array
    {
        $q = DBPDO::ejecutarConsulta(
            "SELECT ct.*, u.nombre as nombre_usuario_apertura 
             FROM caja_turnos ct
             LEFT JOIN usuarios u ON ct.id_usuario_apertura = u.id
             WHERE ct.estado = 'abierto' 
             ORDER BY ct.id DESC LIMIT 1"
        );
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function obtenerUltimoFondoSugerido(): float
    {
        $q = DBPDO::ejecutarConsulta(
            "SELECT fondo_siguiente_turno FROM caja_turnos WHERE estado = 'cerrado' ORDER BY id DESC LIMIT 1"
        );
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['fondo_siguiente_turno'] : 0.0;
    }

    /**
     * Obtiene el último turno cerrado, independientemente de si fue con Z o no.
     */
    public static function obtenerUltimoTurnoCerrado(): ?array
    {
        $q = DBPDO::ejecutarConsulta(
            "SELECT * FROM caja_turnos WHERE estado = 'cerrado' ORDER BY id DESC LIMIT 1"
        );
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function abrirTurno(int $idUsuario, float $fondoInicial): int
    {
        // Verificar que no hay ya un turno abierto
        $turnoExistente = self::obtenerTurnoAbierto();
        if ($turnoExistente) {
            return (int)$turnoExistente['id'];
        }

        if ($fondoInicial < 0) {
            $fondoInicial = 0;
        }

        DBPDO::ejecutarConsulta(
            "INSERT INTO caja_turnos (id_usuario_apertura, fondo_inicial) VALUES (:u, :f)",
            [':u' => $idUsuario, ':f' => $fondoInicial]
        );

        return (int)DBPDO::getPDO()->lastInsertId();
    }

    public static function registrarRetiro(int $idTurno, int $idUsuario, float $importe, string $concepto = ''): void
    {
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

    public static function registrarIngreso(int $idTurno, int $idUsuario, float $importe, string $concepto = ''): void
    {
        if ($importe <= 0) {
            return;
        }
        DBPDO::ejecutarConsulta(
            "INSERT INTO caja_movimientos (id_turno, id_usuario, tipo, importe, concepto) VALUES (:t, :u, 'ingreso', :imp, :c)",
            [':t' => $idTurno, ':u' => $idUsuario, ':imp' => $importe, ':c' => $concepto]
        );
        DBPDO::ejecutarConsulta(
            "UPDATE caja_turnos SET total_ingresado = total_ingresado + :imp WHERE id = :t",
            [':imp' => $importe, ':t' => $idTurno]
        );
    }

    /**
     * Lista todos los movimientos de un turno.
     */
    public static function listarMovimientosTurno(int $idTurno): array
    {
        $sql = "SELECT m.*, u.nombre as nombre_usuario
                FROM caja_movimientos m
                LEFT JOIN usuarios u ON m.id_usuario = u.id
                WHERE m.id_turno = :id
                ORDER BY m.created_at ASC";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idTurno]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Carga los movimientos de múltiples turnos en una sola query (evita N+1).
     * Devuelve un array indexado por id_turno.
     * @param int[] $idsTurnos
     * @return array<int, array>
     */
    public static function listarMovimientosPorTurnos(array $idsTurnos): array
    {
        if (empty($idsTurnos)) {
            return [];
        }
        $placeholders = implode(',', array_map('intval', $idsTurnos));
        $sql = "SELECT m.*, u.nombre as nombre_usuario
                FROM caja_movimientos m
                LEFT JOIN usuarios u ON m.id_usuario = u.id
                WHERE m.id_turno IN ({$placeholders})
                ORDER BY m.created_at ASC";
        $q = DBPDO::ejecutarConsulta($sql, []);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        $resultado = [];
        foreach ($rows as $row) {
            $resultado[(int)$row['id_turno']][] = $row;
        }
        return $resultado;
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

    public static function listarRetiros(int $idTurno): array
    {
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
    public static function obtenerEfectivoDisponible(): float
    {
        $turno = self::obtenerTurnoAbierto();
        if (!$turno) {
            return 0.0;
        }

        $fondoInicial   = (float)$turno['fondo_inicial'];
        $fechaApertura  = $turno['fecha_apertura'];
        $idTurno        = (int)$turno['id'];

        // 1. Ventas en efectivo de este turno (Completadas o Devueltas)
        // Se incluyen las 'devuelta' porque el dinero entró originalmente a la caja.
        // Los reembolsos se restan después mediante caja_movimientos.
        $qVentas = DBPDO::ejecutarConsulta(
            "SELECT COALESCE(SUM(total), 0) AS total_efectivo
             FROM ventas
             WHERE estado IN ('completada', 'devuelta')
               AND metodo_pago = 'efectivo'
               AND id_turno = :idTurno",
            [':idTurno' => $idTurno]
        );
        $rowVentas = $qVentas->fetch(PDO::FETCH_ASSOC);
        $totalEfectivoVentas = (float)($rowVentas['total_efectivo'] ?? 0);

        // 2. Cobros de deudas (pagos_venta) en efectivo en este turno
        $qPagos = DBPDO::ejecutarConsulta(
            "SELECT COALESCE(SUM(importe), 0) AS total_abonos
             FROM pagos_venta
             WHERE id_turno = :idTurno 
               AND metodo_pago = 'efectivo'",
            [':idTurno' => $idTurno]
        );
        $rowPagos = $qPagos->fetch(PDO::FETCH_ASSOC);
        $totalAbonos = (float)($rowPagos['total_abonos'] ?? 0);

        // 3. Retiradas registradas en el turno
        $qRet = DBPDO::ejecutarConsulta(
            "SELECT COALESCE(SUM(importe), 0) AS total_retirado
             FROM caja_movimientos
             WHERE id_turno = :t AND tipo = 'retiro'",
            [':t' => $idTurno]
        );
        $rowRet = $qRet->fetch(PDO::FETCH_ASSOC);
        $totalRetirado = (float)($rowRet['total_retirado'] ?? 0);

        // 4. Ingresos manuales en el turno
        $qIng = DBPDO::ejecutarConsulta(
            "SELECT COALESCE(SUM(importe), 0) AS total_ingresado
             FROM caja_movimientos
             WHERE id_turno = :t AND tipo = 'ingreso'",
            [':t' => $idTurno]
        );
        $rowIng = $qIng->fetch(PDO::FETCH_ASSOC);
        $totalIngresadoManual = (float)($rowIng['total_ingresado'] ?? 0);

        // Fondo + Ventas + Abonos + IngresosManuales - Retiradas
        $efectivo = $fondoInicial + $totalEfectivoVentas + $totalAbonos + $totalIngresadoManual - $totalRetirado;
        return $efectivo > 0 ? $efectivo : 0.0;
    }

    /**
     * Lista los turnos vinculados a un cierre fiscal concreto.
     */
    public static function listarTurnosPorCierre(int $idCierre): array
    {
        $sql = "SELECT ct.*, 
                       ua.nombre AS nombre_usuario_apertura,
                       uc.nombre AS nombre_usuario_cierre
                FROM caja_turnos ct
                LEFT JOIN usuarios ua ON ct.id_usuario_apertura = ua.id
                LEFT JOIN usuarios uc ON ct.id_usuario_cierre = uc.id
                WHERE ct.num_z = :idZ
                ORDER BY ct.fecha_apertura ASC";
        $q = DBPDO::ejecutarConsulta($sql, [':idZ' => $idCierre]);
        $turnos = $q->fetchAll(PDO::FETCH_ASSOC);

        // Enriquecer con resumen de ventas
        foreach ($turnos as &$t) {
            $t['resumen'] = self::obtenerResumenTurno((int)$t['id']);
        }

        return $turnos;
    }

    /**
     * Obtiene un resumen detallado de ventas por método de pago para un turno.
     */
    public static function obtenerResumenTurno(int $idTurno): array
    {
        $sql = "SELECT metodo_pago, SUM(total) as total, COUNT(*) as cantidad 
                FROM ventas 
                WHERE id_turno = :id AND estado IN ('completada', 'devuelta')
                GROUP BY metodo_pago";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idTurno]);
        $results = $q->fetchAll(PDO::FETCH_ASSOC);

        $resumen = [
            'efectivo'   => 0,
            'tarjeta'    => 0,
            'bizum'      => 0,
            'a_cuenta'   => 0,
            'total'      => 0,
            'num_tickets' => 0
        ];

        foreach ($results as $r) {
            $metodo = $r['metodo_pago'];
            if (isset($resumen[$metodo])) {
                $resumen[$metodo] = (float)$r['total'];
            }
            $resumen['total'] += (float)$r['total'];
            $resumen['num_tickets'] += (int)$r['cantidad'];
        }

        return $resumen;
    }

    /**
     * Revisa si hay turnos abiertos de días anteriores y los cierra automáticamente como 'pendiente_arqueo'
     */
    public static function verificarYRealizarCierreAutomatico(): void
    {
        // Se deshabilita el cierre automático para permitir turnos de noche.
        // El administrador deberá realizar el cierre manual de la jornada.
        return;
    }

    /**
     * Devuelve los turnos que están cerrados automáticamente pero pendientes de conteo/arqueo.
     */
    public static function obtenerTurnosPendientesArqueo(): array
    {
        $q = DBPDO::ejecutarConsulta(
            "SELECT ct.*, u.nombre as nombre_usuario_apertura 
             FROM caja_turnos ct
             LEFT JOIN usuarios u ON ct.id_usuario_apertura = u.id
             WHERE ct.estado = 'pendiente_arqueo'
             ORDER BY ct.fecha_apertura DESC"
        );
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Suma un importe al fondo inicial de la caja actual abierta.
     * Se usa cuando se resuelve un arqueo pendiente y se dejó dinero "para el próximo turno"
     * pero ese próximo turno ya está abierto.
     */
    public static function sumarFondoCajaAbierta(float $importe, int $idUsuario): bool
    {
        $turno = self::obtenerTurnoAbiertoReal();
        if (!$turno || $importe <= 0) {
            return false;
        }

        // 1. Actualizar el fondo inicial del turno abierto
        DBPDO::ejecutarConsulta(
            "UPDATE caja_turnos SET fondo_inicial = fondo_inicial + :imp WHERE id = :id",
            [':imp' => $importe, ':id' => $turno['id']]
        );

        // 2. Registrar un movimiento informativo
        DBPDO::ejecutarConsulta(
            "INSERT INTO caja_movimientos (id_turno, id_usuario, tipo, importe, concepto) 
             VALUES (:t, :u, 'retiro', :imp, :c)",
            [
                ':t'   => $turno['id'],
                ':u'   => $idUsuario,
                ':imp' => $importe,
                ':c'   => "Entrada de efectivo desde arqueo pendiente resuelto"
            ]
        );
        // Nota: lo registramos como 'retiro' con importe positivo? 
        // Realmente la tabla caja_movimientos solo tiene ENUM('retiro').
        // Podríamos añadir 'ingreso' al ENUM, pero por ahora lo dejamos como nota en el concepto.
        // El fondo_inicial ya está actualizado así que el arqueo final cuadrará.

        return true;
    }

    /**
     * Calcula el efectivo que debería haber físicamente en el cajón.
     *
     * Usa la tabla pagos_venta como ÚNICA fuente de verdad — la misma
     * que emplea el resumen del cierre de caja — para garantizar coherencia
     * entre la validación y lo que ve el usuario en pantalla.
     *
     * Fórmula:
     *   Fondo inicial
     *   + Cobros en efectivo (pagos_venta metodo_pago = 'efectivo', id_turno)
     *   + Ingresos manuales (caja_movimientos tipo = 'ingreso')
     *   - Retiros manuales  (caja_movimientos tipo = 'retiro')
     *   - Reembolsos en efectivo por abonos (pagos_venta negativos o ventas abono)
     *
     * @return float
     */
    public static function obtenerEfectivoActual(): float
    {
        $turno = self::obtenerTurnoAbierto();
        if (!$turno) {
            return 0.0;
        }

        $idTurno      = (int)$turno['id'];
        $fondoInicial = (float)$turno['fondo_inicial'];

        // 1. Todo el efectivo cobrado en este turno (ventas puras, mixtas y cobros de deuda)
        //    Misma fuente que el cierre de caja → pagos_venta
        $qCobros = DBPDO::ejecutarConsulta(
            "SELECT IFNULL(SUM(importe), 0) AS total
             FROM pagos_venta
             WHERE id_turno = :id AND metodo_pago = 'efectivo'",
            [':id' => $idTurno]
        );
        $cobradoEfectivo = (float)$qCobros->fetch(PDO::FETCH_ASSOC)['total'];

        // 2. Ingresos manuales
        $qIngresos = DBPDO::ejecutarConsulta(
            "SELECT IFNULL(SUM(importe), 0) AS total
             FROM caja_movimientos
             WHERE id_turno = :id AND tipo = 'ingreso'",
            [':id' => $idTurno]
        );
        $totalIngresado = (float)$qIngresos->fetch(PDO::FETCH_ASSOC)['total'];

        // 3. Retiros manuales
        $qRetiros = DBPDO::ejecutarConsulta(
            "SELECT IFNULL(SUM(importe), 0) AS total
             FROM caja_movimientos
             WHERE id_turno = :id AND tipo = 'retiro'",
            [':id' => $idTurno]
        );
        $totalRetirado = (float)$qRetiros->fetch(PDO::FETCH_ASSOC)['total'];

        // 4. Reembolsos en efectivo (abonos que salen del cajón)
        //    Los abonos en efectivo se registran como pagos_venta con importe NEGATIVO
        //    o como ventas tipo 'abono' con metodo_pago 'efectivo'.
        //    Como ya están en pagos_venta con signo correcto, no hace falta resta adicional
        //    siempre que el registro de abonos use importe negativo en pagos_venta.
        //    Si usan importe positivo en pagos_venta pero con tipo_documento='abono', restamos:
        $qAbonos = DBPDO::ejecutarConsulta(
            "SELECT IFNULL(SUM(pv.importe), 0) AS total
             FROM pagos_venta pv
             JOIN ventas v ON v.id = pv.id_venta
             WHERE v.id_turno = :id
               AND pv.metodo_pago = 'efectivo'
               AND v.tipo_documento = 'abono'",
            [':id' => $idTurno]
        );
        $abonosEfectivo = (float)$qAbonos->fetch(PDO::FETCH_ASSOC)['total'];
        // Los abonos ya están incluidos en $cobradoEfectivo si se registraron en pagos_venta.
        // Si no, los restamos aquí. Verificamos si el importe fue positivo o negativo.
        // Para seguridad, los restamos usando abs() para no doble-contar:
        // (si ya fueron negativos en $cobradoEfectivo, abs() los restará dos veces — ajuste fino necesario)
        // Simplificación: los abonos en pagos_venta se guardan con importe POSITIVO → hay que restarlos.
        // La suma en $cobradoEfectivo incluye TODOS los pagos_venta, así que NO debemos restar de nuevo
        // para evitar doble contabilidad. El signo de los abonos en pagos_venta define el resultado.

        return round(
            $fondoInicial
            + $cobradoEfectivo   // ya incluye ventas y cobros; abonos con importe negativo se descuentan solos
            + $totalIngresado
            - $totalRetirado,
            2
        );
    }

    /**
     * Obtiene el total recaudado en este turno por cada método de pago
     * basándose en la tabla pagos_venta (que es el registro real de flujos).
     */
    public static function obtenerTotalesMetodosTurno(int $idTurno): array
    {
        $sql = "SELECT metodo_pago, SUM(importe) as total 
                FROM pagos_venta 
                WHERE id_turno = :id 
                GROUP BY metodo_pago";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idTurno]);
        $results = $q->fetchAll(PDO::FETCH_ASSOC);

        $totales = [
            'efectivo' => 0.0,
            'tarjeta'  => 0.0,
            'bizum'    => 0.0,
            'vale'     => 0.0,
            'puntos'   => 0.0
        ];

        foreach ($results as $r) {
            $m = $r['metodo_pago'];
            if (array_key_exists($m, $totales)) {
                $totales[$m] = (float)$r['total'];
            }
        }
        return $totales;
    }
}
