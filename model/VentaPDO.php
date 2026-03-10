<?php

/**
 * Clase: VentaPDO
 * Gestiona la persistencia de las ventas mediante DBPDO.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
require_once __DIR__ . '/DBPDO.php';
require_once __DIR__ . '/Venta.php';
require_once __DIR__ . '/MovimientoStockPDO.php';

class VentaPDO
{

    public static function obtenerSiguienteTicket(): int
    {
        $sql = "SELECT COALESCE(MAX(numero_ticket), 1000) + 1 AS siguiente FROM ventas";
        $q = DBPDO::ejecutarConsulta($sql);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return (int)$row['siguiente'];
    }

    /**
     * Guarda una venta completa con sus líneas en la base de datos.
     * @param array $datos Datos de la venta
     * @param int $idUsuario ID del usuario que realiza la venta
     * @return int Número de ticket asignado
     */
    public static function guardarVenta(array $datos, int $idUsuario): int
    {
        $numTicket = self::obtenerSiguienteTicket();

        $subtotal    = round((float)$datos['subtotal'], 2);
        $descPct     = round((float)($datos['descuentoPct'] ?? 0), 2);

        if (isset($datos['descuentoAmt']) && (float)$datos['descuentoAmt'] > 0) {
            $descAmt = round((float)$datos['descuentoAmt'], 2);
        } else {
            $descAmt = round($subtotal * $descPct / 100, 2);
        }

        $base        = round($subtotal - $descAmt, 2);

        if (!isset($datos['total']) || (float)$datos['total'] <= 0) {
            $total = round($base * 1.21, 2);
        } else {
            $total = round((float)$datos['total'], 2);
        }

        $ivaAmt = round($total - $base, 2);

        $tipoCliente    = in_array($datos['tipoCliente'] ?? '', ['particular', 'empresa']) ? $datos['tipoCliente'] : 'particular';
        $nombreCliente  = isset($datos['nombreCliente']) ? mb_substr(trim($datos['nombreCliente']), 0, 100) : null;
        $nifCliente     = isset($datos['nifCliente']) ? mb_substr(trim($datos['nifCliente']), 0, 20) : null;
        $metodoPago     = in_array($datos['metodoPago'] ?? '', ['efectivo', 'tarjeta', 'bizum', 'financiado', 'a_cuenta']) ? $datos['metodoPago'] : 'efectivo';
        $idCliente      = isset($datos['idCliente']) ? (int)$datos['idCliente'] : null;
        $efectivoRecibido = ($metodoPago === 'efectivo') ? round((float)($datos['efectivo']['recibido'] ?? $datos['efectivoRecibido'] ?? 0), 2) : 0;

        $estado = ($metodoPago === 'a_cuenta') ? 'pendiente_pago' : 'completada';
        $pagadoACuenta = ($metodoPago === 'a_cuenta') ? round((float)($datos['pagadoACuenta'] ?? 0), 2) : 0;
        $fechaLimite = ($metodoPago === 'a_cuenta' && !empty($datos['fechaLimitePago'])) ? $datos['fechaLimitePago'] : null;

        $descuentoLabel = isset($datos['descuentoLabel']) ? mb_substr(trim($datos['descuentoLabel']), 0, 100) : null;

        $sqlVenta = "INSERT INTO ventas 
             (numero_ticket, fecha, id_usuario, id_cliente, tipo_cliente, nombre_cliente, nif_cliente, metodo_pago,
              subtotal, descuento_pct, descuento_amt, descuento_label, base_imponible, iva_amt, total, efectivo_recibido,
              estado, pagado_a_cuenta, fecha_limite_pago, es_factura, id_turno)
            VALUES (:ticket, NOW(), :usuario, :cliente, :tipo, :nombre, :nif, :metodo,
              :subtotal, :descPct, :descAmt, :descLabel, :base, :ivaAmt, :total, :efectivo,
              :estado, :pagadoACuenta, :fechaLimite, :esFactura, :idTurno)";

        $paramsVenta = [
            ':ticket'   => $numTicket,
            ':usuario'  => $idUsuario,
            ':cliente'  => $idCliente,
            ':tipo'     => $tipoCliente,
            ':nombre'   => $nombreCliente,
            ':nif'      => $nifCliente,
            ':metodo'   => $metodoPago,
            ':subtotal' => $subtotal,
            ':descPct'  => $descPct,
            ':descAmt'  => $descAmt,
            ':descLabel' => $descuentoLabel,
            ':base'     => $base,
            ':ivaAmt'   => $ivaAmt,
            ':total'    => $total,
            ':efectivo' => $efectivoRecibido,
            ':estado'   => $estado,
            ':pagadoACuenta' => $pagadoACuenta,
            ':fechaLimite'  => $fechaLimite,
            ':esFactura'    => (!empty($datos['esFactura']) ? 1 : 0),
            ':idTurno'      => isset($datos['idTurno']) ? (int)$datos['idTurno'] : null
        ];

        $db = DBPDO::getPDO();
        $db->beginTransaction();

        try {
            DBPDO::ejecutarConsulta($sqlVenta, $paramsVenta);

            $qId = DBPDO::ejecutarConsulta("SELECT id FROM ventas WHERE numero_ticket = :t", [':t' => $numTicket]);
            $rowId = $qId->fetch(PDO::FETCH_ASSOC);
            $idVenta = (int)$rowId['id'];

            $fechaVenta = date('Y-m-d');

            foreach ($datos['lineas'] as $linea) {
                $precioUnit = round((float)$linea['price'], 2);
                $qty        = (int)$linea['qty'];
                $totalLinea = round($precioUnit * $qty, 2);
                $ivaAplicado = (float)($linea['iva'] ?? 21.00);

                $mesesGarantia = 24;
                $codigoIva     = 'GENERAL';
                $precioCosteUnit = 0.00;
                $idProducto = isset($linea['id']) ? (int)$linea['id'] : null;

                if ($idProducto) {
                    require_once 'ProductoPDO.php';
                    $prodData = ProductoPDO::obtenerProductoPorId($idProducto);
                    if ($prodData) {
                        $mesesGarantia   = (int)($prodData['meses_garantia'] ?? 24);
                        $precioCosteUnit = (float)($prodData['precio_coste'] ?? 0);
                        if (!empty($prodData['codigo_iva'])) {
                            $codigoIva = $prodData['codigo_iva'];
                        }
                    }
                }

                try {
                    require_once 'TipoIVAPDO.php';
                    $tipoIva = TipoIVAPDO::obtenerVigentePorCodigo($codigoIva, $fechaVenta);
                    if ($tipoIva && isset($tipoIva['porcentaje'])) {
                        $ivaAplicado = (float)$tipoIva['porcentaje'];
                    }
                } catch (\Throwable $e) {
                }

                $variantesData = null;
                $idVariante = !empty($linea['id_variante']) ? (int)$linea['id_variante'] : null;
                if ($idVariante) {
                    $variantesData = ['id_variante' => $idVariante, 'nombre' => $linea['variant'] ?? ''];
                } elseif (isset($linea['variants'])) {
                    $variantesData = $linea['variants'];
                }

                $sqlLinea = "INSERT INTO lineas_venta 
                    (id_venta, id_producto, nombre_producto, codigo_producto, precio_unitario, precio_coste_unitario, iva_aplicado, cantidad, meses_garantia, total_linea, numero_serie, variantes)
                    VALUES (:venta, :prod, :nombre, :codigo, :precio, :coste, :iva, :qty, :garantia, :total, :serial, :variantes)";

                DBPDO::ejecutarConsulta($sqlLinea, [
                    ':venta'   => $idVenta,
                    ':prod'    => $idProducto,
                    ':nombre'  => mb_substr($linea['name'] ?? 'Producto', 0, 100),
                    ':codigo'  => mb_substr($linea['codigo'] ?? '', 0, 50),
                    ':precio'  => $precioUnit,
                    ':coste'   => round($precioCosteUnit, 2),
                    ':iva'     => $ivaAplicado,
                    ':qty'     => $qty,
                    ':garantia' => $mesesGarantia,
                    ':total'   => $totalLinea,
                    ':serial'  => isset($linea['serials']) ? json_encode($linea['serials']) : null,
                    ':variantes' => $variantesData ? json_encode($variantesData, JSON_UNESCAPED_UNICODE) : null
                ]);

                if ($idProducto) {
                    require_once 'ProductoPDO.php';
                    require_once 'MovimientoStockPDO.php';

                    if (!empty($prodData['es_pack'])) {
                        $componentes = ProductoPDO::obtenerComponentesPack($idProducto);
                        foreach ($componentes as $comp) {
                            $qtyComponente = $qty * (int)$comp['cantidad'];
                            ProductoPDO::reducirStock((int)$comp['id_producto'], $qtyComponente);
                            MovimientoStockPDO::registrarMovimiento((int)$comp['id_producto'], 'venta', -$qtyComponente, $idUsuario, "Venta Pack #$numTicket", $db);
                        }
                    } elseif ($idVariante) {
                        require_once 'VariantePDO.php';
                        VariantePDO::reducirStock($idVariante, $qty);
                        MovimientoStockPDO::registrarMovimiento($idProducto, 'venta', -$qty, $idUsuario, "Venta Ticket #$numTicket (Variante ID: $idVariante)", $db);
                    } else {
                        ProductoPDO::reducirStock($idProducto, $qty);
                        MovimientoStockPDO::registrarMovimiento($idProducto, 'venta', -$qty, $idUsuario, "Venta Ticket #$numTicket", $db);
                    }
                }
            }

            if ($metodoPago === 'a_cuenta' && $pagadoACuenta > 0) {
                require_once 'PagoPDO.php';
                PagoPDO::registrarPago($idVenta, $pagadoACuenta, 'efectivo', $idUsuario, 'Entrega inicial al registrar venta');
            }

            if ($metodoPago === 'financiado' && !empty($datos['financiacion'])) {
                $fin = $datos['financiacion'];
                $idFinanciera = $fin['idFinanciera'] ?? $fin['id_financiera'] ?? null;
                $meses = $fin['meses'] ?? null;
                $cuota = $fin['cuotaMensual'] ?? $fin['cuota'] ?? 0;
                $intereses = $fin['importeIntereses'] ?? $fin['intereses'] ?? 0;
                $modalidad = $fin['modalidad'] ?? 'vendedor_paga_intereses';

                if ($idFinanciera && $meses) {
                    $sqlFin = "INSERT INTO ventas_financiacion 
                        (id_venta, id_financiera, meses, cuota_mensual, importe_intereses, modalidad, estado)
                        VALUES (:venta, :financiera, :meses, :cuota, :intereses, :modalidad, 'aprobada')";
                    DBPDO::ejecutarConsulta($sqlFin, [
                        ':venta'      => $idVenta,
                        ':financiera' => (int)$idFinanciera,
                        ':meses'      => (int)$meses,
                        ':cuota'      => round((float)$cuota, 2),
                        ':intereses'  => round((float)$intereses, 2),
                        ':modalidad'  => $modalidad
                    ]);
                }
            }

            $db->commit();
            return $numTicket;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function obtenerVentasHoy(): array
    {
        $sql = "SELECT v.*, u.nombre AS nombre_cajero 
                FROM ventas v 
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                WHERE DATE(v.fecha) = CURDATE() ORDER BY v.fecha ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerVentasPorTurno(int $idTurno): array
    {
        $sql = "SELECT v.*, u.nombre AS nombre_cajero 
                FROM ventas v 
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                WHERE v.id_turno = :t ORDER BY v.fecha ASC";
        $q = DBPDO::ejecutarConsulta($sql, [':t' => $idTurno]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerVentaPorTicket(int $numTicket): ?array
    {
        $sqlVenta = "SELECT v.*, u.nombre AS nombre_cajero
                     FROM ventas v
                     LEFT JOIN usuarios u ON v.id_usuario = u.id
                     WHERE v.numero_ticket = :t";
        $q = DBPDO::ejecutarConsulta($sqlVenta, [':t' => $numTicket]);
        $venta = $q->fetch(PDO::FETCH_ASSOC);
        if (!$venta) return null;

        $sqlLineas = "SELECT lv.*, lv.variantes as variantes_json
                      FROM lineas_venta lv
                      WHERE lv.id_venta = :v 
                      ORDER BY lv.id ASC";
        $qL = DBPDO::ejecutarConsulta($sqlLineas, [':v' => $venta['id']]);
        $venta['lineas'] = $qL->fetchAll(PDO::FETCH_ASSOC);

        // --- NUEVO: FETCH FINANCING DETAILS ---
        if ($venta['metodo_pago'] === 'financiado') {
            $sqlFin = "SELECT vf.*, f.nombre as nombre_financiera 
                       FROM ventas_financiacion vf
                       JOIN financieras f ON vf.id_financiera = f.id
                       WHERE vf.id_venta = :v";
            $qF = DBPDO::ejecutarConsulta($sqlFin, [':v' => $venta['id']]);
            $venta['financiacion'] = $qF->fetch(PDO::FETCH_ASSOC);
        }

        // --- FETCH PAYMENTS (pagos_venta) ---
        require_once 'PagoPDO.php';
        $venta['pagos'] = PagoPDO::obtenerPagosPorVenta((int)$venta['id']);

        return $venta;
    }

    public static function buscarVentas(string $desde, string $hasta, ?int $idUsuario = null, ?int $numTicket = null): array
    {
        $sql = "SELECT v.*, u.nombre as nombre_cajero 
                FROM ventas v
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                WHERE 1=1";

        $params = [];

        if ($numTicket) {
            $sql .= " AND v.numero_ticket = :ticket";
            $params[':ticket'] = $numTicket;
        } else {
            $sql .= " AND DATE(v.fecha) BETWEEN :desde AND :hasta";
            $params[':desde'] = $desde;
            $params[':hasta'] = $hasta;
        }

        if ($idUsuario) {
            $sql .= " AND v.id_usuario = :usuario";
            $params[':usuario'] = $idUsuario;
        }

        $sql .= " ORDER BY v.fecha DESC";
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function obtenerVentasPorCliente(int $idCliente): array
    {
        $sql = "SELECT v.*, u.nombre as nombre_cajero 
                FROM ventas v
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                WHERE v.id_cliente = :cliente
                ORDER BY v.fecha DESC";
        $q = DBPDO::ejecutarConsulta($sql, [':cliente' => $idCliente]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marca una línea de venta como devuelta, repone stock y gestiona el reembolso.
     */
    public static function devolverLinea(int $idLinea, ?string $motivo = null, string $metodoReembolso = 'efectivo'): bool
    {
        // 1. Obtener datos de la línea
        $sql = "SELECT l.id_producto, l.cantidad, l.devuelta, l.id_venta, l.variantes, v.id_cliente, l.total_linea 
                FROM lineas_venta l
                JOIN ventas v ON l.id_venta = v.id
                WHERE l.id = :id";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idLinea]);
        $l = $q->fetch(PDO::FETCH_ASSOC);

        if (!$l || $l['devuelta']) return false;

        // 2. Marcar como devuelta con motivo, fecha y MÉTODO
        $sqlUpd = "UPDATE lineas_venta 
                   SET devuelta = 1, 
                       motivo_devolucion = :motivo, 
                       fecha_devolucion = NOW(),
                       metodo_reembolso = :metodo
                   WHERE id = :id";
        DBPDO::ejecutarConsulta($sqlUpd, [
            ':id' => $idLinea,
            ':motivo' => $motivo ? mb_substr($motivo, 0, 255) : 'Devolución estándar',
            ':metodo' => $metodoReembolso
        ]);

        // 3. Gestionar REEMBOLSO (Informativo - Vale de tienda)
        if ($metodoReembolso === 'vale') {
            // No requiere acción monetaria en base de datos.
        }

        // 4. Reponer stock del producto devuelto (siempre se repone el roto/devuelto)
        $idVariante = null;
        if (!empty($l['variantes'])) {
            $vData = json_decode($l['variantes'], true);
            if (isset($vData['id_variante'])) {
                $idVariante = (int)$vData['id_variante'];
            }
        }

        if ($idVariante) {
            require_once 'VariantePDO.php';
            VariantePDO::aumentarStock($idVariante, (int)$l['cantidad']);
        } elseif ($l['id_producto']) {
            require_once 'ProductoPDO.php';
            $prod = ProductoPDO::obtenerProductoPorId((int)$l['id_producto']);
            if (!empty($prod['es_pack'])) {
                $componentes = ProductoPDO::obtenerComponentesPack((int)$l['id_producto']);
                foreach ($componentes as $comp) {
                    $qtyComponente = (int)$l['cantidad'] * (int)$comp['cantidad'];
                    ProductoPDO::aumentarStock((int)$comp['id_producto'], $qtyComponente);
                    MovimientoStockPDO::registrarMovimiento((int)$comp['id_producto'], 'devolucion', $qtyComponente, null, "Devolución de linea de Pack #{$idLinea}");
                }
            } else {
                ProductoPDO::aumentarStock((int)$l['id_producto'], (int)$l['cantidad']);
                MovimientoStockPDO::registrarMovimiento((int)$l['id_producto'], 'devolucion', (int)$l['cantidad'], null, "Devolución de linea #{$idLinea}");
            }
        }

        // 5. Gestionar REEMPLAZO (si es cambio por garantía, quitamos una unidad nueva de stock)
        if ($metodoReembolso === 'reemplazo') {
            if ($idVariante) {
                VariantePDO::reducirStock($idVariante, (int)$l['cantidad']);
            } elseif ($l['id_producto']) {
                $prod = ProductoPDO::obtenerProductoPorId((int)$l['id_producto']);
                if (!empty($prod['es_pack'])) {
                    $componentes = ProductoPDO::obtenerComponentesPack((int)$l['id_producto']);
                    foreach ($componentes as $comp) {
                        $qtyComponente = (int)$l['cantidad'] * (int)$comp['cantidad'];
                        ProductoPDO::reducirStock((int)$comp['id_producto'], $qtyComponente);
                        MovimientoStockPDO::registrarMovimiento((int)$comp['id_producto'], 'venta', -$qtyComponente, null, "Reemplazo de linea de Pack #{$idLinea}");
                    }
                } else {
                    ProductoPDO::reducirStock((int)$l['id_producto'], (int)$l['cantidad']);
                    MovimientoStockPDO::registrarMovimiento((int)$l['id_producto'], 'venta', -(int)$l['cantidad'], null, "Reemplazo de linea #{$idLinea}");
                }
            }
        }

        // 4. Si todas las líneas de la venta están devueltas, marcar la venta como devuelta
        if (!empty($l['id_venta'])) {
            $sqlStats = "SELECT 
                            COUNT(*) AS total,
                            SUM(CASE WHEN devuelta = 1 THEN 1 ELSE 0 END) AS devueltas
                         FROM lineas_venta
                         WHERE id_venta = :idv";
            $qStats = DBPDO::ejecutarConsulta($sqlStats, [':idv' => $l['id_venta']]);
            $stats = $qStats->fetch(PDO::FETCH_ASSOC);

            if (
                $stats
                && (int)$stats['total'] > 0
                && (int)$stats['total'] === (int)$stats['devueltas']
            ) {
                DBPDO::ejecutarConsulta(
                    "UPDATE ventas SET estado = 'devuelta' WHERE id = :idv",
                    [':idv' => $l['id_venta']]
                );
            }
        }
        return true;
    }

    /**
     * Devuelve una venta completa.
     */
    public static function devolverVenta(int $numTicket, ?string $motivo = null): bool
    {
        $v = self::obtenerVentaPorTicket($numTicket);
        if (!$v || $v['estado'] !== 'completada') return false;

        foreach ($v['lineas'] as $l) {
            if (!$l['devuelta']) {
                self::devolverLinea((int)$l['id'], $motivo);
            }
        }

        DBPDO::ejecutarConsulta("UPDATE ventas SET estado = 'devuelta' WHERE id = :id", [':id' => $v['id']]);
        return true;
    }

    /**
     * Obtiene métricas clave para el dashboard.
     */
    public static function obtenerKPIs(string $desde, string $hasta): array
    {
        $sql = "SELECT 
                    COUNT(v.id) as total_tickets,
                    COALESCE(SUM(v.total), 0) as total_ventas,
                    COALESCE(SUM(v.base_imponible), 0) as total_base,
                    COALESCE((SELECT SUM((lv.precio_unitario - lv.precio_coste_unitario) * lv.cantidad)
                     FROM lineas_venta lv
                     JOIN ventas v2 ON lv.id_venta = v2.id
                     WHERE v2.estado = 'completada' 
                     AND DATE(v2.fecha) BETWEEN :desde AND :hasta
                     AND lv.devuelta = 0), 0) as margen_estimado
                FROM ventas v
                WHERE v.estado = 'completada' AND DATE(v.fecha) BETWEEN :desde2 AND :hasta2";

        $params = [
            ':desde'  => $desde,
            ':hasta'  => $hasta,
            ':desde2' => $desde,
            ':hasta2' => $hasta
        ];

        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas agrupadas por método de pago.
     */
    public static function obtenerVentasPorMetodo(string $desde, string $hasta): array
    {
        $sql = "SELECT metodo_pago, COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad
                FROM ventas
                WHERE estado = 'completada' AND DATE(fecha) BETWEEN :desde AND :hasta
                GROUP BY metodo_pago";
        $q = DBPDO::ejecutarConsulta($sql, [':desde' => $desde, ':hasta' => $hasta]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene evolución de ventas por día.
     */
    public static function obtenerVentasPorFecha(string $desde, string $hasta): array
    {
        $sql = "SELECT DATE(fecha) as fecha, SUM(total) as total
                FROM ventas
                WHERE estado = 'completada' AND DATE(fecha) BETWEEN :desde AND :hasta
                GROUP BY DATE(fecha)
                ORDER BY DATE(fecha) ASC";
        $q = DBPDO::ejecutarConsulta($sql, [':desde' => $desde, ':hasta' => $hasta]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas agrupadas por cajero/usuario.
     */
    public static function obtenerVentasPorCajero(string $desde, string $hasta): array
    {
        $sql = "SELECT u.nombre, COALESCE(SUM(v.total), 0) as total, COUNT(v.id) as cantidad
                FROM ventas v
                JOIN usuarios u ON v.id_usuario = u.id
                WHERE v.estado = 'completada' AND DATE(v.fecha) BETWEEN :desde AND :hasta
                GROUP BY u.id
                ORDER BY total DESC";
        $q = DBPDO::ejecutarConsulta($sql, [':desde' => $desde, ':hasta' => $hasta]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas agrupadas por categoría.
     */
    public static function obtenerVentasPorCategoria(string $desde, string $hasta): array
    {
        $sql = "SELECT p.categoria, COALESCE(SUM(lv.total_linea), 0) as total, COUNT(lv.id) as cantidad
                FROM lineas_venta lv
                JOIN productos p ON lv.id_producto = p.id
                JOIN ventas v ON lv.id_venta = v.id
                WHERE v.estado = 'completada' AND lv.devuelta = 0 
                AND DATE(v.fecha) BETWEEN :desde AND :hasta
                GROUP BY p.categoria
                ORDER BY total DESC";
        $q = DBPDO::ejecutarConsulta($sql, [':desde' => $desde, ':hasta' => $hasta]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los productos más vendidos.
     */
    public static function obtenerTopProductos(string $desde, string $hasta, int $limite = 10): array
    {
        $sql = "SELECT 
                    lv.id_producto,
                    COALESCE(
                        NULLIF(TRIM(lv.nombre_producto), ''),
                        NULLIF(TRIM(p.nombre), ''),
                        CONCAT('Producto ID ', lv.id_producto)
                    ) AS nombre_producto,
                    COALESCE(
                        NULLIF(TRIM(lv.codigo_producto), ''),
                        NULLIF(TRIM(p.referencia), ''),
                        CONCAT('REF-', lv.id_producto)
                    ) AS codigo_producto,
                    SUM(lv.cantidad) AS unidades,
                    SUM(lv.total_linea) AS total_recaudado
                FROM lineas_venta lv
                JOIN ventas v ON lv.id_venta = v.id
                LEFT JOIN productos p ON lv.id_producto = p.id
                WHERE v.estado = 'completada' 
                  AND lv.devuelta = 0
                  AND DATE(v.fecha) BETWEEN :desde AND :hasta
                GROUP BY lv.id_producto, nombre_producto, codigo_producto
                ORDER BY unidades DESC
                LIMIT :limite";

        // PDO::prepare LIMIT doesn't work well with params in some configs depending on emulation
        // we'll cast to int or use string replacement if needed, but standard DBPDO uses prepare
        // Since we know $limite is an int we'll just use it in the query string safely or cast it.
        $sql = str_replace(':limite', (int)$limite, $sql);

        $q = DBPDO::ejecutarConsulta($sql, [':desde' => $desde, ':hasta' => $hasta]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el desglose de márgenes (Ingreso vs Coste).
     */
    public static function obtenerMargenesDetallados(string $desde, string $hasta): array
    {
        $sql = "SELECT DATE(v.fecha) as fecha,
                       SUM(lv.total_linea) as ingresos,
                       SUM(lv.precio_coste_unitario * lv.cantidad) as costes,
                       SUM(lv.total_linea - (lv.precio_coste_unitario * lv.cantidad)) as beneficio
                FROM lineas_venta lv
                JOIN productos p ON lv.id_producto = p.id
                JOIN ventas v ON lv.id_venta = v.id
                WHERE v.estado = 'completada' AND lv.devuelta = 0
                AND DATE(v.fecha) BETWEEN :desde AND :hasta
                GROUP BY DATE(v.fecha)
                ORDER BY fecha ASC";
        $q = DBPDO::ejecutarConsulta($sql, [':desde' => $desde, ':hasta' => $hasta]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el ranking completo de productos, incluyendo los que no han tenido ventas.
     */
    public static function obtenerRankingCompletoProductos(string $desde, string $hasta): array
    {
        $sql = "SELECT 
                    p.id AS id_producto,
                    p.nombre AS nombre_producto,
                    p.referencia AS codigo_producto,
                    p.categoria,
                    COALESCE(SUM(lv.cantidad), 0) AS unidades,
                    COALESCE(SUM(lv.total_linea), 0) AS total_recaudado
                FROM productos p
                LEFT JOIN lineas_venta lv ON p.id = lv.id_producto
                LEFT JOIN ventas v ON lv.id_venta = v.id 
                    AND v.estado = 'completada' 
                    AND lv.devuelta = 0 
                    AND DATE(v.fecha) BETWEEN :desde AND :hasta
                GROUP BY p.id, p.nombre, p.referencia, p.categoria
                ORDER BY unidades DESC, total_recaudado DESC";

        $q = DBPDO::ejecutarConsulta($sql, [':desde' => $desde, ':hasta' => $hasta]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Obtiene el desglose de IVA recaudado por tipo.
     */
    public static function obtenerDesgloseIVA(string $desde, string $hasta): array
    {
        $sql = "SELECT lv.iva_aplicado as porcentaje, SUM(lv.total_linea * (lv.iva_aplicado / (100 + lv.iva_aplicado))) as cuota, SUM(lv.total_linea) as total
                FROM lineas_venta lv
                JOIN ventas v ON lv.id_venta = v.id
                WHERE v.estado = 'completada' AND lv.devuelta = 0
                AND DATE(v.fecha) BETWEEN :desde AND :hasta
                GROUP BY lv.iva_aplicado
                ORDER BY lv.iva_aplicado DESC";
        $q = DBPDO::ejecutarConsulta($sql, [':desde' => $desde, ':hasta' => $hasta]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un pago sobre una venta pendiente o la liquida totalmente.
     */
    public static function liquidarVentaPendiente(int $idVenta, float $importe, string $metodo, ?int $idUsuario = null): bool
    {
        // 1. Obtener datos actuales
        $sql = "SELECT total, pagado_a_cuenta FROM ventas WHERE id = :id AND estado = 'pendiente_pago'";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idVenta]);
        $v = $q->fetch(PDO::FETCH_ASSOC);

        if (!$v) return false;

        $nuevoPagado = round((float)$v['pagado_a_cuenta'] + $importe, 2);
        $totalVenta = round((float)$v['total'], 2);

        // 2. Insert payment record
        require_once 'PagoPDO.php';
        PagoPDO::registrarPago($idVenta, $importe, $metodo, $idUsuario, 'Abono de deuda parcial o total');

        // 3. Actualizar importe pagado
        // Si el nuevo total pagado es igual o mayor al total de la venta, la marcamos como completada
        $nuevoEstado = ($nuevoPagado >= $totalVenta) ? 'completada' : 'pendiente_pago';

        $sqlUpd = "UPDATE ventas 
                   SET pagado_a_cuenta = :pagado, estado = :estado, metodo_pago = :metodo 
                   WHERE id = :id";

        DBPDO::ejecutarConsulta($sqlUpd, [
            ':pagado' => $nuevoPagado,
            ':estado' => $nuevoEstado,
            ':metodo' => $metodo,
            ':id'     => $idVenta
        ]);

        return true;
    }
}
