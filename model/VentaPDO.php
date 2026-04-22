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
require_once dirname(__DIR__) . '/config/config.php';

class VentaPDO
{

    public static function obtenerSiguienteTicket(?PDO $db = null): int
    {
        $db = $db ?? DBPDO::getPDO();
        
        // Bloqueamos la fila del correlativo para este ticket
        $stmt = $db->prepare("SELECT valor FROM correlativos WHERE nombre = 'ticket' FOR UPDATE");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            // Si por alguna razón no existe, lo inicializamos (seguridad adicional)
            $db->query("INSERT INTO correlativos (nombre, valor) VALUES ('ticket', 1000) ON DUPLICATE KEY UPDATE valor = valor");
            $next = 1001;
        } else {
            $next = (int)$row['valor'] + 1;
        }
        
        $upd = $db->prepare("UPDATE correlativos SET valor = ? WHERE nombre = 'ticket'");
        $upd->execute([$next]);
        
        return $next;
    }

    /**
     * Formatea el número de ticket/factura/abono según las reglas de negocio.
     * Ejemplo: T-2532026-1234 / F-2532026-1234 / A-2532026-1234
     */
    public static function formatTicketNumber($numero, $fecha, $esFactura, string $tipoDocumento = 'venta'): string
    {
        if ($tipoDocumento === 'abono') {
            $prefix = 'A';
        } else {
            $prefix = $esFactura ? 'F' : 'T';
        }
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
        $numTicket = 0; // Inicializar para evitar error de variable no definida
        // El número de ticket se obtendrá dentro de la transacción para asegurar atomicidad
        // $numTicket = self::obtenerSiguienteTicket(); 


        $subtotal = round((float)$datos['subtotal'], 2);
        $descPct  = round((float)($datos['descuentoPct'] ?? 0), 2);

        if (isset($datos['descuentoAmt']) && (float)$datos['descuentoAmt'] > 0) {
            $descAmt = round((float)$datos['descuentoAmt'], 2);
        } else {
            $descAmt = round($subtotal * $descPct / 100, 2);
        }

        // 1. Calcular total bruto real desde las líneas enviadas (PVP con IVA)
        $totalBase = 0;
        $totalIva  = 0;
        $totalReal = 0;

        foreach ($datos['lineas'] as $linea) {
            $precioUnit = round((float)$linea['price'], 2);
            $qty        = (int)$linea['qty'];
            $totalLinea = round($precioUnit * $qty, 2);
            $ivaRate    = (float)($linea['iva'] ?? 21.00);

            $lineBase = $totalLinea / (1 + $ivaRate / 100);
            $lineIva  = $totalLinea - $lineBase;

            $totalBase += $lineBase;
            $totalIva  += $lineIva;
            $totalReal += $totalLinea;
        }

        // 2. Determinar el Total Neto Real que el cliente DEBE pagar.
        // El frontend envía 'descuentoAmt' que ya es el total a descontar (incluye promos y descuentos fijos, pero NO puntos ya que son pago).
        // El Total Neto es Bruto - Descuento.
        $totalNetoCalculado = round($totalReal - $descAmt, 2);
        if ($totalNetoCalculado < 0) $totalNetoCalculado = 0;

        // 3. Ajustar Base e IVA proporcionalmente para que la suma cuadre con el Total Neto.
        if ($totalReal > 0) {
            $factorEscala = $totalNetoCalculado / $totalReal;
            $base   = round($totalBase * $factorEscala, 2);
            $ivaAmt = round($totalIva * $factorEscala, 2);
            // El Total final es la suma de base + iva (para evitar descuadres de redondeo)
            $total = round($base + $ivaAmt, 2);
        } else {
            $base   = 0;
            $ivaAmt = 0;
            $total  = 0;
        }

        // Aseguramos que los datos normalizados para persistir no sean negativos individualmente
        $datos['subtotal'] = $totalReal;
        $datos['total']    = $total;
        $datos['base']     = $base;
        $datos['iva']      = $ivaAmt;

        $tipoCliente      = in_array($datos['tipoCliente'] ?? '', ['particular', 'empresa']) ? $datos['tipoCliente'] : 'particular';
        $nombreCliente    = isset($datos['nombreCliente']) ? mb_substr(trim($datos['nombreCliente']), 0, 100) : null;
        $nifCliente       = isset($datos['nifCliente']) ? mb_substr(trim($datos['nifCliente']), 0, 20) : null;
        $metodoPago       = in_array($datos['metodoPago'] ?? '', ['efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'mixto', 'puntos']) ? $datos['metodoPago'] : 'efectivo';
        $idCliente        = isset($datos['idCliente']) ? (int)$datos['idCliente'] : null;
        $efectivoRecibido = round((float)($datos['efectivo']['recibido'] ?? $datos['efectivoRecibido'] ?? 0), 2);

        // [FallBack] Si no viene arriba, buscar en el desglose de pagos (útil para TPV modular)
        if ($efectivoRecibido <= 0.005 && !empty($datos['pagos'])) {
            foreach ($datos['pagos'] as $p) {
                if (($p['metodo'] ?? $p['metodo_pago'] ?? '') === 'efectivo') {
                    $efectivoRecibido = round((float)($p['recibido'] ?? $p['importe'] ?? 0), 2);
                    break;
                }
            }
        }

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
            // Safety Cap: Pagado inicial no puede superar el total neto de la venta
            if ($pagadoACuenta > $total) $pagadoACuenta = $total;
            $totalPagadoCalculado = $pagadoACuenta;
            $fechaLimite = !empty($datos['fechaLimitePago']) ? $datos['fechaLimitePago'] : null;
            $estado = 'pendiente_pago';
        } else {
            $totalPagadoCalculado = $total;
            $estado = 'completada';
        }

        // --- VALIDACIÓN DE EFECTIVO DISPONIBLE PARA EL CAMBIO ---
        $cambio = 0;
        if ($efectivoRecibido > 0) {
            $importeEfectivoReal = 0;
            if ($metodoPago === 'efectivo') {
                $importeEfectivoReal = $total;
            } elseif ($metodoPago === 'mixto' && !empty($datos['pagos'])) {
                foreach ($datos['pagos'] as $pago) {
                    if ($pago['metodo'] === 'efectivo') {
                        $importeEfectivoReal += (float)$pago['importe'];
                    }
                }
            }
            $cambio = round($efectivoRecibido - $importeEfectivoReal, 2);
        }

        if ($cambio > 0.005) {
            require_once __DIR__ . '/CajaTurnoPDO.php';
            $efectivoEnCaja = CajaTurnoPDO::obtenerEfectivoActual();
            if ($efectivoEnCaja < $cambio - 0.009) {
                throw new \Exception("No hay suficiente efectivo en el cajón para el cambio (" . number_format($cambio, 2, ',', '.') . "€). Disponible: " . number_format($efectivoEnCaja, 2, ',', '.') . "€");
            }
        }
        // -------------------------------------------------------

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

        $db = DBPDO::getPDO();
        $db->beginTransaction();

        try {
            $numTicket = self::obtenerSiguienteTicket($db);
            $paramsVenta[':ticket'] = $numTicket;

            DBPDO::ejecutarConsulta($sqlVenta, $paramsVenta);

            $qId   = DBPDO::ejecutarConsulta("SELECT id FROM ventas WHERE numero_ticket = :t", [':t' => $numTicket]);
            $rowId = $qId->fetch(PDO::FETCH_ASSOC);
            $idVenta = (int)$rowId['id'];

            $fechaVenta = date('Y-m-d');

            // [NUEVO] Validación de stock atómica (FOR UPDATE)
            foreach ($datos['lineas'] as $linea) {
                $idProd = (int)($linea['id'] ?? 0);
                if ($idProd <= 0) continue;
                $qtySolicitada = (int)$linea['qty'];

                $sqlLock = "SELECT id, nombre, stock_actual, es_pack FROM productos WHERE id = :id FOR UPDATE";
                $prodLock = DBPDO::ejecutarConsulta($sqlLock, [':id' => $idProd])->fetch(PDO::FETCH_ASSOC);

                if (!$prodLock) throw new Exception("Producto '{$linea['name']}' no encontrado.");

                if (!empty($prodLock['es_pack'])) {
                    $componentes = ProductoPDO::obtenerComponentesPack($idProd);
                    foreach ($componentes as $comp) {
                        $qtyTotalCompo = $qtySolicitada * (int)$comp['cantidad'];
                        $idCompo = (int)$comp['id_producto'];
                        
                        $sqlLockCompo = "SELECT nombre, stock_actual FROM productos WHERE id = :id FOR UPDATE";
                        $compoLock = DBPDO::ejecutarConsulta($sqlLockCompo, [':id' => $idCompo])->fetch(PDO::FETCH_ASSOC);
                        
                        if ($compoLock['stock_actual'] < $qtyTotalCompo) {
                            throw new Exception("Stock insuficiente para '{$compoLock['nombre']}' (Componente de '{$prodLock['nombre']}'). Disponible: {$compoLock['stock_actual']}, Requerido: $qtyTotalCompo");
                        }
                    }
                } else {
                    if ($prodLock['stock_actual'] < $qtySolicitada) {
                        throw new Exception("Stock insuficiente para '{$prodLock['nombre']}'. Disponible: {$prodLock['stock_actual']}, Requerido: $qtySolicitada");
                    }
                }
            }

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

                $precioBaseSnapshot = isset($linea['basePriceSnapshot']) ? (float)$linea['basePriceSnapshot'] : $precioUnit;
                $descuentosLog      = isset($linea['descuentos']) ? $linea['descuentos'] : [];
                
                // Si NO vienen descuentos del frontend, intentamos recalcular (fallback compatibilidad legacy)
                // Usamos la snapshot ya proporcionada si existe para evitar variaciones por tarifas expiradas
                if (empty($descuentosLog) && $idProducto > 0) {
                    try {
                        require_once __DIR__ . '/PriceEngine.php';
                        $breakdown          = PriceEngine::calculate($idProducto, $idCliente, $qty, $datos['codigoCupon'] ?? null);
                        if (!isset($linea['basePriceSnapshot'])) {
                            $precioBaseSnapshot = $breakdown['precio_base'];
                        }
                        $descuentosLog      = $breakdown['descuentos'];
                    } catch (\Throwable $e) {
                        // Si falla el motor de precios, mantenemos los valores básicos de la línea
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
                    $metodo = in_array($pago['metodo'] ?? '', ['efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'vale', 'puntos']) ? $pago['metodo'] : 'efectivo';
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

            // [VERIFACTU] Encadenamiento y Huella
            $fechaFormat = date('d-m-Y'); // DD-MM-YYYY de acuerdo al XSD de la AEAT
            $tipoFC      = ($paramsVenta[':esFactura'] ? 'F1' : 'F2');
            $numFormated = self::formatTicketNumber($numTicket, time(), $paramsVenta[':esFactura'], 'venta');
            
            self::procesarVeriFactu($db, $idVenta, $numFormated, $tipoFC, (float)$total, (float)$ivaAmt, $fechaFormat, $nifCliente ?? '', $nombreCliente ?? 'Cliente General');

            $db->commit();

            // [NUEVO] Envío VeriFactu FUERA de la transacción para evitar conflictos
            try {
                require_once __DIR__ . '/AeatQueueService.php';
                (new AeatQueueService())->enviarEspecifico((int)$idVenta);
            } catch (\Exception $eVf) {
                error_log("Error en envío inmediato VeriFactu: " . $eVf->getMessage());
            }

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
        // DATE(v.fecha) = CURDATE() impide el uso de indices
        $hoy = date('Y-m-d');
        $sql = "SELECT v.*, u.nombre AS nombre_cajero 
            FROM ventas v 
            LEFT JOIN usuarios u ON v.id_usuario = u.id
            WHERE v.fecha >= :hoy ORDER BY v.fecha ASC";
        $q = DBPDO::ejecutarConsulta($sql, [':hoy' => $hoy . ' 00:00:00']);
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
        $sqlVenta = "SELECT v.*, u.nombre AS nombre_cajero, c.puntos AS puntos_cliente_actual, c.email AS cliente_email
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
        $lineasRaw = $qL->fetchAll(PDO::FETCH_ASSOC);

        // Map already returned quantities from Abonos without mutating original lines
        $devueltosPorProducto = [];
        if (($venta['tipo_documento'] ?? 'venta') === 'venta') {
            $sqlTodosAbonos = "SELECT lv.id_producto, COALESCE(SUM(ABS(lv.cantidad)), 0) as devueltos
                               FROM lineas_venta lv
                               JOIN ventas v ON lv.id_venta = v.id
                               WHERE v.id_venta_origen = :idv AND v.tipo_documento = 'abono'
                               GROUP BY lv.id_producto";
            $qAb = DBPDO::ejecutarConsulta($sqlTodosAbonos, [':idv' => $venta['id']]);
            while($row = $qAb->fetch(PDO::FETCH_ASSOC)) {
                $devueltosPorProducto[$row['id_producto']] = (int)$row['devueltos'];
            }
        }

        $lineas = [];
        foreach ($lineasRaw as $l) {
            $pid = $l['id_producto'];
            $cd = $devueltosPorProducto[$pid] ?? 0;
            $devueltos = min((int)$l['cantidad'], $cd);
            
            if ($devueltos > 0 && $devueltos < (int)$l['cantidad']) {
                $lDevuelta = $l;
                $lDevuelta['cantidad'] = $devueltos;
                $lDevuelta['total_linea'] = $devueltos * (float)$l['precio_unitario'];
                $lDevuelta['devuelta'] = 1;

                $lNormal = $l;
                $lNormal['cantidad'] = (int)$l['cantidad'] - $devueltos;
                $lNormal['total_linea'] = $lNormal['cantidad'] * (float)$l['precio_unitario'];
                $lNormal['devuelta'] = 0;

                $lineas[] = $lNormal;
                $lineas[] = $lDevuelta;
                
                $devueltosPorProducto[$pid] -= $devueltos;
            } elseif ($devueltos >= (int)$l['cantidad']) {
                $l['devuelta'] = 1;
                $lineas[] = $l;
                $devueltosPorProducto[$pid] -= $devueltos;
            } else {
                $l['devuelta'] = 0;
                $lineas[] = $l;
            }
        }

        // --- FETCH DISCOUNTS FOR ALL LINES AT ONCE (Fix N+1) ---
        $lineIds = array_unique(array_column($lineasRaw, 'id'));
        $descuentosPorLinea = [];
        
        if (!empty($lineIds)) {
            $placeholders = implode(',', array_fill(0, count($lineIds), '?'));
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
                     WHERE lvd.id_linea_venta IN ($placeholders)";
            
            $qD = DBPDO::ejecutarConsulta($sqlD, array_values($lineIds));
            while ($d = $qD->fetch(PDO::FETCH_ASSOC)) {
                $descuentosPorLinea[$d['id_linea_venta']][] = $d;
            }
        }

        foreach ($lineas as &$l) {
            $l['descuentos'] = $descuentosPorLinea[$l['id']] ?? [];
        }
        $venta['lineas'] = $lineas;


        // --- FETCH PAYMENTS (pagos_venta) ---
        require_once 'PagoPDO.php';
        $venta['pagos'] = PagoPDO::obtenerPagosPorVenta((int)$venta['id']);

        // --- FETCH ABONOS ASOCIADOS (tickets de devolución vinculados a esta venta) ---
        if (($venta['tipo_documento'] ?? 'venta') === 'venta') {
            $sqlAbonos = "SELECT v.id, v.numero_ticket, v.fecha, v.total, v.estado
                          FROM ventas v
                          WHERE v.id_venta_origen = :idv AND v.tipo_documento = 'abono'
                          ORDER BY v.fecha ASC";
            $qA = DBPDO::ejecutarConsulta($sqlAbonos, [':idv' => $venta['id']]);
            $venta['abonos'] = $qA->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $venta['abonos'] = [];
        }

        return $venta;
    }
    public static function obtenerVentaPorId(int $id): ?array
    {
        $sql = "SELECT numero_ticket FROM ventas WHERE id = :id";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return self::obtenerVentaPorTicket((int)$row['numero_ticket']);
    }

    public static function buscarVentas(string $desde, string $hasta, ?int $idUsuario = null, ?string $numTicket = null, string $tipoDocumento = 'todos', int $limit = 50, int $offset = 0, string $ordenPor = 'fecha', string $ordenDir = 'ASC'): array
    {
        // Whitelist para evitar inyección SQL en ORDER BY
        $columnasPermitidas = ['fecha', 'numero_ticket', 'total', 'nombre_cajero'];
        
        $ordenPor = in_array($ordenPor, $columnasPermitidas) ? $ordenPor : 'fecha';
        $ordenDir = strtoupper($ordenDir) === 'DESC' ? 'DESC' : 'ASC';

        // Mapear alias si es necesario
        $campoIdOrder = ($ordenPor === 'nombre_cajero') ? 'u_sub.nombre' : 'v_sub.' . $ordenPor;
        $campoFinalOrder = ($ordenPor === 'nombre_cajero') ? 'u.nombre' : 'v.' . $ordenPor;

        $params = [];
        $whereClause = "WHERE 1=1";

        if ($numTicket) {
            $whereClause .= " AND v_sub.numero_ticket LIKE :ticket";
            $params[':ticket'] = '%' . $numTicket . '%';
        } else {
            $whereClause .= " AND v_sub.fecha >= :desde AND v_sub.fecha <= :hasta";
            $params[':desde'] = $desde . ' 00:00:00';
            $params[':hasta'] = $hasta . ' 23:59:59';
        }

        if ($idUsuario) {
            $whereClause .= " AND v_sub.id_usuario = :usuario";
            $params[':usuario'] = $idUsuario;
        }

        if ($tipoDocumento === 'venta' || $tipoDocumento === 'abono') {
            $whereClause .= " AND v_sub.tipo_documento = :tipoDoc";
            $params[':tipoDoc'] = $tipoDocumento;
        }

        // Técnica: Late Row Lookup. Primero obtenemos solo los IDs de forma eficiente.
        $innerJoin = ($ordenPor === 'nombre_cajero') ? "JOIN usuarios u_sub ON v_sub.id_usuario = u_sub.id" : "";
        
        $sql = "SELECT v.*, u.nombre as nombre_cajero, vo.numero_ticket as numero_ticket_origen
                FROM (
                    SELECT v_sub.id 
                    FROM ventas v_sub
                    $innerJoin
                    $whereClause
                    ORDER BY $campoIdOrder $ordenDir
                    LIMIT :limit OFFSET :offset
                ) AS sub
                JOIN ventas v ON v.id = sub.id
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                LEFT JOIN ventas vo ON v.id_venta_origen = vo.id
                ORDER BY $campoFinalOrder $ordenDir";
        
        // PDO no permite bindParam en LIMIT/OFFSET en algunas versiones si no se emula,
        // así que los reemplazamos directamente tras castearlos a int para total seguridad.
        $sql = str_replace([':limit', ':offset'], [(int)$limit, (int)$offset], $sql);
        
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function contarVentas(string $desde, string $hasta, ?int $idUsuario = null, ?string $numTicket = null, string $tipoDocumento = 'todos'): int
    {
        $sql = "SELECT COUNT(*) FROM ventas v WHERE 1=1";
        $params = [];

        if ($numTicket) {
            $sql .= " AND v.numero_ticket LIKE :ticket";
            $params[':ticket'] = '%' . $numTicket . '%';
        } else {
            $sql .= " AND v.fecha >= :desde AND v.fecha <= :hasta";
            $params[':desde'] = $desde . ' 00:00:00';
            $params[':hasta'] = $hasta . ' 23:59:59';
        }

        if ($idUsuario) {
            $sql .= " AND v.id_usuario = :usuario";
            $params[':usuario'] = $idUsuario;
        }

        if ($tipoDocumento === 'venta') {
            $sql .= " AND v.tipo_documento = 'venta'";
        } elseif ($tipoDocumento === 'abono') {
            $sql .= " AND v.tipo_documento = 'abono'";
        }

        $q = DBPDO::ejecutarConsulta($sql, $params);
        return (int)$q->fetchColumn();
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

    public static function obtenerVentasPorCliente(int $idCliente, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT v.*, u.nombre as nombre_cajero 
                FROM ventas v
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                WHERE v.id_cliente = :cliente
                ORDER BY v.fecha DESC
                LIMIT :limit OFFSET :offset";
        
        $sql = str_replace([':limit', ':offset'], [(int)$limit, (int)$offset], $sql);
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
                $efectivoEnCaja = CajaTurnoPDO::obtenerEfectivoActual();
                if ($efectivoEnCaja < $importeAReembolsar - 0.009) {
                    throw new \Exception("No hay suficiente efectivo en el cajón para el reembolso (" . number_format($importeAReembolsar, 2, ',', '.') . "€). Disponible: " . number_format($efectivoEnCaja, 2, ',', '.') . "€");
                }

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
    public static function obtenerKPIs(string $desde, string $hasta, ?int $idUsuario = null, string $tipoDocumento = 'todos'): array
    {
        $desdeFull = $desde . ' 00:00:00';
        $hastaFull = $hasta . ' 23:59:59';

        $params = [
            ':desde'     => $desdeFull,
            ':hasta'     => $hastaFull
        ];

        $sql = "SELECT 
                    COUNT(v.id) as total_operaciones,
                    COALESCE(SUM(v.total), 0) as total_ventas,
                    COALESCE(SUM(v.base_imponible), 0) as total_base,
                    COALESCE(SUM(sub.margen_v), 0) as beneficio_estimado,
                    COALESCE(SUM(v.total) / NULLIF(COUNT(v.id), 0), 0) as ticket_medio
                FROM ventas v
                LEFT JOIN (
                    SELECT lv_sub.id_venta, SUM((lv_sub.precio_unitario - lv_sub.precio_coste_unitario) * lv_sub.cantidad) as margen_v
                    FROM lineas_venta lv_sub
                    INNER JOIN ventas v_sub ON lv_sub.id_venta = v_sub.id
                    WHERE lv_sub.devuelta = 0 
                      AND v_sub.fecha >= :desde AND v_sub.fecha <= :hasta
                    GROUP BY lv_sub.id_venta
                ) AS sub ON v.id = sub.id_venta
                WHERE v.estado = 'completada' 
                  AND v.metodo_pago IN ('efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'mixto') ";

        if ($idUsuario) {
            $sql .= " AND v.id_usuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }
        if ($tipoDocumento === 'venta' || $tipoDocumento === 'abono') {
            $sql .= " AND v.tipo_documento = :tipoDoc";
            $params[':tipoDoc'] = $tipoDocumento;
        }

        $sql .= " AND v.fecha >= :desde AND v.fecha <= :hasta";

        $q = DBPDO::ejecutarConsulta($sql, $params);
        $result = $q->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return [
                'total_operaciones' => 0,
                'total_ventas' => 0,
                'total_base' => 0,
                'beneficio_estimado' => 0,
                'ticket_medio' => 0
            ];
        }

        return $result;
    }

    /**
     * Obtiene ventas agrupadas por método de pago.
     */
    public static function obtenerVentasPorMetodo(string $desde, string $hasta, ?int $idUsuario = null, string $tipoDocumento = 'todos'): array
    {
        $sql = "SELECT metodo_pago, COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad
                FROM ventas
                WHERE estado = 'completada' 
                  AND metodo_pago IN ('efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'mixto') ";
        
        $params = [':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59'];
        if ($idUsuario) {
            $sql .= " AND id_usuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }
        if ($tipoDocumento === 'venta' || $tipoDocumento === 'abono') {
            $sql .= " AND tipo_documento = :tipoDoc";
            $params[':tipoDoc'] = $tipoDocumento;
        }
        $sql .= " AND fecha >= :desde AND fecha <= :hasta GROUP BY metodo_pago";

        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene evolución de ventas por día.
     */
    public static function obtenerVentasPorFecha(string $desde, string $hasta, ?int $idUsuario = null, string $tipoDocumento = 'todos'): array
    {
        $sql = "SELECT DATE(fecha) as fecha, SUM(total) as total
                FROM ventas
                WHERE estado = 'completada' 
                  AND metodo_pago IN ('efectivo', 'tarjeta', 'bizum', 'a_cuenta', 'mixto') ";

        $params = [':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59'];
        if ($idUsuario) {
            $sql .= " AND id_usuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }
        if ($tipoDocumento === 'venta' || $tipoDocumento === 'abono') {
            $sql .= " AND tipo_documento = :tipoDoc";
            $params[':tipoDoc'] = $tipoDocumento;
        }

        $sql .= " AND fecha >= :desde AND fecha <= :hasta
                GROUP BY DATE(fecha)
                ORDER BY DATE(fecha) ASC";
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas agrupadas por cajero/usuario.
     */
    public static function obtenerVentasPorCajero(string $desde, string $hasta, ?int $idUsuario = null, string $tipoDocumento = 'todos'): array
    {
        $sql = "SELECT u.nombre, COALESCE(SUM(v.total), 0) as total, COUNT(v.id) as cantidad
                FROM ventas v
                JOIN usuarios u ON v.id_usuario = u.id
                WHERE v.estado = 'completada' 
                  AND v.metodo_pago != 'financiado' ";

        $params = [':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59'];
        if ($idUsuario) {
            $sql .= " AND v.id_usuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }
        if ($tipoDocumento === 'venta' || $tipoDocumento === 'abono') {
            $sql .= " AND v.tipo_documento = :tipoDoc";
            $params[':tipoDoc'] = $tipoDocumento;
        }

        $sql .= " AND v.fecha >= :desde AND v.fecha <= :hasta
                GROUP BY u.id
                ORDER BY total DESC";
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas agrupadas por categoría.
     */
    public static function obtenerVentasPorCategoria(string $desde, string $hasta, ?int $idUsuario = null, string $tipoDocumento = 'todos'): array
    {
        $sql = "SELECT p.categoria, COALESCE(SUM(lv.total_linea), 0) as total, COUNT(lv.id) as cantidad
                FROM lineas_venta lv
                JOIN ventas v ON lv.id_venta = v.id
                JOIN productos p ON lv.id_producto = p.id
                WHERE v.estado = 'completada' 
                  AND v.metodo_pago != 'financiado' 
                  AND lv.devuelta = 0 ";

        $params = [':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59'];
        if ($idUsuario) {
            $sql .= " AND v.id_usuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }
        if ($tipoDocumento === 'venta' || $tipoDocumento === 'abono') {
            $sql .= " AND v.tipo_documento = :tipoDoc";
            $params[':tipoDoc'] = $tipoDocumento;
        }

        $sql .= " AND v.fecha >= :desde AND v.fecha <= :hasta
                GROUP BY p.categoria
                ORDER BY total DESC";
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los productos más vendidos.
     */
    public static function obtenerTopProductos(string $desde, string $hasta, int $limite = 10, ?int $idUsuario = null, string $tipoDocumento = 'todos'): array
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
                  AND lv.devuelta = 0 ";

        $params = [':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59'];
        if ($idUsuario) {
            $sql .= " AND v.id_usuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }
        if ($tipoDocumento === 'venta' || $tipoDocumento === 'abono') {
            $sql .= " AND v.tipo_documento = :tipoDoc";
            $params[':tipoDoc'] = $tipoDocumento;
        }

        $sql .= " AND v.fecha >= :desde AND v.fecha <= :hasta
                GROUP BY nombre_producto_limpio, codigo_producto
                ORDER BY unidades DESC, total_recaudado DESC
                LIMIT :limite";

        $sql = str_replace(':limite', (int)$limite, $sql);
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el desglose de márgenes (Ingreso vs Coste).
     */
    public static function obtenerMargenesDetallados(string $desde, string $hasta, ?int $idUsuario = null, string $tipoDocumento = 'todos', string $agrupacion = 'dia'): array
    {
        $selectFecha = "DATE(v.fecha) as fecha";
        $groupBy = "DATE(v.fecha)";

        if ($agrupacion === 'mes') {
            $selectFecha = "DATE_FORMAT(v.fecha, '%Y-%m-01') as fecha";
            $groupBy = "DATE_FORMAT(v.fecha, '%Y-%m')";
        } elseif ($agrupacion === 'año') {
            $selectFecha = "DATE_FORMAT(v.fecha, '%Y-01-01') as fecha";
            $groupBy = "YEAR(v.fecha)";
        }

        $sql = "SELECT $selectFecha,
                       SUM(lv.total_linea) as ingresos,
                       SUM(lv.precio_coste_unitario * lv.cantidad) as costes,
                       SUM(lv.total_linea - (lv.precio_coste_unitario * lv.cantidad)) as beneficio
                FROM lineas_venta lv
                JOIN ventas v ON lv.id_venta = v.id
                WHERE v.estado = 'completada' AND v.metodo_pago != 'financiado' AND lv.devuelta = 0 ";

        $params = [':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59'];
        if ($idUsuario) {
            $sql .= " AND v.id_usuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }
        if ($tipoDocumento === 'venta' || $tipoDocumento === 'abono') {
            $sql .= " AND v.tipo_documento = :tipoDoc";
            $params[':tipoDoc'] = $tipoDocumento;
        }

        $sql .= " AND v.fecha >= :desde AND v.fecha <= :hasta
                GROUP BY $groupBy
                ORDER BY fecha ASC";
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el ranking completo de productos, incluyendo los que no han tenido ventas.
     */
    public static function obtenerRankingCompletoProductos(string $desde, string $hasta, int $limit = 100, int $offset = 0, ?int $idUsuario = null, string $tipoDocumento = 'todos'): array
    {
        $params = [':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59'];
        $whereSub = "WHERE v.estado = 'completada' AND v.metodo_pago != 'financiado' AND lv.devuelta = 0 ";
        
        if ($idUsuario) {
            $whereSub .= " AND v.id_usuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }
        if ($tipoDocumento === 'venta' || $tipoDocumento === 'abono') {
            $whereSub .= " AND v.tipo_documento = :tipoDoc";
            $params[':tipoDoc'] = $tipoDocumento;
        }
        $whereSub .= " AND v.fecha >= :desde AND v.fecha <= :hasta";

        $sql = "SELECT 
                    p.id AS id_producto,
                    TRIM(SUBSTRING_INDEX(p.nombre, '(', 1)) AS nombre_producto_limpio,
                    p.referencia AS codigo_producto,
                    p.categoria,
                    COALESCE(sales.unidades, 0) AS unidades,
                    COALESCE(sales.total_recaudado, 0) AS total_recaudado
                FROM productos p
                LEFT JOIN (
                    SELECT lv.id_producto, SUM(lv.cantidad) AS unidades, SUM(lv.total_linea) AS total_recaudado
                    FROM lineas_venta lv
                    INNER JOIN ventas v ON lv.id_venta = v.id
                    $whereSub
                    GROUP BY lv.id_producto
                ) AS sales ON p.id = sales.id_producto
                ORDER BY unidades DESC, total_recaudado DESC, p.id ASC
                LIMIT :limit OFFSET :offset";

        $limitVal = (int)$limit;
        $offsetVal = (int)$offset;
        $sql = str_replace([':limit', ':offset'], [$limitVal, $offsetVal], $sql);

        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Obtiene el desglose de IVA recaudado por tipo.
     */
    public static function obtenerDesgloseIVA(string $desde, string $hasta, ?int $idUsuario = null, string $tipoDocumento = 'todos'): array
    {
        $sql = "SELECT lv.iva_aplicado as porcentaje, SUM(lv.total_linea * (lv.iva_aplicado / (100 + lv.iva_aplicado))) as cuota, SUM(lv.total_linea) as total
                FROM lineas_venta lv
                INNER JOIN ventas v ON lv.id_venta = v.id
                WHERE v.estado = 'completada' AND v.metodo_pago != 'financiado' AND lv.devuelta = 0 ";

        $params = [':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59'];
        if ($idUsuario) {
            $sql .= " AND v.id_usuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }
        if ($tipoDocumento === 'venta' || $tipoDocumento === 'abono') {
            $sql .= " AND v.tipo_documento = :tipoDoc";
            $params[':tipoDoc'] = $tipoDocumento;
        }

        $sql .= " AND v.fecha >= :desde AND v.fecha <= :hasta
                GROUP BY lv.iva_aplicado
                ORDER BY lv.iva_aplicado DESC";
        $q = DBPDO::ejecutarConsulta($sql, $params);
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

    /**
     * Crea un ticket de abono (factura rectificativa) independiente vinculado a la venta original.
     * No modifica el ticket de origen. El abono tiene importes negativos.
     *
     * @param int    $idVentaOrigen  ID de la venta a la que se asocia el abono
     * @param array  $lineasDevolver Array de ['id_linea' => X, 'cantidad' => Y] a devolver
     * @param string $motivo         Motivo de la devolución / rectificación
     * @param string $metodoReembolso 'efectivo', 'vale', 'reemplazo'
     * @param int    $idUsuario      ID del usuario que procesa
     * @param bool   $reponerStock   Si se deben reponer productos al inventario
     * @return int  Número de ticket del abono generado
     */
    public static function crearAbono(int $idVentaOrigen, array $lineasDevolver, string $motivo, string $metodoReembolso, int $idUsuario, bool $reponerStock = true): int
    {
        $tipoRectificativa = 'S'; // Siempre Sustitución (S) en el flujo del TPV
        $tipoR_auto = 'R1'; // Por defecto: Devoluciones, roturas, etc. (Art. 80.Uno, Dos y Seis)

        $motivoLower = mb_strtolower($motivo);
        if (strpos($motivoLower, 'concurso') !== false) {
            $tipoR_auto = 'R2'; // Concurso de acreedores
        } elseif (strpos($motivoLower, 'incobrable') !== false) {
            $tipoR_auto = 'R3'; // Créditos incobrables
        } elseif (strpos($motivoLower, 'error') !== false || strpos($motivoLower, 'otro') !== false || strpos($motivoLower, 'datos') !== false) {
            $tipoR_auto = 'R4'; // Resto de causas (errores materiales, etc.)
        }

        // 1. Obtener datos de la venta original
        $sqlV = "SELECT * FROM ventas WHERE id = :id";
        $qV   = DBPDO::ejecutarConsulta($sqlV, [':id' => $idVentaOrigen]);
        $ventaOrigen = $qV->fetch(PDO::FETCH_ASSOC);
        if (!$ventaOrigen) throw new \Exception('Venta de origen no encontrada.');

        // 2. Calcular totales del abono a partir de las líneas a devolver
        $totalAbono     = 0;
        $lineasAbono    = []; // Enriquecidas con datos completos de la línea original

        // Calcular factor de escala original para prorratear descuentos globales en la devolución
        $origTotal    = abs((float)$ventaOrigen['total']);
        $origSubtotal = abs((float)$ventaOrigen['subtotal']);
        $factorEscala = ($origSubtotal > 0.01) ? ($origTotal / $origSubtotal) : 1;
        // El factor no debería ser superior a 1 si solo hubo descuentos, pero lo limitamos para seguridad
        if ($factorEscala > 1) $factorEscala = 1;

        foreach ($lineasDevolver as $item) {
            $idLinea  = (int)$item['id_linea'];
            $cantidad = (int)$item['cantidad'];

            $sqlL = "SELECT * FROM lineas_venta WHERE id = :id AND id_venta = :iv";
            $qL   = DBPDO::ejecutarConsulta($sqlL, [':id' => $idLinea, ':iv' => $idVentaOrigen]);
            $linea = $qL->fetch(PDO::FETCH_ASSOC);
            if (!$linea) continue;

            $cantidadMaxima = (int)$linea['cantidad'];
            if ($cantidad <= 0) $cantidad = $cantidadMaxima;
            if ($cantidad > $cantidadMaxima) $cantidad = $cantidadMaxima;

            // Precio nominal de la línea
            $precioUnitNominal = (float)$linea['precio_unitario'];
            $ivaRate           = (float)$linea['iva_aplicado'];
            
            // Aplicar el factor de escala de la venta original para obtener el precio efectivo
            $precioUnitEfectivo = round($precioUnitNominal * $factorEscala, 2);
            
            $totalLinea  = round($precioUnitEfectivo * $cantidad, 2);
            $base        = $totalLinea / (1 + $ivaRate / 100);
            $iva         = $totalLinea - $base;

            $totalBaseAbono += $base;
            $totalIvaAbono  += $iva;
            $totalAbono     += $totalLinea;

            $lineasAbono[] = [
                'linea_origen' => $linea,
                'cantidad'     => $cantidad,
                'total_linea'  => $totalLinea,
                'precio_unit_efectivo' => $precioUnitEfectivo
            ];
        }

        if (empty($lineasAbono) || $totalAbono <= 0) {
            throw new \Exception('No hay líneas válidas para el abono.');
        }

        $totalBaseAbono = round($totalBaseAbono, 2);
        $totalIvaAbono  = round($totalIvaAbono, 2);
        $totalAbono     = round($totalAbono, 2);

        // El número de ticket se obtendrá dentro de la transacción para asegurar atomicidad
        // $numTicket = self::obtenerSiguienteTicket();

        // 4. INSERT en ventas (importe NEGATIVO para el abono)
        $sqlAbono = "INSERT INTO ventas
            (numero_ticket, fecha, id_usuario, id_cliente, tipo_cliente, nombre_cliente, nif_cliente,
             metodo_pago, subtotal, descuento_pct, descuento_amt, descuento_label,
             base_imponible, iva_pct, iva_amt, total, efectivo_recibido,
             estado, pagado_a_cuenta, fecha_limite_pago, es_factura, comentarios, id_turno,
             puntos_ganados, puntos_canjeados, puntos_descuento_amt,
             tipo_documento, id_venta_origen)
        VALUES
            (:ticket, NOW(), :usuario, :cliente, :tipo, :nombre, :nif,
             :metodo, :subtotal, 0, 0, NULL,
             :base, 21.00, :iva, :total, 0,
             'completada', 0, NULL, :esFactura, :comentarios, :idTurno,
             0, 0, 0,
             'abono', :ventaOrigen)";

        require_once __DIR__ . '/CajaTurnoPDO.php';
        $turno   = CajaTurnoPDO::obtenerTurnoAbierto();
        $idTurno = $turno ? (int)$turno['id'] : null;

        $paramsAbono = [
            ':ticket'      => $numTicket,
            ':usuario'     => $idUsuario,
            ':cliente'     => $ventaOrigen['id_cliente'],
            ':tipo'        => $ventaOrigen['tipo_cliente'],
            ':nombre'      => $ventaOrigen['nombre_cliente'],
            ':nif'         => $ventaOrigen['nif_cliente'],
            ':metodo'      => $metodoReembolso === 'reemplazo' ? 'efectivo' : $metodoReembolso,
            ':subtotal'    => -$totalAbono,
            ':base'        => -$totalBaseAbono,
            ':iva'         => -$totalIvaAbono,
            ':total'       => -$totalAbono,
            ':esFactura'   => (int)$ventaOrigen['es_factura'],
            ':comentarios' => 'Abono por devolución. Motivo: ' . mb_substr($motivo, 0, 200),
            ':idTurno'     => $idTurno,
            ':ventaOrigen' => $idVentaOrigen,
        ];

        $db = DBPDO::getPDO();
        $db->beginTransaction();

        try {
            $numTicket = self::obtenerSiguienteTicket($db);
            $paramsAbono[':ticket'] = $numTicket;

            DBPDO::ejecutarConsulta($sqlAbono, $paramsAbono);
            $idAbono = (int)$db->lastInsertId();

            // 5. Insertar líneas del abono (cantidades NEGATIVAS)
            foreach ($lineasAbono as $la) {
                $lo    = $la['linea_origen'];
                $qty   = $la['cantidad'];
                $total = $la['total_linea'];

                $sqlLinea = "INSERT INTO lineas_venta
                    (id_venta, id_producto, nombre_producto, codigo_producto,
                     precio_unitario, precio_base_snapshot, precio_coste_unitario,
                     iva_aplicado, cantidad, meses_garantia, total_linea,
                     devuelta, motivo_devolucion, fecha_devolucion, metodo_reembolso)
                VALUES
                    (:venta, :prod, :nombre, :codigo,
                     :precio, :base_snap, :coste,
                     :iva, :qty, :garantia, :total,
                     1, :motivo, NOW(), :metodo)";

                DBPDO::ejecutarConsulta($sqlLinea, [
                    ':venta'     => $idAbono,
                    ':prod'      => $lo['id_producto'],
                    ':nombre'    => $lo['nombre_producto'],
                    ':codigo'    => $lo['codigo_producto'],
                    ':precio'    => -$la['precio_unit_efectivo'],
                    ':base_snap' => -$la['precio_unit_efectivo'],
                    ':coste'     => -$lo['precio_coste_unitario'],
                    ':iva'       => $lo['iva_aplicado'],
                    ':qty'       => -$qty,
                    ':garantia'  => $lo['meses_garantia'],
                    ':total'     => -$total,
                    ':motivo'    => mb_substr($motivo, 0, 255),
                    ':metodo'    => $metodoReembolso,
                ]);

                // 6. Reponer stock del producto devuelto (Solo si se solicita)
                if ($reponerStock && $lo['id_producto']) {
                    $prod = ProductoPDO::obtenerProductoPorId((int)$lo['id_producto']);
                    if (!empty($prod['es_pack'])) {
                        $componentes = ProductoPDO::obtenerComponentesPack((int)$lo['id_producto']);
                        foreach ($componentes as $comp) {
                            $qtyComp = $qty * (int)$comp['cantidad'];
                            ProductoPDO::aumentarStock((int)$comp['id_producto'], $qtyComp);
                            MovimientoStockPDO::registrarMovimiento((int)$comp['id_producto'], 'devolucion', $qtyComp, $idUsuario, "Abono #$numTicket (devolución Pack)", $db);
                        }
                    } else {
                        ProductoPDO::aumentarStock((int)$lo['id_producto'], $qty);
                        MovimientoStockPDO::registrarMovimiento((int)$lo['id_producto'], 'devolucion', $qty, $idUsuario, "Abono #$numTicket (devolución)", $db);
                    }
                }
            }

            // 7. Gestionar REEMBOLSO
            if ($metodoReembolso === 'vale') {
                require_once __DIR__ . '/ValePDO.php';
                $idCliente = $ventaOrigen['id_cliente'] ? (int)$ventaOrigen['id_cliente'] : null;
                $codigoVale = ValePDO::crearVale($idCliente, $idVentaOrigen, $totalAbono);
                require_once __DIR__ . '/LogPDO.php';
                LogPDO::addLog('GENERACION_VALE', "Vale generado (#$codigoVale) por $totalAbono€ - Abono #$numTicket");
            } elseif ($metodoReembolso === 'efectivo') {
                require_once __DIR__ . '/CajaTurnoPDO.php';
                $efectivoEnCaja = CajaTurnoPDO::obtenerEfectivoActual();
                if ($efectivoEnCaja < $totalAbono - 0.009) {
                    throw new \Exception("No hay suficiente efectivo en el cajón para el reembolso (" . number_format($totalAbono, 2, ',', '.') . "€). Disponible: " . number_format($efectivoEnCaja, 2, ',', '.') . "€");
                }

                if ($turno) {
                    CajaTurnoPDO::registrarRetiro(
                        (int)$turno['id'],
                        $idUsuario,
                        $totalAbono,
                        "Reembolso efectivo - Abono #$numTicket"
                    );
                }
            }
            // 'reemplazo': solo se repone stock (ya hecho arriba); no hay reembolso dinerario

            // 8. Actualizar estado de la venta original
            // Calcular el total ya abonado para esta venta
            $sqlSumAbonos = "SELECT COALESCE(SUM(ABS(total)), 0) AS total_abonado
                             FROM ventas
                             WHERE id_venta_origen = :idv AND tipo_documento = 'abono'";
            $qSum = DBPDO::ejecutarConsulta($sqlSumAbonos, [':idv' => $idVentaOrigen]);
            $rowSum = $qSum->fetch(PDO::FETCH_ASSOC);
            $totalAbonado = (float)($rowSum['total_abonado'] ?? 0);
            $totalVentaOriginal = abs((float)$ventaOrigen['total']);

            if ($totalAbonado >= $totalVentaOriginal - 0.01) {
                $nuevoEstado = 'devuelta';
            } else {
                $nuevoEstado = 'parcialmente_devuelta';
            }
            DBPDO::ejecutarConsulta(
                "UPDATE ventas SET estado = :estado WHERE id = :id",
                [':estado' => $nuevoEstado, ':id' => $idVentaOrigen]
            );

            // [VERIFACTU] Encadenamiento y Huella (Abono)
            $fechaFormatAb = date('d-m-Y');

            // Determinar el tipo R correcto:
            //   - Si la original era F2 (ticket simplificado, es_factura=0) → siempre R5 (regla obligatoria AEAT)
            //   - De lo contrario, usar el tipo mapeado automáticamente por el motivo
            $esFacturaOrigen = (bool)$ventaOrigen['es_factura'];
            if (!$esFacturaOrigen) {
                $tipoAb = 'R5'; // Obligatorio para F2 independientemente del motivo
            } else {
                $tipoAb = $tipoR_auto;
            }

            $numFormatedAb = self::formatTicketNumber($numTicket, time(), $ventaOrigen['es_factura'], 'abono');

            // Referencia a la factura/ticket original rectificado
            $serieOrigenAb = self::formatTicketNumber(
                $ventaOrigen['numero_ticket'],
                $ventaOrigen['fecha'],
                $ventaOrigen['es_factura'],
                'venta'
            );
            $fechaOrigenAb   = date('d-m-Y', strtotime($ventaOrigen['fecha']));
            // Importes de la venta original (para ImporteRectificacion en el XML)
            $baseOrigenAb    = self::normalizarValorHash(abs((float)$ventaOrigen['base_imponible']));
            $cuotaOrigenAb   = self::normalizarValorHash(abs((float)$ventaOrigen['iva_amt']));

            self::procesarVeriFactu(
                $db, $idAbono, $numFormatedAb, $tipoAb,
                (float)$totalAbono, (float)$totalIvaAbono, $fechaFormatAb,
                $ventaOrigen['nif_cliente'] ?? '',
                $ventaOrigen['nombre_cliente'] ?? '',
                $serieOrigenAb, $fechaOrigenAb,
                $baseOrigenAb, $cuotaOrigenAb,
                $tipoRectificativa
            );

            $db->commit();

            // [NUEVO] Envío VeriFactu FUERA de la transacción para evitar conflictos (Abono)
            try {
                require_once __DIR__ . '/AeatQueueService.php';
                (new AeatQueueService())->enviarEspecifico((int)$idAbono);
            } catch (\Exception $eVf) {
                error_log("Error en envío inmediato VeriFactu (Abono): " . $eVf->getMessage());
            }

            return $numTicket;

        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (\Throwable $eRollback) {
                    error_log("Error al hacer rollBack en crearAbono: " . $eRollback->getMessage());
                }
            }
            error_log("Error original en crearAbono: " . $e->getMessage() . " \nTraza: " . $e->getTraceAsString());
            throw $e;
        }
    }
    /**
     * Verifica si los índices de rendimiento están creados.
     * @return bool True si todo está optimizado.
     */
    public static function estanIndicesListos(): bool
    {
        try {
            $q1 = DBPDO::ejecutarConsulta("SHOW INDEX FROM ventas WHERE Key_name = 'idx_ventas_fecha'");
            $q2 = DBPDO::ejecutarConsulta("SHOW INDEX FROM ventas WHERE Key_name = 'idx_ventas_perf'");
            return ($q1->rowCount() > 0 && $q2->rowCount() > 0);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * [REPRODUCTO] Método mantenido para compatibilidad de firma, pero los índices
     * ahora se gestionan mediante scripts de migración fuera del runtime.
     */
    public static function optimizarIndices(): bool
    {
        return true;
    }

    /**
     * ============================================================
     * MÉTODOS VERIFACTU (RD 1007/2023)
     * ============================================================
     */

    /**
     * Obtiene el hash_actual del último registro de venta para el encadenamiento VeriFactu.
     * @param PDO|null $db Conexión opcional (para usar dentro de transacciones)
     */
    public static function obtenerUltimoHash(?PDO $db = null, int $idVenta = 0): ?string
    {
        $db = $db ?? DBPDO::getPDO();
        $where = "WHERE hash_actual IS NOT NULL";
        $params = [];
        if ($idVenta > 0) {
            $where .= " AND id < :id";
            $params[':id'] = $idVenta;
        }
        $sql = "SELECT hash_actual FROM ventas $where ORDER BY id DESC LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['hash_actual'] : null;
    }

    /**
     * Genera la cadena normalizada y el hash SHA256 según el Reglamento VeriFactu.
     * @param array $datos Cabecera de la factura/evento
     * @param string|null $hashAnterior Huella del registro previo
     * @return string Hash SHA256 en mayúsculas
     */
    public static function generarHashVeriFactu(array $datos, ?string $hashAnterior = null): string
    {
        // 1. Recopilación de campos (Tipo: Alta de factura)
        // Estructura oficial: IDEmisorFactura, NumSerieFactura, FechaExpedicionFactura, TipoFactura, CuotaTotal, ImporteTotal, Huella, FechaHoraHusoGenRegistro
        
        $campos = [
            'IDEmisorFactura'         => $datos['nif_emisor'] ?? '',
            'NumSerieFactura'         => $datos['numero_serie'] ?? '',
            'FechaExpedicionFactura'  => date('d-m-Y', strtotime($datos['fecha_expedicion'] ?? date('Y-m-d'))),
            'TipoFactura'             => $datos['tipo_factura'] ?? 'F1',
            'CuotaTotal'              => self::normalizarValorHash($datos['cuota_total'] ?? 0),
            'ImporteTotal'            => self::normalizarValorHash($datos['importe_total'] ?? 0),
            'Huella'                  => $hashAnterior ?? str_repeat('0', 64),
            'FechaHoraHusoGenRegistro'=> $datos['fecha_hora_gen'] ?? date('Y-m-d\TH:i:sP')
        ];

        // 2. Concatenación con formato nombreCampo1=valorCampo1&nombreCampo2=valorCampo2...
        $cadena = "";
        foreach ($campos as $nombre => $valor) {
            if ($cadena !== "") $cadena .= "&";
            $cadena .= $nombre . "=" . $valor;
        }

        // 3. Cálculo del Hash (SHA256)
        $hashResult = strtoupper(hash('sha256', $cadena));
        
        // Log detallado para diagnóstico de Error 2000 (Huella incorrecta)
        error_log("VeriFactu Hash String: " . $cadena);
        error_log("VeriFactu Hash Result: " . $hashResult);
        
        return $hashResult;
    }

    /**
     * Procesa el encadenamiento VeriFactu para un registro de venta/abono recién insertado.
     * @param PDO $db Conexión activa
     * @param int $idVenta ID del registro en la tabla ventas
     * @param string $numeroSerie Número de ticket/factura formateado
     * @param string $tipoFactura Clave AEAT (F1, R1, F2, R5...)
     * @param float $total Importe total
     * @param float $iva Cuota total IVA
     * @param string $nifCliente NIF del destinatario (para facturas completas)
     * @param string $nombreCliente Nombre/Razón del destinatario
     * @param string $facturaOrigenSerie Serie de la factura rectificada (para abonos)
     * @param string $facturaOrigenFecha Fecha de la factura rectificada DD-MM-YYYY
     * @param string $baseOrigenRectificada Base imponible de la factura original
     * @param string $cuotaOrigenRectificada Cuota IVA de la factura original
     */
    private static function procesarVeriFactu(
        PDO $db, int $idVenta, string $numeroSerie, string $tipoFactura,
        float $total, float $iva, string $fechaExpedicion,
        string $nifCliente = '', string $nombreCliente = 'Cliente General',
        string $facturaOrigenSerie = '', string $facturaOrigenFecha = '',
        string $baseOrigenRectificada = '', string $cuotaOrigenRectificada = '',
        string $tipoRectificativa = 'S'
    ): void
    {
        // 0. Asegurar formato de serie si viene solo el número (limpieza de inconsistencias)
        if (is_numeric($numeroSerie) || !str_contains($numeroSerie, '-')) {
            // Re-formateamos para asegurar consistencia con el hash que espera AEAT
            $esFactura = ($tipoFactura[0] === 'F') ? 1 : 0;
            $tipoInterno = ($tipoFactura[0] === 'R') ? 'abono' : 'venta';
            $numeroSerie = self::formatTicketNumber((int)$numeroSerie, $fechaExpedicion, $esFactura, $tipoInterno);
        }

        // Ajuste automático: Si no hay NIF de cliente, forzamos factura simplificada (Ticket) 
        // para cumplir con la normativa VeriFactu (Error 1189).
        if (empty($nifCliente)) {
            if ($tipoFactura === 'F1') $tipoFactura = 'F2';
            if ($tipoFactura === 'R1') $tipoFactura = 'R5';
        }

        require_once __DIR__ . '/ConfiguracionPDO.php';
        $nifEmisor = defined('EMPRESA_CIF') ? EMPRESA_CIF : (ConfiguracionPDO::obtenerValor('empresa_nif') ?? '00000000T');

        // 1. Obtener hash anterior para el encadenamiento
        $hashAnterior = self::obtenerUltimoHash($db, $idVenta);

        // 2. Normalizar valores para el Hash y el XML (Sincronización total)
        $normCuota   = self::normalizarValorHash($iva);
        $normImporte = self::normalizarValorHash($total);
        $normBase    = self::normalizarValorHash($total - $iva);
        $normHashAnt = $hashAnterior ?? str_repeat('0', 64); // No normalizar la huella!

        $fechaHoraGen = date('Y-m-d\TH:i:sP');
        $datosHash = [
            'nif_emisor'       => $nifEmisor,
            'numero_serie'     => $numeroSerie,
            'fecha_expedicion' => $fechaExpedicion,
            'tipo_factura'     => $tipoFactura,
            'cuota_total'      => $normCuota,
            'importe_total'    => $normImporte,
            'fecha_hora_gen'   => $fechaHoraGen
        ];
        
        $hashActual = self::generarHashVeriFactu($datosHash, $normHashAnt);

        // 3. Generar XML VeriFactu
        require_once __DIR__ . '/VeriFactuService.php';
        $vfService = new VeriFactuService();
        
        // Necesitamos datos del registro anterior para el XML
        $datosUltimo = self::obtenerDatosUltimoRegistro($db);

        $resultadoXML = $vfService->procesarAlta([
            'numero_serie'              => $numeroSerie,
            'fecha_expedicion'          => $fechaExpedicion,
            'tipo_factura'              => $tipoFactura,
            'tipo_rectificativa'        => $tipoRectificativa,
            'base_imponible'            => $normBase,
            'cuota_total'              => $normCuota,
            'importe_total'            => $normImporte,
            'hash_actual'              => $hashActual,
            'hash_anterior'            => $normHashAnt,
            'fecha_hora_gen'           => $fechaHoraGen,
            'destinatario_nif'         => $nifCliente,
            'destinatario_nombre'      => $nombreCliente,
            'factura_rectificada_serie' => $facturaOrigenSerie,
            'factura_rectificada_fecha' => $facturaOrigenFecha,
            'base_rectificada'          => $baseOrigenRectificada,
            'cuota_rectificada'         => $cuotaOrigenRectificada,
            'serie_anterior'           => $datosUltimo['serie'] ?? '',
            'fecha_anterior'           => $datosUltimo['fecha'] ?? ''
        ]);

        // 4. Encolar para envío a la AEAT (Asíncrono)
        require_once __DIR__ . '/AeatQueueService.php';
        $queueService = new AeatQueueService();
        if (isset($resultadoXML['ok']) && $resultadoXML['ok']) {
            $queueService->encolar($idVenta, $resultadoXML['path']);
        } else {
            // Guardar el error en la cola o en log si falló la creación del XML
            $errorMsg = $resultadoXML['error'] ?? 'Error desconocido al generar XML';
            error_log("VeriFactu Error: $errorMsg");
            // Opcionalmente encolar con estado 'error'
            $queueService->encolarError($idVenta, "Error generación XML: $errorMsg");
        }

        // 5. Construir URL del código QR para cotejo AEAT
        $qrBaseUrl = defined('VERIFACTU_URL_QR_PRUEBAS') ? VERIFACTU_URL_QR_PRUEBAS : 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR';
        $qrParams = [
            'nif'      => $nifEmisor,
            'numserie' => $numeroSerie,
            'fecha'    => date('d-m-Y', strtotime($fechaExpedicion)),
            // La AEAT siempre espera valor absoluto (positivo) en el campo importe del QR
            'importe'  => number_format(abs($total), 2, '.', ''),
            'hash'     => strtoupper(substr($hashActual, 0, 8))
        ];
        $qrUrl = $qrBaseUrl . "?" . http_build_query($qrParams, '', '&', PHP_QUERY_RFC3986);

        // 6. Persistir en la base de datos
        $xmlPath = $resultadoXML['ok'] ? $resultadoXML['path'] : null;
        $estadoEnvio = ($resultadoXML['ok']) ? 'pendiente' : 'error';

        $sql = "UPDATE ventas 
                SET hash_actual = :hashActual, 
                    hash_anterior = :hashAnterior,
                    fecha_hora_gen_fiscal = :fechaHora,
                    estado_envio_aeat = :estado,
                    codigo_qr = :xmlPath,
                    qr_verifactu = :qrUrl
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':hashActual'   => $hashActual,
            ':hashAnterior' => $hashAnterior,
            ':fechaHora'    => $fechaHoraGen,
            ':estado'       => $estadoEnvio,
            ':xmlPath'      => $xmlPath,
            ':qrUrl'        => $qrUrl,
            ':id'           => $idVenta
        ]);
    }

    /**
     * Realiza una ANULACIÓN técnica del registro en VeriFactu.
     * Se usa cuando un registro se envió por error y debe ser invalidado (no es un abono comercial).
     */
    public static function anularRegistroVerifactu(int $idVenta, string $motivo): array
    {
        $db = DBPDO::getPDO();
        $venta = self::obtenerVentaPorId($idVenta);
        if (!$venta) return ['ok' => false, 'error' => 'Venta no encontrada'];

        // 1. Obtener hash anterior para encadenar la anulación
        $hashAnterior = self::obtenerUltimoHash($db);
        $fechaHoraGen = date('Y-m-d\TH:i:sP');

        require_once __DIR__ . '/ConfiguracionPDO.php';
        $nifEmisor = defined('EMPRESA_CIF') ? EMPRESA_CIF : (ConfiguracionPDO::obtenerValor('empresa_nif') ?? '00000000T');

        // 2. Generar huella de anulación (Campos mínimos según normativa)
        $datosHash = [
            'nif_emisor'       => $nifEmisor,
            'numero_serie'     => self::formatTicketNumber($venta['numero_ticket'], $venta['fecha'], $venta['es_factura']),
            'fecha_expedicion' => date('d-m-Y', strtotime($venta['fecha'])),
            'tipo_factura'     => 'ANUL', // Identificador interno para el log
            'cuota_total'      => '0.00',
            'importe_total'    => '0.00',
            'fecha_hora_gen'   => $fechaHoraGen
        ];
        $hashActual = self::generarHashVeriFactu($datosHash, $hashAnterior);

        // 3. Generar XML RegistroAnulacion
        require_once __DIR__ . '/VeriFactuService.php';
        $vfService = new VeriFactuService();
        $datosUltimo = self::obtenerDatosUltimoRegistro($db);

        $resultadoXML = $vfService->procesarAnulacion([
            'numero_serie'     => $datosHash['numero_serie'],
            'fecha_expedicion' => $datosHash['fecha_expedicion'],
            'hash_actual'      => $hashActual,
            'hash_anterior'    => $hashAnterior,
            'fecha_hora_gen'   => $fechaHoraGen,
            'serie_anterior'   => $datosUltimo['serie'] ?? '',
            'fecha_anterior'   => $datosUltimo['fecha'] ?? ''
        ]);

        if ($resultadoXML['ok']) {
            // Actualizar estado en la venta
            DBPDO::ejecutarConsulta(
                "UPDATE ventas SET estado_envio_aeat = 'anulado_pendiente', hash_actual = :h WHERE id = :id",
                [':h' => $hashActual, ':id' => $idVenta]
            );

            // Encolar XML de anulación
            require_once __DIR__ . '/AeatQueueService.php';
            (new AeatQueueService())->encolar($idVenta, $resultadoXML['path']);
        }

        return $resultadoXML;
    }

    /**
     * Normaliza los valores para la huella según AEAT:
     * - Trim de espacios.
     * - Numéricos con 1 o 2 decimales, ignorando ceros a la derecha irrelevantes.
     */
    private static function normalizarValorHash($valor): string
    {
        $valor = trim((string)$valor);
        
        // Si es numérico y no parece una huella (64 chars) ni un NIF/Serie (alfanumérico largo), forzamos formato estándar. 
        if (is_numeric($valor) && strlen($valor) < 20 && !preg_match('/^[A-Z]{1}/i', $valor)) {
            $f = (float)$valor;
            // Formateamos siempre a 2 decimales para coincidir con el XML y la AEAT
            $valor = number_format($f, 2, '.', '');
        }
        
        return $valor;
    }

    /**
     * Obtiene los datos de serie y fecha del último registro fiscal para el encadenamiento XML.
     */
    public static function obtenerDatosUltimoRegistro(?PDO $db = null): array
    {
        $db = $db ?? DBPDO::getPDO();
        $sql = "SELECT numero_ticket, fecha, es_factura, hash_actual 
                FROM ventas 
                WHERE hash_actual IS NOT NULL 
                ORDER BY id DESC LIMIT 1";
        $stmt = $db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) return [];

        $numFormated = self::formatTicketNumber($row['numero_ticket'], $row['fecha'], $row['es_factura']);
        $fechaFormat = date('d-m-Y', strtotime($row['fecha']));

        return [
            'serie' => $numFormated,
            'fecha' => $fechaFormat,
            'hash'  => $row['hash_actual']
        ];
    }
}
