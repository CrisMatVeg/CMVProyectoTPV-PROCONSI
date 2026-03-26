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
require_once __DIR__ . '/PriceEngine.php';

class VentaPDO
{

    public static function obtenerSiguienteTicket(): int
    {
        $sql = "SELECT COALESCE(MAX(numero_ticket), 0) + 1 AS siguiente FROM ventas";
        $q = DBPDO::ejecutarConsulta($sql);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return (int)$row['siguiente'];
    }

    /**
     * Formatea el número de ticket/factura según las reglas de negocio.
     * Ejemplo: T-2532026-1234
     */
    public static function formatTicketNumber($numero, $fecha, $esFactura): string
    {
        $prefix = $esFactura ? 'F' : 'T';
        $time = is_numeric($fecha) ? $fecha : strtotime($fecha);
        $datePart = date('jnY', $time); // j=dia sin ceros, n=mes sin ceros, Y=año 4 digitos
        return "{$prefix}-{$datePart}-{$numero}";
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

        $subtotal = round((float)$datos['subtotal'], 2);
        $descPct  = round((float)($datos['descuentoPct'] ?? 0), 2);

        if (isset($datos['descuentoAmt']) && (float)$datos['descuentoAmt'] > 0) {
            $descAmt = round((float)$datos['descuentoAmt'], 2);
        } else {
            $descAmt = round($subtotal * $descPct / 100, 2);
        }

        // Calcular total real, base e IVA directamente desde las líneas
        $totalBase = 0;
        $totalIva  = 0;
        $totalReal = 0;

        foreach ($datos['lineas'] as $linea) {
            $precioUnit = round((float)$linea['price'], 2);
            $qty        = (int)$linea['qty'];
            $totalLinea = round($precioUnit * $qty, 2);
            $ivaRate    = (float)($linea['iva'] ?? 21.00);

            $base = $totalLinea / (1 + $ivaRate / 100);
            $iva  = $totalLinea - $base;

            $totalBase += $base;
            $totalIva  += $iva;
            $totalReal += $totalLinea;
        }

        $base   = round($totalBase, 2);
        $ivaAmt = round($totalIva, 2);
        $total  = round($totalReal, 2);

        $tipoCliente      = in_array($datos['tipoCliente'] ?? '', ['particular', 'empresa']) ? $datos['tipoCliente'] : 'particular';
        $nombreCliente    = isset($datos['nombreCliente']) ? mb_substr(trim($datos['nombreCliente']), 0, 100) : null;
        $nifCliente       = isset($datos['nifCliente']) ? mb_substr(trim($datos['nifCliente']), 0, 20) : null;
        $metodoPago       = in_array($datos['metodoPago'] ?? '', ['efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'mixto']) ? $datos['metodoPago'] : 'efectivo';
        $idCliente        = isset($datos['idCliente']) ? (int)$datos['idCliente'] : null;
        $efectivoRecibido = round((float)($datos['efectivo']['recibido'] ?? $datos['efectivoRecibido'] ?? 0), 2);

        $totalPagadoCalculado = 0;
        $pagadoACuenta = 0;
        $fechaLimite = null;

        if ($metodoPago === 'mixto' && !empty($datos['pagos'])) {
            foreach ($datos['pagos'] as $pago) {
                if ($pago['metodo'] !== 'a_cuenta') {
                    $totalPagadoCalculado += (float)$pago['importe'];
                } else {
                    $fechaLimite = $pago['fecha_limite'] ?? null;
                }
            }
            $pagadoACuenta = $totalPagadoCalculado; // Para reportes, cuánto ha dejado pagado inicialmente
            $estado = ($totalPagadoCalculado < $total - 0.01) ? 'pendiente_pago' : 'completada';
        } elseif ($metodoPago === 'a_cuenta') {
            $pagadoACuenta = round((float)($datos['pagadoACuenta'] ?? 0), 2);
            $totalPagadoCalculado = $pagadoACuenta;
            $fechaLimite = !empty($datos['fechaLimitePago']) ? $datos['fechaLimitePago'] : null;
            $estado = 'pendiente_pago';
        } else {
            $totalPagadoCalculado = $total;
            $estado = 'completada';
        }

        $descuentoLabel = isset($datos['descuentoLabel']) ? mb_substr(trim($datos['descuentoLabel']), 0, 100) : null;

        $sqlVenta = "INSERT INTO ventas 
         (numero_ticket, fecha, id_usuario, id_cliente, tipo_cliente, nombre_cliente, nif_cliente, metodo_pago,
          subtotal, descuento_pct, descuento_amt, descuento_label, base_imponible, iva_amt, total, efectivo_recibido,
          estado, pagado_a_cuenta, fecha_limite_pago, es_factura, comentarios, id_turno,
          puntos_ganados, puntos_canjeados, puntos_descuento_amt)
        VALUES (:ticket, NOW(), :usuario, :cliente, :tipo, :nombre, :nif, :metodo,
          :subtotal, :descPct, :descAmt, :descLabel, :base, :ivaAmt, :total, :efectivo,
          :estado, :pagadoACuenta, :fechaLimite, :esFactura, :comentarios, :idTurno,
          :puntosGanados, :puntosCanjeados, :puntosDescuentoAmt)";

        $paramsVenta = [
            ':ticket'        => $numTicket,
            ':usuario'       => $idUsuario,
            ':cliente'       => $idCliente,
            ':tipo'          => $tipoCliente,
            ':nombre'        => $nombreCliente,
            ':nif'           => $nifCliente,
            ':metodo'        => $metodoPago,
            ':subtotal'      => $subtotal,
            ':descPct'       => $descPct,
            ':descAmt'       => $descAmt,
            ':descLabel'     => $descuentoLabel,
            ':base'          => $base,
            ':ivaAmt'        => $ivaAmt,
            ':total'         => $total,
            ':efectivo'      => $efectivoRecibido,
            ':estado'        => $estado,
            ':pagadoACuenta' => $pagadoACuenta,
            ':fechaLimite'   => $fechaLimite,
            ':esFactura'     => (!empty($datos['esFactura']) || $total >= 3000 ? 1 : 0),
            ':comentarios'   => isset($datos['comentarios']) ? $datos['comentarios'] : null,
            ':idTurno'       => isset($datos['idTurno']) ? (int)$datos['idTurno'] : null,
            ':puntosGanados' => (int)($datos['puntosGanados'] ?? 0),
            ':puntosCanjeados' => (int)($datos['puntosCanjeados'] ?? 0),
            ':puntosDescuentoAmt' => (float)($datos['puntosDescuentoAmt'] ?? 0.00)
        ];

        require_once __DIR__ . '/ProductoPDO.php';
        ProductoPDO::init();

        $db = DBPDO::getPDO();
        $db->beginTransaction();

        try {
            DBPDO::ejecutarConsulta($sqlVenta, $paramsVenta);

            $qId   = DBPDO::ejecutarConsulta("SELECT id FROM ventas WHERE numero_ticket = :t", [':t' => $numTicket]);
            $rowId = $qId->fetch(PDO::FETCH_ASSOC);
            $idVenta = (int)$rowId['id'];

            $fechaVenta = date('Y-m-d');

            foreach ($datos['lineas'] as $linea) {
                $precioUnit  = round((float)$linea['price'], 2);
                $qty         = (int)$linea['qty'];
                $totalLinea  = round($precioUnit * $qty, 2);
                $ivaAplicado = (float)($linea['iva'] ?? 21.00);

                $mesesGarantia   = 24;
                $codigoIva       = 'GENERAL';
                $precioCosteUnit = 0.00;
                $idProducto      = isset($linea['id']) ? (int)$linea['id'] : null;

                if ($idProducto > 0) {
                    require_once __DIR__ . '/ProductoPDO.php';
                    $prodData = ProductoPDO::obtenerProductoPorId($idProducto);
                    if ($prodData) {
                        $mesesGarantia   = (int)($prodData['meses_garantia'] ?? 24);
                        $precioCosteUnit = (float)($prodData['precio_coste'] ?? 0);
                        if (!empty($prodData['codigo_iva'])) {
                            $codigoIva = $prodData['codigo_iva'];
                        }
                    }

                    try {
                        require_once __DIR__ . '/TipoIVAPDO.php';
                        $tipoIva = TipoIVAPDO::obtenerVigentePorCodigo($codigoIva, $fechaVenta);
                        if ($tipoIva && isset($tipoIva['porcentaje'])) {
                            $ivaAplicado = (float)$tipoIva['porcentaje'];
                        }
                    } catch (\Throwable $e) {
                    }
                }

                $precioBaseSnapshot = $precioUnit;
                $descuentosLog      = [];
                if ($idProducto > 0) {
                    try {
                        $breakdown          = PriceEngine::calculate($idProducto, $idCliente, $qty, $datos['codigoCupon'] ?? null);
                        $precioBaseSnapshot = $breakdown['precio_base'];
                        $descuentosLog      = $breakdown['descuentos'];
                    } catch (\Throwable $e) {
                    }
                }

                $sqlLinea = "INSERT INTO lineas_venta 
                (id_venta, id_producto, nombre_producto, codigo_producto, precio_unitario, precio_base_snapshot, precio_coste_unitario, iva_aplicado, cantidad, meses_garantia, total_linea, numero_serie)
                VALUES (:venta, :prod, :nombre, :codigo, :precio, :base_snap, :coste, :iva, :qty, :garantia, :total, :serial)";

                DBPDO::ejecutarConsulta($sqlLinea, [
                    ':venta'     => $idVenta,
                    ':prod'      => ($idProducto > 0 ? $idProducto : null),
                    ':nombre'    => mb_substr($linea['name'] ?? 'Producto', 0, 100),
                    ':codigo'    => mb_substr($linea['codigo'] ?? '', 0, 50),
                    ':precio'    => $precioUnit,
                    ':base_snap' => $precioBaseSnapshot,
                    ':coste'     => round($precioCosteUnit, 2),
                    ':iva'       => $ivaAplicado,
                    ':qty'       => $qty,
                    ':garantia'  => $mesesGarantia,
                    ':total'     => $totalLinea,
                    ':serial'    => isset($linea['serials']) ? (is_string($linea['serials']) ? $linea['serials'] : json_encode($linea['serials'])) : null
                ]);

                $idLineaInserción = $db->lastInsertId();

                if ($idLineaInserción && !empty($descuentosLog)) {
                    foreach ($descuentosLog as $d) {
                        DBPDO::ejecutarConsulta(
                            "INSERT INTO lineas_venta_descuentos (id_linea_venta, tipo_descuento, id_origen, valor_descontado)
                         VALUES (:linea, :tipo, :origen, :valor)",
                            [
                                ':linea'  => $idLineaInserción,
                                ':tipo'   => $d['tipo_descuento'],
                                ':origen' => $d['id_origen'],
                                ':valor'  => $d['valor_descontado']
                            ]
                        );
                    }
                }

                if ($idProducto > 0) {
                    require_once __DIR__ . '/ProductoPDO.php';
                    require_once __DIR__ . '/MovimientoStockPDO.php';

                    if (!empty($prodData['es_pack'])) {
                        $componentes = ProductoPDO::obtenerComponentesPack($idProducto);
                        foreach ($componentes as $comp) {
                            $qtyComponente = $qty * (int)$comp['cantidad'];
                            ProductoPDO::reducirStock((int)$comp['id_producto'], $qtyComponente);
                            MovimientoStockPDO::registrarMovimiento((int)$comp['id_producto'], 'venta', -$qtyComponente, $idUsuario, "Venta Pack #$numTicket", $db);
                        }
                    } else {
                        ProductoPDO::reducirStock($idProducto, $qty);
                        MovimientoStockPDO::registrarMovimiento($idProducto, 'venta', -$qty, $idUsuario, "Venta Ticket #$numTicket", $db);
                    }
                }
            }

            if (!empty($datos['idVale']) || !empty($datos['codigoVale'])) {
                $codigoVale  = $datos['codigoVale'] ?? null;
                $importeVale = (float)($datos['importeVale'] ?? 0);

                if ($codigoVale && $importeVale > 0) {
                    require_once __DIR__ . '/ValePDO.php';
                    $consumir = min($total, $importeVale);
                    if (ValePDO::consumirVale($codigoVale, $consumir)) {
                        require_once __DIR__ . '/PagoPDO.php';
                        PagoPDO::registrarPago($idVenta, $consumir, 'vale', $idUsuario, "Pago parcial con vale #$codigoVale");
                        $importeVale = $consumir;
                    } else {
                        $importeVale = 0;
                    }
                }
            }

            // [NUEVO] Soporte para pagos mixtos
            if (!empty($datos['pagos']) && is_array($datos['pagos'])) {
                require_once __DIR__ . '/PagoPDO.php';
                foreach ($datos['pagos'] as $pago) {
                    $importePago = round((float)$pago['importe'], 2);
                    $metodo = in_array($pago['metodo'] ?? '', ['efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'vale']) ? $pago['metodo'] : 'efectivo';
                    // We DO NOT register 'a_cuenta' in pagos_venta because it is pending debt, not a payment.
                    if ($importePago > 0 && $metodo !== 'a_cuenta') {
                        PagoPDO::registrarPago($idVenta, $importePago, $metodo, $idUsuario, 'Pago de la venta', $datos['idTurno'] ?? null);
                    }
                }
            } else {
                // Lógica legacy para un solo método de pago
                if ($metodoPago === 'a_cuenta' && $pagadoACuenta > 0) {
                    require_once __DIR__ . '/PagoPDO.php';
                    PagoPDO::registrarPago($idVenta, $pagadoACuenta, 'efectivo', $idUsuario, 'Entrega inicial al registrar venta', $datos['idTurno'] ?? null);
                } else if ($metodoPago !== 'a_cuenta' && $metodoPago !== 'mixto') {
                    $importeVale  = (float)($datos['importeVale'] ?? 0);
                    $pagoRestante = round($total - $importeVale, 2);
                    if ($pagoRestante > 0) {
                        require_once __DIR__ . '/PagoPDO.php';
                        PagoPDO::registrarPago($idVenta, $pagoRestante, $metodoPago, $idUsuario, 'Pago de la venta', $datos['idTurno'] ?? null);
                    }
                }
            }


            // [NUEVO] Actualizar puntos del cliente
            if ($idCliente) {
                require_once __DIR__ . '/ClientePDO.php';
                // Primero check expiración por si acaso
                ClientePDO::checkExpiracion($idCliente);
                
                // Sumar puntos ganados
                if (!empty($paramsVenta[':puntosGanados'])) {
                    ClientePDO::sumarPuntos($idCliente, $paramsVenta[':puntosGanados']);
                }
                // Restar puntos canjeados
                if (!empty($paramsVenta[':puntosCanjeados'])) {
                    ClientePDO::restarPuntos($idCliente, $paramsVenta[':puntosCanjeados']);
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
        $sqlVenta = "SELECT v.*, u.nombre AS nombre_cajero, c.puntos AS puntos_cliente_actual
                     FROM ventas v
                     LEFT JOIN usuarios u ON v.id_usuario = u.id
                     LEFT JOIN clientes c ON v.id_cliente = c.id
                     WHERE v.numero_ticket = :t";
        $q = DBPDO::ejecutarConsulta($sqlVenta, [':t' => $numTicket]);
        $venta = $q->fetch(PDO::FETCH_ASSOC);
        if (!$venta) return null;

        $sqlLineas = "SELECT lv.*, TRIM(SUBSTRING_INDEX(lv.nombre_producto, '(', 1)) as nombre_producto
                      FROM lineas_venta lv
                      WHERE lv.id_venta = :v 
                      ORDER BY lv.id ASC";
        $qL = DBPDO::ejecutarConsulta($sqlLineas, [':v' => $venta['id']]);
        $lineas = $qL->fetchAll(PDO::FETCH_ASSOC);

        // --- FETCH DISCOUNTS FOR EACH LINE WITH NAMES ---
        foreach ($lineas as &$l) {
            $sqlD = "SELECT lvd.*, 
                            CASE 
                                WHEN lvd.tipo_descuento = 'tarifa' THEN tp.nombre
                                WHEN lvd.tipo_descuento = 'promocion' THEN p.descripcion
                                WHEN lvd.tipo_descuento = 'cupon' THEN p.descripcion
                                ELSE NULL 
                            END as nombre_descuento
                     FROM lineas_venta_descuentos lvd
                     LEFT JOIN tarifas_precios tp ON lvd.tipo_descuento = 'tarifa' AND lvd.id_origen = tp.id
                     LEFT JOIN promociones p ON (lvd.tipo_descuento = 'promocion' OR lvd.tipo_descuento = 'cupon') AND lvd.id_origen = p.id
                     WHERE lvd.id_linea_venta = :id";
            $qD = DBPDO::ejecutarConsulta($sqlD, [':id' => $l['id']]);
            $l['descuentos'] = $qD->fetchAll(PDO::FETCH_ASSOC);
        }
        $venta['lineas'] = $lineas;


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

    /**
     * Convierte una venta existente (ticket) en factura actualizando los datos del cliente.
     * No modifica stock ni líneas. Solo actualiza la cabecera de la venta.
     */
    public static function convertirAFactura(int $idVenta, string $nombreCliente, string $nifCliente): bool
    {
        $nombreCliente = mb_substr(trim($nombreCliente), 0, 100);
        $nifCliente    = mb_substr(trim($nifCliente), 0, 20);

        if ($nombreCliente === '' || $nifCliente === '') {
            return false;
        }

        $sql = "UPDATE ventas 
                SET es_factura    = 1,
                    tipo_cliente  = 'empresa',
                    nombre_cliente = :nombre,
                    nif_cliente   = :nif
                WHERE id = :id";

        DBPDO::ejecutarConsulta($sql, [
            ':id'     => $idVenta,
            ':nombre' => $nombreCliente,
            ':nif'    => $nifCliente,
        ]);

        return true;
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
    public static function devolverLinea(int $idLinea, ?string $motivo = null, string $metodoReembolso = 'efectivo', ?int $cantidadADevolver = null): bool
    {
        // 1. Obtener datos de la línea original
        $sql = "SELECT * FROM lineas_venta WHERE id = :id";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idLinea]);
        $l = $q->fetch(PDO::FETCH_ASSOC);

        if (!$l || $l['devuelta']) return false;

        $idVenta = (int)$l['id_venta'];
        $cantidadOriginal = (int)$l['cantidad'];
        $qtyDevolucion = ($cantidadADevolver !== null && $cantidadADevolver > 0) ? $cantidadADevolver : $cantidadOriginal;

        if ($qtyDevolucion > $cantidadOriginal) {
            $qtyDevolucion = $cantidadOriginal;
        }

        // 2. Lógica de DIVISIÓN de línea si es parcial
        $idLineaFinal = $idLinea; // Por defecto operamos sobre la original
        $importeAReembolsar = (float)$l['total_linea'];

        if ($qtyDevolucion < $cantidadOriginal) {
            // Caso PARCIAL: Dividimos
            $unidadesRestantes = $cantidadOriginal - $qtyDevolucion;
            $precioUnitario = (float)$l['precio_unitario'];
            $totalRestante = round($unidadesRestantes * $precioUnitario, 2);
            $totalDevuelto = round($qtyDevolucion * $precioUnitario, 2);
            $importeAReembolsar = $totalDevuelto;

            // A. Actualizamos la original para que solo tenga lo NO devuelto
            DBPDO::ejecutarConsulta(
                "UPDATE lineas_venta SET cantidad = :q, total_linea = :t WHERE id = :id",
                [':q' => $unidadesRestantes, ':t' => $totalRestante, ':id' => $idLinea]
            );

            // B. Creamos una NUEVA línea marcada como devuelta
            $campos = array_keys($l);
            $campos = array_filter($campos, fn($c) => $c !== 'id'); // Quitamos el ID autoincremental

            $sqlIns = "INSERT INTO lineas_venta (" . implode(',', $campos) . ") 
                       VALUES (:" . implode(',:', $campos) . ")";

            $paramsIns = [];
            foreach ($campos as $c) {
                $paramsIns[":" . $c] = $l[$c];
            }

            // Ajustamos los valores específicos para la "rama" devuelta
            $paramsIns[':cantidad'] = $qtyDevolucion;
            $paramsIns[':total_linea'] = $totalDevuelto;
            $paramsIns[':devuelta'] = 1;

            DBPDO::ejecutarConsulta($sqlIns, $paramsIns);
            $idLineaFinal = (int)DBPDO::getPDO()->lastInsertId();
        }

        // 3. Marcar la línea (la original o la nueva dividida) como devuelta formalmente
        $sqlUpd = "UPDATE lineas_venta 
                   SET devuelta = 1, 
                       motivo_devolucion = :motivo, 
                       fecha_devolucion = NOW(),
                       metodo_reembolso = :metodo
                   WHERE id = :id";

        DBPDO::ejecutarConsulta($sqlUpd, [
            ':id' => $idLineaFinal,
            ':motivo' => $motivo ? mb_substr($motivo, 0, 255) : 'Devolución estándar',
            ':metodo' => $metodoReembolso
        ]);

        // 4. Gestionar REEMBOLSO (VOUCHER / VALE / EFECTIVO)
        // Obtenemos el cliente de la venta para el vale (necesario si no estaba en el fetch inicial)
        if ($metodoReembolso === 'vale' || $metodoReembolso === 'efectivo') {
            $qV = DBPDO::ejecutarConsulta("SELECT id_cliente FROM ventas WHERE id = :id", [':id' => $idVenta]);
            $v = $qV->fetch(PDO::FETCH_ASSOC);
            $idCliente = $v ? $v['id_cliente'] : null;

            if ($metodoReembolso === 'vale') {
                require_once __DIR__ . '/ValePDO.php';
                $codigoVale = ValePDO::crearVale($idCliente ? (int)$idCliente : null, $idVenta, $importeAReembolsar);
                LogPDO::addLog('GENERACION_VALE', "Vale generado (#$codigoVale) por importe de " . number_format($importeAReembolsar, 2, ',', '.') . "€ tras devolución parcial/total de línea #$idLinea");
            } elseif ($metodoReembolso === 'efectivo') {
                require_once __DIR__ . '/CajaTurnoPDO.php';
                $turno = CajaTurnoPDO::obtenerTurnoAbierto();
                if ($turno) {
                    CajaTurnoPDO::registrarRetiro(
                        (int)$turno['id'],
                        $_SESSION['usuarioActualTPV']->getId(),
                        $importeAReembolsar,
                        "Devolución en efectivo ($qtyDevolucion uds): Línea #$idLinea (Venta #$idVenta)"
                    );
                }
            }
        }

        // 5. Reponer stock del producto devuelto
        if ($l['id_producto']) {
            require_once __DIR__ . '/ProductoPDO.php';
            $prod = ProductoPDO::obtenerProductoPorId((int)$l['id_producto']);
            if (!empty($prod['es_pack'])) {
                $componentes = ProductoPDO::obtenerComponentesPack((int)$l['id_producto']);
                foreach ($componentes as $comp) {
                    $qtyComponente = $qtyDevolucion * (int)$comp['cantidad'];
                    ProductoPDO::aumentarStock((int)$comp['id_producto'], $qtyComponente);
                    MovimientoStockPDO::registrarMovimiento((int)$comp['id_producto'], 'devolucion', $qtyComponente, null, "Devolución parcial de Pack #$idLinea");
                }
            } else {
                ProductoPDO::aumentarStock((int)$l['id_producto'], $qtyDevolucion);
                MovimientoStockPDO::registrarMovimiento((int)$l['id_producto'], 'devolucion', $qtyDevolucion, null, "Devolución parcial de linea #$idLinea");
            }
        }

        // 6. Gestionar REEMPLAZO (Sustitución)
        if ($metodoReembolso === 'reemplazo') {
            if ($l['id_producto']) {
                if (!empty($prod['es_pack'])) {
                    $componentes = ProductoPDO::obtenerComponentesPack((int)$l['id_producto']);
                    foreach ($componentes as $comp) {
                        $qtyComponente = $qtyDevolucion * (int)$comp['cantidad'];
                        ProductoPDO::reducirStock((int)$comp['id_producto'], $qtyComponente);
                        MovimientoStockPDO::registrarMovimiento((int)$comp['id_producto'], 'venta', -$qtyComponente, null, "Cambio por garantía (REEMPLAZO) de Pack #$idLinea");
                    }
                } else {
                    ProductoPDO::reducirStock((int)$l['id_producto'], $qtyDevolucion);
                    MovimientoStockPDO::registrarMovimiento((int)$l['id_producto'], 'venta', -$qtyDevolucion, null, "Cambio por garantía (REEMPLAZO) de linea #$idLinea");
                }
            }
        }

        // 7. Si todas las líneas de la venta están devueltas, marcar la venta como devuelta
        if ($idVenta) {
            $sqlStats = "SELECT COUNT(*) AS total, SUM(CASE WHEN devuelta = 1 THEN 1 ELSE 0 END) AS devueltas
                         FROM lineas_venta WHERE id_venta = :idv";
            $qStats = DBPDO::ejecutarConsulta($sqlStats, [':idv' => $idVenta]);
            $stats = $qStats->fetch(PDO::FETCH_ASSOC);

            if ($stats && (int)$stats['total'] > 0 && (int)$stats['total'] === (int)$stats['devueltas']) {
                DBPDO::ejecutarConsulta("UPDATE ventas SET estado = 'devuelta' WHERE id = :idv", [':idv' => $idVenta]);
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
                WHERE v.estado = 'completada' AND v.metodo_pago IN ('efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'mixto') AND DATE(v.fecha) BETWEEN :desde2 AND :hasta2";

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
                WHERE estado = 'completada' AND metodo_pago IN ('efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'mixto') AND DATE(fecha) BETWEEN :desde AND :hasta
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
                WHERE estado = 'completada' AND metodo_pago != 'financiado' AND DATE(fecha) BETWEEN :desde AND :hasta
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
                WHERE v.estado = 'completada' AND v.metodo_pago != 'financiado' AND DATE(v.fecha) BETWEEN :desde AND :hasta
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
                WHERE v.estado = 'completada' AND v.metodo_pago != 'financiado' AND lv.devuelta = 0 
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
                    MAX(lv.id_producto) AS id_producto,
                    TRIM(SUBSTRING_INDEX(COALESCE(
                        NULLIF(TRIM(lv.nombre_producto), ''),
                        NULLIF(TRIM(p.nombre), ''),
                        CONCAT('Producto ID ', lv.id_producto)
                    ), '(', 1)) AS nombre_producto_limpio,
                    COALESCE(
                        NULLIF(TRIM(lv.codigo_producto), ''),
                        NULLIF(TRIM(p.referencia), ''),
                        CONCAT('REF-', IFNULL(lv.id_producto, 0))
                    ) AS codigo_producto,
                    SUM(lv.cantidad) AS unidades,
                    SUM(lv.total_linea) AS total_recaudado
                FROM lineas_venta lv
                JOIN ventas v ON lv.id_venta = v.id
                LEFT JOIN productos p ON lv.id_producto = p.id
                WHERE v.estado = 'completada' 
                  AND v.metodo_pago != 'financiado'
                  AND lv.devuelta = 0
                  AND DATE(v.fecha) BETWEEN :desde AND :hasta
                GROUP BY nombre_producto_limpio, codigo_producto
                ORDER BY unidades DESC, total_recaudado DESC
                LIMIT :limite";

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
                WHERE v.estado = 'completada' AND v.metodo_pago != 'financiado' AND lv.devuelta = 0
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
                    MIN(p.id) AS id_producto,
                    TRIM(SUBSTRING_INDEX(p.nombre, '(', 1)) AS nombre_producto_limpio,
                    p.referencia AS codigo_producto,
                    p.categoria,
                    COALESCE(SUM(lv.cantidad), 0) AS unidades,
                    COALESCE(SUM(lv.total_linea), 0) AS total_recaudado
                FROM productos p
                LEFT JOIN lineas_venta lv ON p.id = lv.id_producto
                LEFT JOIN ventas v ON lv.id_venta = v.id 
                    AND v.estado = 'completada' 
                    AND v.metodo_pago != 'financiado'
                    AND lv.devuelta = 0 
                    AND DATE(v.fecha) BETWEEN :desde AND :hasta
                GROUP BY nombre_producto_limpio, codigo_producto, p.categoria
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
                WHERE v.estado = 'completada' AND v.metodo_pago != 'financiado' AND lv.devuelta = 0
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
        require_once __DIR__ . '/PagoPDO.php';
        require_once __DIR__ . '/CajaTurnoPDO.php';
        $turno = CajaTurnoPDO::obtenerTurnoAbierto();
        $idTurno = $turno ? (int)$turno['id'] : null;

        PagoPDO::registrarPago($idVenta, $importe, $metodo, $idUsuario, 'Abono de deuda parcial o total', $idTurno);

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
