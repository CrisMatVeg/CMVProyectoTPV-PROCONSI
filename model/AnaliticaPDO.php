<?php
require_once __DIR__ . '/DBPDO.php';

class AnaliticaPDO
{
    /**
     * Recalcula el resumen de un día concreto y lo guarda en analitica_resumen_diario.
     * Se llama al cerrar caja o como tarea programada.
     */
    public static function recalcularDia(string $fecha): void
    {
        $db = DBPDO::getPDO();

        // 1. KPIs del día
        $kpis = DBPDO::ejecutarConsulta("
            SELECT 
                COUNT(v.id) as total_tickets,
                COALESCE(SUM(v.total), 0) as total_ventas,
                COALESCE(SUM(v.base_imponible), 0) as total_base,
                COALESCE(SUM(v.total * 0),0) as placeholder,
                COALESCE(SUM(CASE WHEN v.metodo_pago='efectivo' THEN v.total ELSE 0 END),0) as ef,
                COALESCE(SUM(CASE WHEN v.metodo_pago='tarjeta'  THEN v.total ELSE 0 END),0) as tar,
                COALESCE(SUM(CASE WHEN v.metodo_pago='bizum'    THEN v.total ELSE 0 END),0) as biz,
                COALESCE(SUM(CASE WHEN v.metodo_pago='a_cuenta' THEN v.total ELSE 0 END),0) as ac
            FROM ventas v
            WHERE v.estado = 'completada'
              AND v.fecha >= :f_ini AND v.fecha < :f_fin
              AND v.metodo_pago IN ('efectivo','tarjeta','bizum','a_cuenta','mixto')
        ", [':f_ini' => $fecha . ' 00:00:00', ':f_fin' => $fecha . ' 23:59:59'])->fetch(PDO::FETCH_ASSOC);

        // 2. Margen del día
        $margen = DBPDO::ejecutarConsulta("
            SELECT COALESCE(SUM((lv.precio_unitario - lv.precio_coste_unitario) * lv.cantidad), 0) as margen
            FROM lineas_venta lv
            JOIN ventas v ON lv.id_venta = v.id
            WHERE v.estado = 'completada'
              AND v.fecha >= :f_ini AND v.fecha < :f_fin
              AND lv.devuelta = 0
        ", [':f_ini' => $fecha . ' 00:00:00', ':f_fin' => $fecha . ' 23:59:59'])->fetchColumn();

        // 3. Guardar resumen (INSERT o UPDATE si ya existe)
        DBPDO::ejecutarConsulta("
            INSERT INTO analitica_resumen_diario 
                (fecha, total_tickets, total_ventas, total_base, margen_estimado,
                 total_efectivo, total_tarjeta, total_bizum, total_a_cuenta)
            VALUES 
                (:f, :tickets, :ventas, :base, :margen, :ef, :tar, :biz, :ac)
            ON DUPLICATE KEY UPDATE
                total_tickets   = VALUES(total_tickets),
                total_ventas    = VALUES(total_ventas),
                total_base      = VALUES(total_base),
                margen_estimado = VALUES(margen_estimado),
                total_efectivo  = VALUES(total_efectivo),
                total_tarjeta   = VALUES(total_tarjeta),
                total_bizum     = VALUES(total_bizum),
                total_a_cuenta  = VALUES(total_a_cuenta),
                calculado_en    = NOW()
        ", [
            ':f'       => $fecha,
            ':tickets' => $kpis['total_tickets'] ?? 0,
            ':ventas'  => $kpis['total_ventas'] ?? 0,
            ':base'    => $kpis['total_base'] ?? 0,
            ':margen'  => $margen ?? 0,
            ':ef'      => $kpis['ef'] ?? 0,
            ':tar'     => $kpis['tar'] ?? 0,
            ':biz'     => $kpis['biz'] ?? 0,
            ':ac'      => $kpis['ac'] ?? 0,
        ]);

        // 4. Guardar productos del día
        DBPDO::ejecutarConsulta(
            "DELETE FROM analitica_producto_diario WHERE fecha = :f",
            [':f' => $fecha]
        );

        DBPDO::ejecutarConsulta("
            INSERT INTO analitica_producto_diario 
                (fecha, id_producto, nombre, categoria, referencia, unidades, total, coste)
            SELECT 
                :f,
                lv.id_producto,
                MAX(p.nombre),
                MAX(p.categoria),
                MAX(p.referencia),
                SUM(lv.cantidad),
                SUM(lv.total_linea),
                SUM(lv.precio_coste_unitario * lv.cantidad)
            FROM lineas_venta lv
            JOIN ventas v ON lv.id_venta = v.id
            LEFT JOIN productos p ON lv.id_producto = p.id
            WHERE v.estado = 'completada'
              AND v.fecha >= :f_ini AND v.fecha < :f_fin
              AND lv.devuelta = 0
              AND lv.id_producto IS NOT NULL
            GROUP BY lv.id_producto
        ", [
            ':f'     => $fecha,
            ':f_ini' => $fecha . ' 00:00:00',
            ':f_fin' => $fecha . ' 23:59:59',
        ]);

        // 5. Guardar desglose IVA del dia
        DBPDO::ejecutarConsulta(
            "DELETE FROM analitica_iva_diario WHERE fecha = :f",
            [':f' => $fecha]
        );
        DBPDO::ejecutarConsulta("
            INSERT INTO analitica_iva_diario (fecha, porcentaje, cuota, total)
            SELECT :f,
                   lv.iva_aplicado,
                   SUM(lv.total_linea * (lv.iva_aplicado / (100 + lv.iva_aplicado))),
                   SUM(lv.total_linea)
            FROM lineas_venta lv
            JOIN ventas v ON lv.id_venta = v.id
            WHERE v.estado = 'completada'
              AND v.metodo_pago != 'financiado'
              AND lv.devuelta = 0
              AND v.fecha >= :f_ini AND v.fecha < :f_fin
            GROUP BY lv.iva_aplicado
        ", [
            ':f'     => $fecha,
            ':f_ini' => $fecha . ' 00:00:00',
            ':f_fin' => $fecha . ' 23:59:59',
        ]);
    }

    /**
     * Rellena los resúmenes de todos los días que faltan en el histórico.
     * Ejecutar una sola vez desde phpMyAdmin o un script.
     */
    public static function poblarHistorico(): void
    {
        $q = DBPDO::ejecutarConsulta("
            SELECT DISTINCT DATE(fecha) as dia 
            FROM ventas 
            WHERE estado = 'completada'
            ORDER BY dia ASC
        ");
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            self::recalcularDia($row['dia']);
        }
    }

    /**
     * KPIs desde la tabla precalculada (instantáneo).
     */
    public static function obtenerKPIsRapido(string $desde, string $hasta): array
    {
        // Si el rango incluye hoy, combina resúmenes + datos en tiempo real del día actual
        $hoy = date('Y-m-d');
        $hastaResumen = ($hasta >= $hoy) ? date('Y-m-d', strtotime($hoy . ' -1 day')) : $hasta;

        $resultado = [
            'total_operaciones'  => 0, 
            'total_ventas'       => 0, 
            'total_base'         => 0, 
            'beneficio_estimado' => 0,
            'ticket_medio'       => 0
        ];

        // Datos históricos desde tabla precalculada
        if ($desde <= $hastaResumen) {
            $row = DBPDO::ejecutarConsulta("
                SELECT 
                    COALESCE(SUM(total_tickets), 0)   as total_operaciones,
                    COALESCE(SUM(total_ventas), 0)    as total_ventas,
                    COALESCE(SUM(total_base), 0)      as total_base,
                    COALESCE(SUM(margen_estimado), 0) as beneficio_estimado
                FROM analitica_resumen_diario
                WHERE fecha BETWEEN :d AND :h
            ", [':d' => $desde, ':h' => $hastaResumen])->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $resultado['total_operaciones']  += (int)$row['total_operaciones'];
                $resultado['total_ventas']       += (float)$row['total_ventas'];
                $resultado['total_base']         += (float)$row['total_base'];
                $resultado['beneficio_estimado'] += (float)$row['beneficio_estimado'];
            }
        }

        // Datos de hoy en tiempo real (si el rango incluye hoy)
        if ($hasta >= $hoy) {
            $hoyData = DBPDO::ejecutarConsulta("
                SELECT 
                    COUNT(v.id) as total_operaciones,
                    COALESCE(SUM(v.total), 0) as total_ventas,
                    COALESCE(SUM(v.base_imponible), 0) as total_base
                FROM ventas v
                WHERE v.estado = 'completada'
                  AND v.fecha >= :f_ini AND v.fecha < :f_fin
                  AND v.metodo_pago IN ('efectivo','tarjeta','bizum','a_cuenta','mixto')
            ", [
                ':f_ini' => $hoy . ' 00:00:00',
                ':f_fin' => $hoy . ' 23:59:59',
            ])->fetch(PDO::FETCH_ASSOC);

            $resultado['total_operaciones']  += (int)($hoyData['total_operaciones'] ?? 0);
            $resultado['total_ventas']       += (float)($hoyData['total_ventas'] ?? 0);
            $resultado['total_base']         += (float)($hoyData['total_base'] ?? 0);

            // Margen de hoy en tiempo real
            $margenHoy = DBPDO::ejecutarConsulta("
                SELECT COALESCE(SUM((lv.precio_unitario - lv.precio_coste_unitario) * lv.cantidad), 0)
                FROM lineas_venta lv JOIN ventas v ON lv.id_venta = v.id
                WHERE v.estado = 'completada'
                  AND v.fecha >= :ini AND v.fecha < :fin AND lv.devuelta = 0
            ", [':ini' => $hoy . ' 00:00:00', ':fin' => $hoy . ' 23:59:59'])->fetchColumn();
            $resultado['beneficio_estimado'] += (float)($margenHoy ?? 0);
        }

        // Calcular Ticket Medio final
        if ($resultado['total_operaciones'] > 0) {
            $resultado['ticket_medio'] = $resultado['total_ventas'] / $resultado['total_operaciones'];
        }

        return $resultado;
    }

    /**
     * Evolución desde tabla precalculada con agrupación opcional.
     * Si el rango incluye hoy, añade los datos del día actual desde ventas en tiempo real.
     */
    public static function obtenerEvolucionRapida(string $desde, string $hasta, string $agrupacion = 'dia'): array
    {
        $hoy = date('Y-m-d');
        $hastaResumen = ($hasta >= $hoy) ? date('Y-m-d', strtotime('-1 day')) : $hasta;

        $selectFecha = "fecha";
        $groupBy = "fecha";

        if ($agrupacion === 'mes') {
            $selectFecha = "DATE_FORMAT(fecha, '%Y-%m-01') as fecha";
            $groupBy = "DATE_FORMAT(fecha, '%Y-%m')";
        } elseif ($agrupacion === 'año') {
            $selectFecha = "DATE_FORMAT(fecha, '%Y-01-01') as fecha";
            $groupBy = "YEAR(fecha)";
        }

        $rows = [];
        if ($desde <= $hastaResumen) {
            $rows = DBPDO::ejecutarConsulta("
                SELECT
                    $selectFecha,
                    SUM(total_ventas) as ingresos,
                    SUM(total_ventas - margen_estimado) as costes,
                    SUM(margen_estimado) as beneficio
                FROM analitica_resumen_diario
                WHERE fecha BETWEEN :d AND :h
                GROUP BY $groupBy
                ORDER BY fecha ASC
            ", [':d' => $desde, ':h' => $hastaResumen])->fetchAll(PDO::FETCH_ASSOC);
        }

        // Añadir datos de hoy en tiempo real si el rango lo incluye
        if ($hasta >= $hoy) {
            $hoyRow = DBPDO::ejecutarConsulta("
                SELECT
                    :fecha as fecha,
                    COALESCE(SUM(lv.total_linea), 0) as ingresos,
                    COALESCE(SUM(lv.precio_coste_unitario * lv.cantidad), 0) as costes,
                    COALESCE(SUM(lv.total_linea - lv.precio_coste_unitario * lv.cantidad), 0) as beneficio
                FROM lineas_venta lv
                JOIN ventas v ON lv.id_venta = v.id
                WHERE v.estado = 'completada' AND lv.devuelta = 0
                  AND v.fecha >= :ini AND v.fecha <= :fin
            ", [':fecha' => $hoy, ':ini' => $hoy . ' 00:00:00', ':fin' => $hoy . ' 23:59:59'])->fetch(PDO::FETCH_ASSOC);

            if ($hoyRow) {
                if ($agrupacion === 'dia') {
                    $rows[] = $hoyRow;
                } else {
                    // Para mes/año: fusionar con la entrada existente del mismo período
                    $clavePeriodo = $agrupacion === 'mes' ? date('Y-m-01') : date('Y-01-01');
                    $encontrado = false;
                    foreach ($rows as &$row) {
                        if ($row['fecha'] === $clavePeriodo) {
                            $row['ingresos']  = (float)$row['ingresos']  + (float)$hoyRow['ingresos'];
                            $row['costes']    = (float)$row['costes']    + (float)$hoyRow['costes'];
                            $row['beneficio'] = (float)$row['beneficio'] + (float)$hoyRow['beneficio'];
                            $encontrado = true;
                            break;
                        }
                    }
                    unset($row);
                    if (!$encontrado) {
                        $hoyRow['fecha'] = $clavePeriodo;
                        $rows[] = $hoyRow;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Top productos desde tabla precalculada.
     */
    public static function obtenerTopProductosRapido(string $desde, string $hasta, int $limite = 10): array
    {
        return DBPDO::ejecutarConsulta("
            SELECT 
                id_producto,
                MAX(nombre) as nombre_producto_limpio,
                MAX(referencia) as codigo_producto,
                MAX(categoria) as categoria,
                SUM(unidades) as unidades,
                SUM(total) as total_recaudado
            FROM analitica_producto_diario
            WHERE fecha BETWEEN :d AND :h
            GROUP BY id_producto
            ORDER BY unidades DESC
            LIMIT $limite
        ", [':d' => $desde, ':h' => $hasta])->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Desglose de IVA desde tabla precalculada.
     */
    public static function obtenerDesgloseIVARapido(string $desde, string $hasta): array
    {
        return DBPDO::ejecutarConsulta("
            SELECT 
                porcentaje,
                SUM(cuota) as cuota,
                SUM(total) as total
            FROM analitica_iva_diario
            WHERE fecha BETWEEN :d AND :h
            GROUP BY porcentaje
            ORDER BY porcentaje DESC
        ", [':d' => $desde, ':h' => $hasta])
        ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ranking de productos desde tabla precalculada con paginación.
     */
    public static function obtenerRankingRapido(string $desde, string $hasta, int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT
                p.id                                           AS id_producto,
                TRIM(SUBSTRING_INDEX(p.nombre, '(', 1))       AS nombre_producto_limpio,
                p.referencia                                   AS codigo_producto,
                p.categoria,
                COALESCE(sales.unidades, 0)                AS unidades,
                COALESCE(sales.total, 0)                   AS total_recaudado
            FROM productos p
            LEFT JOIN (
                SELECT id_producto, SUM(unidades) as unidades, SUM(total) as total
                FROM analitica_producto_diario
                WHERE fecha BETWEEN :d AND :h
                GROUP BY id_producto
            ) AS sales ON p.id = sales.id_producto
            ORDER BY unidades DESC, total_recaudado DESC, p.id ASC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        return DBPDO::ejecutarConsulta($sql, [':d' => $desde, ':h' => $hasta])
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Categorías desde tabla precalculada.
     * Si el rango incluye hoy, fusiona con datos en tiempo real del día actual.
     */
    public static function obtenerCategoriasRapido(string $desde, string $hasta): array
    {
        $hoy = date('Y-m-d');
        $hastaResumen = ($hasta >= $hoy) ? date('Y-m-d', strtotime('-1 day')) : $hasta;

        $rows = [];
        if ($desde <= $hastaResumen) {
            $rows = DBPDO::ejecutarConsulta("
                SELECT
                    MAX(categoria) as categoria,
                    SUM(total)     as total,
                    SUM(unidades)  as cantidad
                FROM analitica_producto_diario
                WHERE fecha BETWEEN :d AND :h
                  AND categoria IS NOT NULL
                GROUP BY categoria
                ORDER BY total DESC
            ", [':d' => $desde, ':h' => $hastaResumen])->fetchAll(PDO::FETCH_ASSOC);
        }

        // Fusionar con datos de hoy en tiempo real
        if ($hasta >= $hoy) {
            $hoyRows = DBPDO::ejecutarConsulta("
                SELECT
                    COALESCE(p.categoria, 'Sin categoría') as categoria,
                    SUM(lv.total_linea) as total,
                    SUM(lv.cantidad) as cantidad
                FROM lineas_venta lv
                JOIN ventas v ON lv.id_venta = v.id
                LEFT JOIN productos p ON lv.id_producto = p.id
                WHERE v.estado = 'completada' AND lv.devuelta = 0
                  AND v.fecha >= :ini AND v.fecha <= :fin
                GROUP BY p.categoria
            ", [':ini' => $hoy . ' 00:00:00', ':fin' => $hoy . ' 23:59:59'])->fetchAll(PDO::FETCH_ASSOC);

            $catMap = [];
            foreach ($rows as $i => $row) {
                $catMap[$row['categoria']] = $i;
            }
            foreach ($hoyRows as $hoyRow) {
                $cat = $hoyRow['categoria'];
                if (isset($catMap[$cat])) {
                    $rows[$catMap[$cat]]['total']    = (float)$rows[$catMap[$cat]]['total']    + (float)$hoyRow['total'];
                    $rows[$catMap[$cat]]['cantidad']  = (float)$rows[$catMap[$cat]]['cantidad'] + (float)$hoyRow['cantidad'];
                } else {
                    $rows[] = $hoyRow;
                }
            }
            usort($rows, fn($a, $b) => (float)$b['total'] <=> (float)$a['total']);
        }

        return $rows;
    }
}