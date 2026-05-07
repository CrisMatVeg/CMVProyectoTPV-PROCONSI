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
        $datePart = date('dmY', $time); // d=dia con ceros, m=mes sin ceros, Y=año 4 digitos
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

        // 3. [MODIFICADO] Según requerimiento del usuario: los descuentos/promos NO afectan a la base imponible ni al IVA.
        // Se mantienen los valores originales (brutos) para base e IVA.
        $base   = round($totalBase, 2);
        $ivaAmt = round($totalIva, 2);
        
        // El Total de la venta es el neto tras aplicar descuentos
        $total  = $totalNetoCalculado;

        if ($totalReal <= 0) {
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
        $idTypeAEAT       = $datos['aeatIdType'] ?? null;
        $codigoPaisAEAT   = $datos['aeatCodigoPais'] ?? null;
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

        // Validar que los pagos mixtos cubren el total
        if ($metodoPago === 'mixto' && !empty($datos['pagos'])) {
            $sumaPagos = array_sum(array_map(fn($p) => (float)($p['importe'] ?? 0), $datos['pagos']));
            if ($sumaPagos < $total - 0.02) {
                throw new \Exception(
                    "Pagos mixtos insuficientes: la suma (" . number_format($sumaPagos, 2, ',', '.') .
                    "€) no cubre el total (" . number_format($total, 2, ',', '.') . "€)."
                );
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
         (numero_ticket, fecha, id_usuario, id_cliente, tipo_cliente, nombre_cliente, nif_cliente, aeat_id_type, aeat_codigo_pais, metodo_pago,
          subtotal, descuento_pct, descuento_amt, descuento_label, base_imponible, iva_amt, total, efectivo_recibido,
          estado, pagado_a_cuenta, fecha_limite_pago, es_factura, comentarios, id_turno,
          puntos_ganados, puntos_canjeados, puntos_descuento_amt)
        VALUES (:ticket, NOW(), :usuario, :cliente, :tipo, :nombre, :nif, :aeatIdType, :aeatCodigoPais, :metodo,
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
            ':aeatIdType'    => $idTypeAEAT,
            ':aeatCodigoPais' => $codigoPaisAEAT,
            ':metodo'        => $metodoPago,
            ':subtotal'      => $totalReal,
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

                // PriceEngine es la fuente autoritativa de precio y descuentos para productos reales.
                // Los comodines (id <= 0) usan el precio introducido manualmente.
                if ($idProducto > 0) {
                    try {
                        require_once __DIR__ . '/PriceEngine.php';
                        $breakdown          = PriceEngine::calculate($idProducto, $idCliente, $qty, $datos['codigoCupon'] ?? null);
                        $precioUnit         = round($breakdown['precio_unitario_final'], 2);
                        $totalLinea         = round($precioUnit * $qty, 2);
                        $precioBaseSnapshot = $breakdown['precio_base'];
                        $descuentosLog      = $breakdown['descuentos'];
                    } catch (\Throwable $e) {
                        // Fallback: mantener precio del frontend si PriceEngine falla
                        $precioBaseSnapshot = isset($linea['basePriceSnapshot']) ? (float)$linea['basePriceSnapshot'] : $precioUnit;
                        $descuentosLog      = isset($linea['descuentos']) ? $linea['descuentos'] : [];
                    }
                } else {
                    // Comodín: precio introducido manualmente, sin tarifas
                    $precioBaseSnapshot = $precioUnit;
                    $descuentosLog      = [];
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
            $fechaFormat = date('d-m-Y'); 
            $tipoFC      = ($paramsVenta[':esFactura'] ? 'F1' : 'F2');
            $numFormated = self::formatTicketNumber($numTicket, time(), $paramsVenta[':esFactura'], 'venta');
            
            self::procesarVeriFactu(
                $db, $idVenta, $numFormated, $tipoFC, 
                (float)$total, (float)$ivaAmt, $fechaFormat, 
                $nifCliente ?? '', $nombreCliente ?? 'Cliente General',
                '', '', '', '', 'S', null,
                $idTypeAEAT, $codigoPaisAEAT
            );

            $db->commit();

            // Intento de envío — Respeta el Natural Batching (60s)
            try {
                require_once __DIR__ . '/AeatQueueService.php';
                (new AeatQueueService())->procesarCola();
            } catch (\Exception $eVf) {
                error_log("Error al disparar la cola VeriFactu: " . $eVf->getMessage());
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

    public static function obtenerVentaPorTicket(int $numTicket, ?PDO $db = null): ?array
    {
        $sqlVenta = "SELECT v.*, u.nombre AS nombre_cajero, c.puntos AS puntos_cliente_actual, c.email AS cliente_email
                     FROM ventas v
                     LEFT JOIN usuarios u ON v.id_usuario = u.id
                     LEFT JOIN clientes c ON v.id_cliente = c.id
                     WHERE v.numero_ticket = :t";
        $q = DBPDO::ejecutarConsulta($sqlVenta, [':t' => $numTicket], $db);
        $venta = $q->fetch(PDO::FETCH_ASSOC);
        if (!$venta) return null;

        $sqlLineas = "SELECT lv.*, TRIM(SUBSTRING_INDEX(lv.nombre_producto, '(', 1)) as nombre_producto
                      FROM lineas_venta lv
                      WHERE lv.id_venta = :v 
                      ORDER BY lv.id ASC";
        $qL = DBPDO::ejecutarConsulta($sqlLineas, [':v' => $venta['id']], $db);
        $lineasRaw = $qL->fetchAll(PDO::FETCH_ASSOC);

        // Map already returned quantities from Abonos without mutating original lines
        $devueltosPorProducto = [];
        if (($venta['tipo_documento'] ?? 'venta') === 'venta') {
            $sqlTodosAbonos = "SELECT lv.id_producto, COALESCE(SUM(ABS(lv.cantidad)), 0) as devueltos
                               FROM lineas_venta lv
                               JOIN ventas v ON lv.id_venta = v.id
                               WHERE v.id_venta_origen = :idv AND v.tipo_documento = 'abono'
                               GROUP BY lv.id_producto";
            $qAb = DBPDO::ejecutarConsulta($sqlTodosAbonos, [':idv' => $venta['id']], $db);
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
            
            $qD = DBPDO::ejecutarConsulta($sqlD, array_values($lineIds), $db);
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
            $qA = DBPDO::ejecutarConsulta($sqlAbonos, [':idv' => $venta['id']], $db);
            $venta['abonos'] = $qA->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $venta['abonos'] = [];
        }

        return $venta;
    }
    public static function obtenerVentaPorId(int $id, ?PDO $db = null): ?array
    {
        $db = $db ?? DBPDO::getPDO();
        $sql = "SELECT numero_ticket FROM ventas WHERE id = :id";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $id], $db);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return self::obtenerVentaPorTicket((int)$row['numero_ticket'], $db);
    }

    public static function buscarVentas(string $desde, string $hasta, ?int $idUsuario = null, ?string $numTicket = null, string $tipoDocumento = 'todos', int $limit = 50, int $offset = 0, string $ordenPor = 'fecha', string $ordenDir = 'ASC', bool $soloIncidencias = false): array
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
        } elseif (!$soloIncidencias) {
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

        if ($soloIncidencias) {
            $whereClause .= " AND v_sub.estado_envio_aeat IN ('error_critico', 'subsanacion_pendiente')";
        }

        // Técnica: Late Row Lookup. Primero obtenemos solo los IDs de forma eficiente.
        $innerJoin = ($ordenPor === 'nombre_cajero') ? "JOIN usuarios u_sub ON v_sub.id_usuario = u_sub.id" : "";
        
        $sql = "SELECT v.*, u.nombre as nombre_cajero, vo.numero_ticket as numero_ticket_origen, c_aeat.ultimo_error as aeat_error, v.id as id_venta_proconsis, v.id as id
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
                LEFT JOIN cola_envios c_aeat ON c_aeat.id = (
                    SELECT MAX(id) FROM cola_envios WHERE id_venta = v.id
                )
                ORDER BY $campoFinalOrder $ordenDir";
        
        // PDO no permite bindParam en LIMIT/OFFSET en algunas versiones si no se emula,
        // así que los reemplazamos directamente tras castearlos a int para total seguridad.
        $sql = str_replace([':limit', ':offset'], [(int)$limit, (int)$offset], $sql);
        
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function contarVentas(string $desde, string $hasta, ?int $idUsuario = null, ?string $numTicket = null, string $tipoDocumento = 'todos', bool $soloIncidencias = false): int
    {
        $sql = "SELECT COUNT(*) FROM ventas v WHERE 1=1";
        $params = [];

        if ($numTicket) {
            $sql .= " AND v.numero_ticket LIKE :ticket";
            $params[':ticket'] = '%' . $numTicket . '%';
        } elseif (!$soloIncidencias) {
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

        if ($soloIncidencias) {
            $sql .= " AND v.estado_envio_aeat IN ('error_critico', 'subsanacion_pendiente')";
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

        // [VERIFACTU] Si ya tiene huella, no podemos convertirla directamente 
        // porque cambiaría el ID del registro (prefijo T a F) y rompería la cadena.
        $sqlCheck = "SELECT hash_actual FROM ventas WHERE id = :id";
        $stmtCheck = DBPDO::ejecutarConsulta($sqlCheck, [':id' => $idVenta]);
        if ($stmtCheck->fetchColumn()) {
            throw new Exception("Inalterabilidad VeriFactu: No se puede convertir un ticket ya emitido en factura. Debe anularlo y emitir una nueva venta o usar factura rectificativa.");
        }

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

    public static function subsanarVenta(int $idVenta, string $nombre, string $nif, bool $rechazoPrevio = false): bool
    {
        $db = DBPDO::getPDO();
        $db->beginTransaction();
        try {
            // 0. Obtener IDENTIFICADOR ORIGINAL antes de cualquier cambio
            // El ID (Serie+Numero+Fecha) de una factura para la AEAT es INMUTABLE.
            $sqlOrig = "SELECT numero_ticket, fecha, es_factura, nif_cliente, tipo_documento, estado_envio_aeat, hash_anterior FROM ventas WHERE id = :id";
            $vOrig = DBPDO::ejecutarConsulta($sqlOrig, [':id' => $idVenta], $db)->fetch();
            if (!$vOrig) throw new Exception("Venta no encontrada.");

            // error_critico (rechazado)    → Alta por Rechazo (Subsanacion=S, RechazoPrevio=X)
            // subsanacion_pendiente (aceptado con errores) → Alta de Subsanación (Subsanacion=S, RechazoPrevio=N)
            $esRechazo            = ($vOrig['estado_envio_aeat'] === 'error_critico');
            $esAceptadoConErrores = ($vOrig['estado_envio_aeat'] === 'subsanacion_pendiente');
            
            $flagSubsanacion      = 'S'; // Se está subsanando
            $flagRechazoPrevio    = $esRechazo ? 'S' : 'N'; // S activará el flag 'X' en VeriFactuService

            require_once __DIR__ . '/ConfiguracionPDO.php';
            $nifEmisor = defined('EMPRESA_CIF') ? EMPRESA_CIF : (ConfiguracionPDO::obtenerValor('empresa_nif') ?? '00000000T');

            // Usamos el identificador que ya conoce la AEAT (o el que se generó originalmente)
            $esFacturaOld = !empty($vOrig['es_factura']) && !empty($vOrig['nif_cliente']);
            $numeroSerieOriginal = self::formatTicketNumber($vOrig['numero_ticket'], $vOrig['fecha'], $esFacturaOld, $vOrig['tipo_documento']);

            // 1. Actualizar datos en la venta.
            // Solo convertir a factura (es_factura=1) si se aportan nombre y NIF,
            // o si ya era factura. Para tickets regenerados sin datos de cliente
            // (confirmarRegeneracion) no hay que alterar el tipo de documento.
            $hayDatosCliente = ($nombre !== '' || $nif !== '');
            if ($hayDatosCliente || !empty($vOrig['es_factura'])) {
                DBPDO::ejecutarConsulta(
                    "UPDATE ventas SET nombre_cliente = :nombre, nif_cliente = :nif, es_factura = 1 WHERE id = :id",
                    [':id' => $idVenta, ':nombre' => $nombre, ':nif' => $nif],
                    $db
                );
            }

            // 2. Obtener datos para VeriFactu (con los importes y demás)
            $datosVf = self::getVentaParaVeriFactu($idVenta, $db);
            if (!$datosVf) throw new Exception("getVentaParaVeriFactu retornó null para ID $idVenta");
            
            if (empty($datosVf['fecha_expedicion'])) {
                 // Inspeccionar por qué está vacía
                 $stmtDebug = $db->prepare("SELECT id, fecha, numero_ticket FROM ventas WHERE id = ?");
                 $stmtDebug->execute([$idVenta]);
                 $rowDebug = $stmtDebug->fetch(PDO::FETCH_ASSOC);
                 throw new Exception("CRITICAL: fecha_expedicion vacía para ID $idVenta. DatosVf: " . json_encode($datosVf) . " | DB Row: " . json_encode($rowDebug));
            }
            
            // FORZAR el identificador original y el tipo de registro
            $datosVf['numero_serie'] = $numeroSerieOriginal;
            $datosVf['subsanacion']  = $flagSubsanacion;
            $datosVf['rechazo_previo'] = $flagRechazoPrevio;

            // Sincronizar tipo_factura con el prefijo del numero_serie para evitar errores de huella (2000)
            // Si empieza por T, debe ser F2 (Simplificada), si empieza por F debe ser F1 (Factura)
            $prefix = substr($numeroSerieOriginal, 0, 1);
            $datosVf['tipo_factura'] = ($prefix === 'F') ? 'F1' : 'F2';

            // 3. Obtener el hash anterior (SIEMPRE EL ÚLTIMO GLOBAL)
            // Para evitar romper la cadena de las ventas que se hicieron DESPUÉS de esta,
            // la subsanación debe enviarse como un nuevo eslabón al FINAL de la cadena actual.
            $datosUltimo = self::obtenerUltimoHash($db);
            $hashAnterior = $datosUltimo['hash'] ?? str_repeat('0', 64);
            $serieAnt = $datosUltimo['serie'] ?? '';
            $fechaAnt = $datosUltimo['fecha'] ?? '';

            $datosVf['serie_anterior'] = $serieAnt;
            $datosVf['fecha_anterior'] = $fechaAnt;
            $datosVf['hash_anterior']  = $hashAnterior;

            $fechaHoraGen = date('Y-m-d\\TH:i:sP');

            // 4. Recalcular el hash del registro
            // La CuotaTotal del hash debe coincidir EXACTAMENTE con lo que VeriFactuService
            // pondrá en <CuotaTotal> del XML: la suma de cuotas de los desgloses.
            // Si se usara cuota_total (iva_amt de BD), habría divergencia → error 2000.
            $cuotaParaHash = 0;
            foreach ($datosVf['desgloses'] as $d) {
                $cuotaParaHash += ($d['cuota'] ?? 0) + ($d['cuota_re'] ?? 0);
            }
            if ($cuotaParaHash == 0) {
                $cuotaParaHash = $datosVf['cuota_total'];
            }

            $datosVf['nif_emisor'] = $nifEmisor;
            $datosVf['fecha_hora_gen'] = $fechaHoraGen;
            $hashActual = self::generarHashVeriFactu([
                'nif_emisor'       => $nifEmisor,
                'numero_serie'     => $datosVf['numero_serie'],
                'fecha_expedicion' => $datosVf['fecha_expedicion'],
                'tipo_factura'     => $datosVf['tipo_factura'],
                'cuota_total'      => $cuotaParaHash,
                'importe_total'    => $datosVf['importe_total'],
                'fecha_hora_gen'   => $fechaHoraGen
            ], $hashAnterior);

            // 5. Preparar datos finales para el XML
            $datosVf['fecha_hora_gen'] = $fechaHoraGen;
            $datosVf['hash_actual']    = $hashActual;

            // 6. Generar XML firmado
            require_once __DIR__ . '/VeriFactuService.php';
            $vf    = new VeriFactuService();
            $resVf = $vf->procesarAlta($datosVf);
            if (!$resVf['ok']) throw new Exception("Error al generar XML: " . $resVf['error']);

            // 7. Persistir y encolar
            // IMPORTANTE: NO actualizamos hash_actual en la tabla 'ventas' si ya tiene uno,
            // para no romper la referencia de las ventas posteriores que ya apuntan al hash original.
            // Solo actualizamos el estado. El nuevo hash vive en verifactu_logs.
            DBPDO::ejecutarConsulta(
                "UPDATE ventas SET estado_envio_aeat = 'pendiente' WHERE id = :id",
                [':id' => $idVenta],
                $db
            );

            $stmtLog = $db->prepare("INSERT INTO verifactu_logs (id_venta, tipo_registro, numero_serie, xml_path, hash_anterior, hash_actual, estado)
                                     VALUES (:id, :tipo, :serie, :path, :ant, :act, 'pendiente')");
            $stmtLog->execute([
                ':id'    => $idVenta,
                ':tipo'  => ($esRechazo ? 'AltaPorRechazo' : 'AltaPorSubsanacion'),
                ':serie' => $datosVf['numero_serie'],
                ':path'  => $resVf['path'],
                ':ant'   => $hashAnterior,
                ':act'   => $hashActual
            ]);

            require_once __DIR__ . '/AeatQueueService.php';
            $queueSvc = new AeatQueueService();
            
            // 6.5 LIMPIEZA: Eliminar cualquier rastro anterior en la cola para esta venta
            // Esto evita duplicados visuales y técnicos en el dashboard y el procesador.
            DBPDO::ejecutarConsulta("DELETE FROM cola_envios WHERE id_venta = :id", [':id' => $idVenta], $db);

            $queueSvc->encolar($idVenta, $resVf['path']);

            $db->commit();

            // Tras subsanar el registro bloqueante, regenerar y encolar los bloqueados posteriores
            $queueSvc->desbloquearRegistrosSiguientes($idVenta);

            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function regenerarVentaAlta(int $idVenta): bool
    {
        try {
            self::regenerarRegistroVeriFactu($idVenta);
            return true;
        } catch (Exception $e) {
            error_log("Error en regenerarVentaAlta: " . $e->getMessage());
            throw $e;
        }
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
        $totalBaseAbono = 0;
        $totalIvaAbono  = 0;
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

        // Determinar si es rectificativa total (Sustitución) o parcial (Diferencias)
        $totalVentaOriginal = abs((float)$ventaOrigen['total']);
        $sqlSumPrev = "SELECT COALESCE(SUM(ABS(total)), 0) AS total_ya_abonado
                       FROM ventas
                       WHERE id_venta_origen = :idv AND tipo_documento = 'abono'";
        $qSumPrev = DBPDO::ejecutarConsulta($sqlSumPrev, [':idv' => $idVentaOrigen]);
        $totalYaAbonadoPrev = (float)($qSumPrev->fetch(PDO::FETCH_ASSOC)['total_ya_abonado'] ?? 0);
        
        $esRectificativaTotal = (($totalYaAbonadoPrev + $totalAbono) >= $totalVentaOriginal - 0.01);

        // Calcular valores VeriFactu antes del INSERT
        if ($esRectificativaTotal) {
            $tipoRectificativa = 'S'; // Sustitución
            $totalAeat = 0.0;
        } else {
            $tipoRectificativa = 'I'; // Diferencias
            $totalAeat = -$totalAbono;
        }

        // 4. INSERT en ventas (importe NEGATIVO para el abono)
        $sqlAbono = "INSERT INTO ventas
            (numero_ticket, fecha, id_usuario, id_cliente, tipo_cliente, nombre_cliente, nif_cliente,
             metodo_pago, subtotal, descuento_pct, descuento_amt, descuento_label,
             base_imponible, iva_pct, iva_amt, total, efectivo_recibido,
             estado, pagado_a_cuenta, fecha_limite_pago, es_factura, comentarios, id_turno,
             puntos_ganados, puntos_canjeados, puntos_descuento_amt,
             tipo_documento, id_venta_origen, tipo_rectificativa, total_aeat)
        VALUES
            (:ticket, NOW(), :usuario, :cliente, :tipo, :nombre, :nif,
             :metodo, :subtotal, 0, 0, NULL,
             :base, 21.00, :iva, :total, 0,
             'completada', 0, NULL, :esFactura, :comentarios, :idTurno,
             0, 0, 0,
             'abono', :ventaOrigen, :tipoRect, :totalAeat)";

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
            ':tipoRect'    => $tipoRectificativa,
            ':totalAeat'   => $totalAeat,
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



            // [VERIFACTU] Encadenamiento y Huella (Abono)
            $fechaFormatAb = date('d-m-Y');

            // Determinar el tipo R correcto:
            //   - Si la original era F2 (ticket simplificado, es_factura=0) → siempre R5 (regla obligatoria AEAT)
            //   - De lo contrario, usar el tipo mapeado automáticamente por el motivo
            $esFacturaOrigen = (bool)$ventaOrigen['es_factura'];
            // Forzamos R5 si la original era simplificada (F2) o si es una rectificativa TOTAL (Anulación)
            if (!$esFacturaOrigen || $esRectificativaTotal) {
                $tipoAb = 'R5';
            } else {
                $tipoAb = $tipoR_auto;
            }

            $numFormatedAb = self::formatTicketNumber($numTicket, time(), $ventaOrigen['es_factura'], 'abono');

            // [NUEVO] Lógica de cumplimiento AEAT: Diferenciar entre rectificativa parcial y total
            //   - Rectificativa Parcial (Devolución): Tipo 'I' (Diferencias), enviamos importe negativo.
            //   - Rectificativa Total (Anulación): Tipo 'S' (Sustitución), enviamos importe 0.00.
            
            // El valor de esRectificativaTotal ya ha sido calculado arriba antes del INSERT

            // Actualizar estado de la venta original según si se ha devuelto todo o no
            $nuevoEstado = $esRectificativaTotal ? 'devuelta' : 'parcialmente_devuelta';
            DBPDO::ejecutarConsulta(
                "UPDATE ventas SET estado = :estado WHERE id = :id",
                [':estado' => $nuevoEstado, ':id' => $idVentaOrigen]
            );

            // FLUJO RECTIFICACIÓN (Verde):
            // Generamos abono normal (Tipo I o S según si es total) para que ambos salgan en Verde
            // [NUEVO] Si es total (Sustitución por cero), la cuota también debe ser 0 para evitar error de signos
            $ivaAeat   = $esRectificativaTotal ? 0.0 : -$totalIvaAbono;
            $skipVerifactuAbono = false;

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
                (float)$totalAeat, (float)$ivaAeat, $fechaFormatAb,
                $ventaOrigen['nif_cliente'] ?? '',
                $ventaOrigen['nombre_cliente'] ?? '',
                $serieOrigenAb, $fechaOrigenAb,
                $baseOrigenAb, $cuotaOrigenAb,
                $tipoRectificativa, null,
                $ventaOrigen['aeat_id_type'] ?? '01',
                $ventaOrigen['aeat_codigo_pais'] ?? 'ES'
            );

            $db->commit();

            // [NUEVO] Envío VeriFactu FUERA de la transacción para evitar conflictos
            try {
                require_once __DIR__ . '/AeatQueueService.php';
                (new AeatQueueService())->procesarCola(); 
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
     * Genera la cadena normalizada y el hash SHA256 según el Reglamento VeriFactu.
     * @param array $datos Cabecera de la factura/evento
     * @param string|null $hashAnterior Huella del registro previo
     * @return string Hash SHA256 en mayúsculas
     */
    public static function generarHashVeriFactu(array $datos, ?string $hashAnterior = null): string
    {
        // 1. Recopilación de campos (Tipo: Alta de factura)
        // La AEAT exige un formato de cadena de consulta (Query String) para el cálculo del hash
        $campos = [
            'IDEmisorFactura'         => $datos['nif_emisor'] ?? '',
            'NumSerieFactura'         => $datos['numero_serie'] ?? '',
            'FechaExpedicionFactura'  => date('d-m-Y', strtotime($datos['fecha_expedicion'] ?? date('Y-m-d'))),
            'TipoFactura'             => $datos['tipo_factura'] ?? 'F1',
            'CuotaTotal'              => self::normalizarValorHash($datos['cuota_total'] ?? 0),
            'ImporteTotal'            => self::normalizarValorHash($datos['importe_total'] ?? 0),
            'Huella'                  => strtoupper($hashAnterior ?? str_repeat('0', 64)),
            'FechaHoraHusoGenRegistro'=> $datos['fecha_hora_gen'] ?? date('Y-m-d\TH:i:sP')
        ];

        // 2. Construcción de la cadena con formato KEY=VALUE&KEY=VALUE...
        // El orden debe ser EXACTAMENTE el especificado por la AEAT.
        $cadena = "IDEmisorFactura=" . $campos['IDEmisorFactura']
                . "&NumSerieFactura=" . $campos['NumSerieFactura']
                . "&FechaExpedicionFactura=" . $campos['FechaExpedicionFactura']
                . "&TipoFactura=" . $campos['TipoFactura']
                . "&CuotaTotal=" . $campos['CuotaTotal']
                . "&ImporteTotal=" . $campos['ImporteTotal']
                . "&Huella=" . $campos['Huella']
                . "&FechaHoraHusoGenRegistro=" . $campos['FechaHoraHusoGenRegistro'];

        // 3. Cálculo del Hash (SHA256) en mayúsculas
        $hashResult = strtoupper(hash('sha256', $cadena));
        
        // DEBUG: Guardar la cadena exacta para inspección
        file_put_contents(dirname(__DIR__) . '/storage/debug_hash.txt', "CADENA: $cadena\nHASH: $hashResult\n\n", FILE_APPEND);
        
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
        string $tipoRectificativa = 'S', ?int $relativeToId = null,
        ?string $aeatIdType = '01', ?string $aeatCodigoPais = 'ES'
    ): void
    {
        // 1. Obtener datos COMPLETOS y CONSISTENTES mediante getVentaParaVeriFactu
        // Esto garantiza que el desglose de IVA sea matemáticamente correcto (Error 1142)
        $datosVf = self::getVentaParaVeriFactu($idVenta, $db);
        if (!$datosVf) return;

        // 2. Sobrescribir identificadores si es necesario (ej. para rectificativas o series específicas)
        // Aunque getVentaParaVeriFactu ya intenta detectarlos, procesarVeriFactu puede recibir
        // parámetros forzados desde el flujo de anulación o venta.
        $datosVf['numero_serie']     = $numeroSerie;
        $datosVf['tipo_factura']     = $tipoFactura;
        $datosVf['destinatario_nif'] = $nifCliente;
        $datosVf['destinatario_nombre'] = $nombreCliente;
        
        // 3. Obtener el hash anterior (SIEMPRE EL ÚLTIMO GLOBAL)
        $datosUltimo = self::obtenerUltimoHash($db, $relativeToId);
        $hashAnterior = $datosUltimo['hash'] ?? str_repeat('0', 64);
        $serieAnt = $datosUltimo['serie'] ?? '';
        $fechaAnt = $datosUltimo['fecha'] ?? '';

        $datosVf['serie_anterior'] = $serieAnt;
        $datosVf['fecha_anterior'] = $fechaAnt;
        $datosVf['hash_anterior']  = $hashAnterior;

        $fechaHoraGen = date('Y-m-d\TH:i:sP');
        $datosVf['fecha_hora_gen'] = $fechaHoraGen;

        // 4. Recalcular el hash usando la cuota_total consistente (suma de desgloses)
        $cuotaParaHash = 0;
        foreach ($datosVf['desgloses'] as $d) {
            $cuotaParaHash += ($d['cuota'] ?? 0) + ($d['cuota_re'] ?? 0);
        }

        $hashActual = self::generarHashVeriFactu([
            'nif_emisor'       => defined('EMPRESA_CIF') ? EMPRESA_CIF : '00000000T',
            'numero_serie'     => $datosVf['numero_serie'],
            'fecha_expedicion' => $datosVf['fecha_expedicion'],
            'tipo_factura'     => $datosVf['tipo_factura'],
            'cuota_total'      => $cuotaParaHash,
            'importe_total'    => $datosVf['importe_total'],
            'fecha_hora_gen'   => $fechaHoraGen
        ], $hashAnterior);

        $datosVf['hash_actual'] = $hashActual;

        $datosVf['destinatario_id_type']     = $aeatIdType ?? '01';
        $datosVf['destinatario_pais']        = $aeatCodigoPais ?? 'ES';
        $datosVf['factura_rectificada_serie'] = $facturaOrigenSerie;
        $datosVf['factura_rectificada_fecha'] = $facturaOrigenFecha;
        $datosVf['base_rectificada']          = $baseOrigenRectificada;
        $datosVf['cuota_rectificada']         = $cuotaOrigenRectificada;
        $datosVf['tipo_rectificativa']        = $tipoRectificativa;

        // 5. Generar XML VeriFactu firmado
        require_once __DIR__ . '/VeriFactuService.php';
        $vfService = new VeriFactuService();
        $resultadoXML = $vfService->procesarAlta($datosVf);

        // 4. Encolar para envío a la AEAT

        require_once __DIR__ . '/AeatQueueService.php';
        $queueService = new AeatQueueService();
        if (isset($resultadoXML['ok']) && $resultadoXML['ok']) {
            $queueService->encolar($idVenta, $resultadoXML['path']);
        } else {
            $errorMsg = $resultadoXML['error'] ?? 'Error desconocido al generar XML';
            error_log("VeriFactu Error: $errorMsg");
            $queueService->encolarError($idVenta, "Error generación XML: $errorMsg");
        }

        // 5. Construir URL del código QR para cotejo AEAT
        $nifEmisor = defined('EMPRESA_CIF') ? EMPRESA_CIF : '00000000T';
        $qrBaseUrl = defined('VERIFACTU_URL_QR_PRUEBAS') ? VERIFACTU_URL_QR_PRUEBAS : 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR';
        $qrParams = [
            'nif'      => $nifEmisor,
            'numserie' => $numeroSerie,
            'fecha'    => date('d-m-Y', strtotime($fechaExpedicion)),
            // El importe debe ser el total firmado (con signo en caso de abonos por diferencias)
            'importe'  => number_format($total, 2, '.', ''),
            'hash'     => strtoupper(substr($hashActual, 0, 8))
        ];
        $qrUrl = $qrBaseUrl . "?" . http_build_query($qrParams, '', '&', PHP_QUERY_RFC3986);

        // 6. Actualizar información fiscal y QR en ventas
        // El estado_envio_aeat YA ha sido actualizado por $queueService->encolar()
        $xmlPath = $resultadoXML['ok'] ? $resultadoXML['path'] : null;
        $sql = "UPDATE ventas 
                SET hash_actual = :hashActual, 
                    hash_anterior = :hashAnterior,
                    fecha_hora_gen_fiscal = :fechaHora,
                    codigo_qr = :xmlPath,
                    qr_verifactu = :qrUrl
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':hashActual'   => $hashActual,
            ':hashAnterior' => $hashAnterior,
            ':fechaHora'    => $fechaHoraGen,
            ':xmlPath'      => $xmlPath,
            ':qrUrl'        => $qrUrl,
            ':id'           => $idVenta
        ]);

        // [NUEVO] Registrar en verifactu_logs para mantener la cadena única
        if ($resultadoXML['ok']) {
            $stmtLog = $db->prepare("INSERT INTO verifactu_logs (id_venta, tipo_registro, numero_serie, xml_path, hash_anterior, hash_actual, estado)
                                     VALUES (:id, 'Alta', :serie, :path, :ant, :act, 'pendiente')");
            $stmtLog->execute([
                ':id'    => $idVenta,
                ':serie' => $numeroSerie,
                ':path'  => $xmlPath,
                ':ant'   => $hashAnterior,
                ':act'   => $hashActual
            ]);
        }
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
     * Obtiene los datos de serie y fecha del último registro fiscal absoluto para el encadenamiento XML.
     * Consulta tanto la tabla de ventas (altas) como verifactu_logs (anulaciones/altas extra).
     */
    public static function obtenerUltimoHash(?PDO $db = null, ?int $relativeToId = null): array
    {
        $db = $db ?? DBPDO::getPDO();
        
        $whereLog = $relativeToId ? "AND id_venta < " . (int)$relativeToId : "";
        $whereVenta = $relativeToId ? "AND id < " . (int)$relativeToId : "";

        // 1. Intentamos obtener el registro más reciente de los logs (excluyendo rechazos críticos)
        $sqlLogs = "SELECT numero_serie as serie, DATE_FORMAT(fecha_registro, '%d-%m-%Y') as fecha, hash_actual as hash 
                    FROM verifactu_logs 
                    WHERE estado IN ('pendiente', 'enviado', 'subsanacion_pendiente')
                    $whereLog
                    ORDER BY id DESC LIMIT 1 FOR UPDATE";
        $stmtLogs = $db->query($sqlLogs);
        $resLogs = $stmtLogs->fetch(PDO::FETCH_ASSOC);

        if ($resLogs) return $resLogs;

        // 2. Fallback a la tabla de ventas (excluyendo rechazos críticos)
        $sqlVentas = "SELECT numero_ticket, fecha, es_factura, hash_actual 
                      FROM ventas 
                      WHERE hash_actual IS NOT NULL 
                      AND estado_envio_aeat NOT IN ('error_critico')
                      $whereVenta
                      ORDER BY id DESC LIMIT 1 FOR UPDATE";
        $stmtVentas = $db->query($sqlVentas);
        $resVentas = $stmtVentas->fetch(PDO::FETCH_ASSOC);

        if (!$resVentas) return [];

        return [
            'serie' => self::formatTicketNumber($resVentas['numero_ticket'], $resVentas['fecha'], $resVentas['es_factura']),
            'fecha' => date('d-m-Y', strtotime($resVentas['fecha'])),
            'hash'  => $resVentas['hash_actual']
        ];
    }

    /**
     * Prepara y genera un registro de ANULACIÓN para VeriFactu.
     * @param PDO $db Conexión activa (dentro de transacción)
     * @param int $idVenta ID de la venta original a anular
     */
    public static function procesarAnulacionVeriFactu($db, $idVenta)
    {
        // 1. Obtener datos de la venta original
        $stmt = $db->prepare("SELECT * FROM ventas WHERE id = :id");
        $stmt->execute([':id' => $idVenta]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$venta) return;

        // 2. Preparar datos para RegistroAnulacion
        require_once __DIR__ . '/VeriFactuService.php';
        $vfs = new VeriFactuService();
        
        $numFormated = self::formatTicketNumber(
            $venta['numero_ticket'],
            $venta['fecha'],
            $venta['es_factura'],
            $venta['tipo_documento']
        );
        $fechaExp = date('d-m-Y', strtotime($venta['fecha']));
        $fechaGen = date('Y-m-d\TH:i:sP');

        // Calcular Hash Anterior para la anulación
        $hashAnt = self::obtenerUltimoHash($db);

        require_once __DIR__ . '/ConfiguracionPDO.php';
        $nifEmisor = defined('EMPRESA_CIF') ? EMPRESA_CIF : (ConfiguracionPDO::obtenerValor('empresa_nif') ?? '00000000T');

        $datos = [
            'numero_serie'     => $numFormated,
            'fecha_expedicion' => $fechaExp,
            'hash_anterior'    => $hashAnt['hash'] ?? str_repeat('0', 64),
            'serie_anterior'   => $hashAnt['serie'] ?? '',
            'fecha_anterior'   => $hashAnt['fecha'] ?? '',
            'fecha_hora_gen'   => $fechaGen
        ];

        // [NUEVO] Cálculo de huella para Anulación según especificación AEAT
        // Formato: IDEmisorFacturaAnulada=...&NumSerieFacturaAnulada=...&FechaExpedicionFacturaAnulada=...&Huella=...&FechaHoraHusoGenRegistro=...
        $cadena = "IDEmisorFacturaAnulada=" . $nifEmisor .
                  "&NumSerieFacturaAnulada=" . $numFormated .
                  "&FechaExpedicionFacturaAnulada=" . $fechaExp .
                  "&Huella=" . $datos['hash_anterior'] .
                  "&FechaHoraHusoGenRegistro=" . $fechaGen;
        
        $hashActual = strtoupper(hash('sha256', $cadena));
        $datos['hash_actual'] = $hashActual;
        
        // Log para diagnóstico
        error_log("VeriFactu Anulacion Hash String: " . $cadena);
        error_log("VeriFactu Anulacion Hash Result: " . $hashActual);

        // Procesar XML de anulación
        $res = $vfs->procesarAnulacion($datos);
        
        if ($res['ok']) {
            $stmtLog = $db->prepare("INSERT INTO verifactu_logs (id_venta, tipo_registro, numero_serie, xml_path, hash_anterior, hash_actual, estado)
                                     VALUES (:id, 'Anulacion', :serie, :path, :ant, :act, 'pendiente')");
            $stmtLog->execute([
                ':id'    => $idVenta,
                ':serie' => $numFormated,
                ':path'  => $res['path'],
                ':ant'   => $datos['hash_anterior'],
                ':act'   => $hashActual
            ]);

            // [NUEVO] Actualizar la venta con la huella de anulación para que el QR sea consistente
            $stmtUpd = $db->prepare("UPDATE ventas SET hash_actual = :hash WHERE id = :id");
            $stmtUpd->execute([':hash' => $hashActual, ':id' => $idVenta]);

            // [IMPORTANTE] Encolar para el envío real a la AEAT
            require_once __DIR__ . '/AeatQueueService.php';
            (new AeatQueueService())->encolar($idVenta, $res['path']);

            // Reforzar el estado para distinguir anulación en la UI
            $db->prepare("UPDATE ventas SET estado_envio_aeat = 'anulado_pendiente' WHERE id = :id AND estado_envio_aeat = 'pendiente'")
               ->execute([':id' => $idVenta]);
        }
    }

    /**
     * Anula una venta completa fiscalmente (RegistroAnulacion) sin generar un ticket de abono.
     * Actualiza el estado de la venta original a 'anulada'.
     *
     * @param int    $idVenta       ID de la venta a anular
     * @param string $motivo        Motivo de la anulación
     * @param string $metodoReembolso 'efectivo', 'vale'
     * @param int    $idUsuario     ID del usuario que procesa
     * @param bool   $reponerStock  Si se deben reponer productos al inventario
     * @return bool
     */
    public static function anularVentaFiscalmente(int $idVenta, string $motivo, string $metodoReembolso, int $idUsuario, bool $reponerStock = true): bool
    {
        $db = DBPDO::getPDO();
        $db->beginTransaction();

        try {
            // 1. Obtener datos de la venta original
            $venta = self::obtenerVentaPorId($idVenta);
            if (!$venta) throw new \Exception('Venta no encontrada.');

            // 2. Reponer stock (Solo si se solicita)
            if ($reponerStock) {
                require_once __DIR__ . '/ProductoPDO.php';
                require_once __DIR__ . '/MovimientoStockPDO.php';
                foreach ($venta['lineas'] as $linea) {
                    if (!empty($linea['id_producto'])) {
                        ProductoPDO::aumentarStock((int)$linea['id_producto'], (int)$linea['cantidad']);
                        MovimientoStockPDO::registrarMovimiento((int)$linea['id_producto'], 'anulacion', (int)$linea['cantidad'], $idUsuario, "Anulación Ticket #".$venta['numero_ticket'], $db);
                    }
                }
            }

            // 3. Gestionar reembolso (Solo devolvemos lo que realmente se pagó)
            $montoAReembolsar = (float)$venta['total'];
            if (($venta['estado'] ?? '') === 'pendiente_pago' || ($venta['estado'] ?? '') === 'parcialmente_devuelta') {
                $montoAReembolsar = (float)($venta['pagado_a_cuenta'] ?? 0);
            }

            $idCliente = $venta['id_cliente'] ? (int)$venta['id_cliente'] : null;

            if ($montoAReembolsar > 0.005) {
                if ($metodoReembolso === 'vale') {
                    require_once __DIR__ . '/ValePDO.php';
                    $codigoVale = ValePDO::crearVale($idCliente, $idVenta, $montoAReembolsar);
                    require_once __DIR__ . '/LogPDO.php';
                    LogPDO::addLog('GENERACION_VALE', "Vale generado (#$codigoVale) por $montoAReembolsar€ por anulación de Ticket #".$venta['numero_ticket']);
                } elseif ($metodoReembolso === 'efectivo') {
                    require_once __DIR__ . '/CajaTurnoPDO.php';
                    $turno = CajaTurnoPDO::obtenerTurnoAbierto();
                    if ($turno) {
                        CajaTurnoPDO::registrarRetiro(
                            (int)$turno['id'],
                            $idUsuario,
                            $montoAReembolsar,
                            "Reembolso efectivo por anulación Ticket #".$venta['numero_ticket']
                        );
                    }
                }
            }

            // 4. Revertir puntos del cliente (si es socio)
            if ($idCliente) {
                require_once __DIR__ . '/ClientePDO.php';
                $puntosGanados = (int)($venta['puntos_ganados'] ?? 0);
                $puntosCanjeados = (int)($venta['puntos_canjeados'] ?? 0);

                if ($puntosGanados > 0) {
                    ClientePDO::restarPuntos($idCliente, $puntosGanados);
                }
                if ($puntosCanjeados > 0) {
                    ClientePDO::sumarPuntos($idCliente, $puntosCanjeados);
                }
            }

            // 4. Actualizar estado de la venta original a 'anulada'
            DBPDO::ejecutarConsulta(
                "UPDATE ventas SET estado = :estado, comentarios = :coment WHERE id = :id",
                [
                    ':estado' => 'anulada', 
                    ':coment' => $venta['comentarios'] . " | ANULADA: $motivo",
                    ':id'     => $idVenta
                ]
            );

            // 5. Enviar RegistroAnulacion a VeriFactu
            self::procesarAnulacionVeriFactu($db, $idVenta);

            $db->commit();

            // 6. Procesar cola VeriFactu de forma inmediata (fuera de transacción)
            try {
                require_once __DIR__ . '/AeatQueueService.php';
                (new AeatQueueService())->procesarCola(); 
            } catch (\Exception $eVf) {
                error_log("Error en envío inmediato VeriFactu (Anulación): " . $eVf->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene los datos de una venta formateados para VeriFactu.
     */
    public static function getVentaParaVeriFactu(int $idVenta, ?PDO $db = null)
    {
        $db = $db ?? DBPDO::getPDO();
        $sql = "SELECT v.*, u.nombre as nombre_cajero 
                FROM ventas v 
                LEFT JOIN usuarios u ON v.id_usuario = u.id 
                WHERE v.id = :id";
        $stmt = DBPDO::ejecutarConsulta($sql, [':id' => $idVenta], $db);
        $v = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$v) return null;

        $esAbono = ($v['tipo_documento'] ?? 'venta') === 'abono';
        $numeroSerie = self::formatTicketNumber($v['numero_ticket'], $v['fecha'], !empty($v['es_factura']), $v['tipo_documento'] ?? 'venta');
        
        $tipoFactura = $esAbono ? 'R1' : ($v['es_factura'] ? 'F1' : 'F2');
        
        // [REGLA FISCAL] Si no hay NIF, no puede ser F1 ni R1 (deben ser Simplificadas F2 o R5)
        if (empty($v['nif_cliente']) || trim($v['nif_cliente']) === '') {
            if ($tipoFactura === 'F1') $tipoFactura = 'F2';
            if ($tipoFactura === 'R1') $tipoFactura = 'R5';
        }

        // Recuperar datos del anterior para el encadenamiento
        $serieAnt = '';
        $fechaAnt = '';
        if (!empty($v['hash_anterior']) && $v['hash_anterior'] !== str_repeat('0', 64)) {
            // Buscar en logs vinculando con ventas para obtener la FECHA REAL del ticket
            $sqlLog = "SELECT l.numero_serie, v.fecha 
                       FROM verifactu_logs l 
                       JOIN ventas v ON l.id_venta = v.id 
                       WHERE l.hash_actual = :h LIMIT 1";
            $resLog = DBPDO::ejecutarConsulta($sqlLog, [':h' => $v['hash_anterior']], $db)->fetch();
            if ($resLog) {
                $serieAnt = $resLog['numero_serie'];
                $fechaAnt = date('d-m-Y', strtotime($resLog['fecha']));
            } else {
                // Fallback a ventas directamente
                $sqlV = "SELECT numero_ticket, fecha, es_factura, tipo_documento FROM ventas WHERE hash_actual = :h LIMIT 1";
                $resV = DBPDO::ejecutarConsulta($sqlV, [':h' => $v['hash_anterior']], $db)->fetch();
                if ($resV) {
                    $serieAnt = self::formatTicketNumber($resV['numero_ticket'], $resV['fecha'], $resV['es_factura'], $resV['tipo_documento']);
                    $fechaAnt = date('d-m-Y', strtotime($resV['fecha']));
                }
            }
        }

        // Obtener el desglose de IVA real por líneas (para evitar Error 1142)
        $sqlL = "SELECT iva_aplicado, SUM(total_linea) as total_bruto 
                 FROM lineas_venta 
                 WHERE id_venta = :id 
                 GROUP BY iva_aplicado";
        $stmtL = DBPDO::ejecutarConsulta($sqlL, [':id' => $idVenta], $db);
        $lineasIva = $stmtL->fetchAll(PDO::FETCH_ASSOC);

        // [RECALCULO ROBUSTO PARA EVITAR ERROR 1142]
        // No podemos confiar en los campos base_imponible e iva_amt de la tabla si hay descuentos,
        // ya que la AEAT exige una coherencia exacta entre Base * Tipo = Cuota.
        // Redistribuimos el total real cobrado entre los tipos de IVA presentes.
        
        $totalVenta = (float)$v['total'];
        $totalBrutoVenta = 0;
        foreach ($lineasIva as $l) $totalBrutoVenta += (float)$l['total_bruto'];
        
        $desgloses = [];
        $runningTotalNeto = 0;
        $countGroups = count($lineasIva);
        
        foreach ($lineasIva as $index => $l) {
            $rawRate = (float)$l['iva_aplicado'];
            
            // Forzar a tipos estándar de España
            $standardRates = [21.00, 10.00, 4.00, 0.00];
            $ivaRate = 21.00;
            $minDiff = 999;
            foreach ($standardRates as $sr) {
                if (abs($rawRate - $sr) < $minDiff) {
                    $minDiff = abs($rawRate - $sr);
                    $ivaRate = $sr;
                }
            }

            // 1. Distribuir el total de la venta proporcionalmente a este grupo de IVA
            if ($index === $countGroups - 1) {
                $totalNetoGrupo = round($totalVenta - $runningTotalNeto, 2);
            } else {
                if ($totalBrutoVenta > 0) {
                    $totalNetoGrupo = round(($l['total_bruto'] / $totalBrutoVenta) * $totalVenta, 2);
                } else {
                    $totalNetoGrupo = 0;
                }
                $runningTotalNeto += $totalNetoGrupo;
            }

            // 2. Calcular Base e IVA para que sumen el TotalNetoGrupo y cumplan Base * Tipo = Cuota
            // Base = Total / (1 + Tipo)
            $baseG = round($totalNetoGrupo / (1 + $ivaRate / 100), 2);
            $cuotaG = round($totalNetoGrupo - $baseG, 2);
            
            // Validar coherencia fiscal (Error 1142)
            $cuotaLegal = round($baseG * ($ivaRate / 100), 2);
            if (abs($cuotaG - $cuotaLegal) > 0.001) {
                // Si hay descuadre de 1 céntimo, ajustamos la base para que la cuota sea legal
                $baseG = round($totalNetoGrupo - $cuotaLegal, 2);
                $cuotaG = $cuotaLegal;
                
                // Re-verificar si al ajustar la base, la cuota legal cambia (recursivo conceptual)
                $cuotaFinal = round($baseG * ($ivaRate / 100), 2);
                if (abs($baseG + $cuotaFinal - $totalNetoGrupo) > 0.001) {
                    // Si no cuadra, priorizamos Base + Cuota = Total y dejamos que el margen de error
                    // de la AEAT (0.01) absorba la diferencia si es posible.
                }
            }

            $desgloses[] = [
                'tipo'     => $ivaRate,
                'base'     => $baseG,
                'cuota'    => $cuotaG,
                'tipo_re'  => 0,
                'cuota_re' => 0
            ];
        }

        // Asegurar que el importe total que enviamos es la suma exacta de lo calculado
        $baseTotalCalculada = 0;
        $cuotaTotalCalculada = 0;
        foreach ($desgloses as $d) {
            $baseTotalCalculada += $d['base'];
            $cuotaTotalCalculada += $d['cuota'];
        }
        $importeTotalFinal = round($baseTotalCalculada + $cuotaTotalCalculada, 2);

        return [
            'numero_serie'              => $numeroSerie,
            'fecha_expedicion'          => date('d-m-Y', strtotime($v['fecha'])),
            'tipo_factura'              => $tipoFactura,
            'tipo_rectificativa'        => $v['tipo_rectificativa'] ?? ($esAbono ? 'S' : null),
            'base_imponible'            => $baseTotalCalculada,
            'cuota_total'               => $cuotaTotalCalculada,
            'importe_total'             => $importeTotalFinal,
            'hash_actual'              => $v['hash_actual'],
            'hash_anterior'            => $v['hash_anterior'],
            'fecha_hora_gen'           => !empty($v['fecha_hora_gen_fiscal']) ? date('Y-m-d\TH:i:sP', strtotime($v['fecha_hora_gen_fiscal'])) : date('Y-m-d\TH:i:sP'),
            'destinatario_nif'         => $v['nif_cliente'],
            'destinatario_nombre'      => $v['nombre_cliente'],
            'destinatario_id_type'     => $v['aeat_id_type'] ?? '01',
            'destinatario_pais'        => $v['aeat_codigo_pais'] ?? 'ES',
            'factura_rectificada_serie' => $v['num_ticket_origen_completo'] ?? null,
            'factura_rectificada_fecha' => !empty($v['fecha_ticket_origen']) ? date('d-m-Y', strtotime($v['fecha_ticket_origen'])) : null,
            'serie_anterior'           => $serieAnt,
            'fecha_anterior'           => $fechaAnt,
            'desgloses'                => $desgloses
        ];
    }

    /**
     * Regenera el registro VeriFactu para una venta existente (útil para corregir errores de Hash 2000).
     */
    public static function regenerarRegistroVeriFactu(int $idVenta): void
    {
        $db = DBPDO::getPDO();
        $q = DBPDO::ejecutarConsulta("SELECT * FROM ventas WHERE id = :id", [':id' => $idVenta]);
        $v = $q->fetch(PDO::FETCH_ASSOC);
        if (!$v) throw new \Exception("Venta no encontrada");

        $ticket = $v['numero_ticket'];
        $esFactura = (bool)$v['es_factura'];
        $tipoDoc = $v['tipo_documento'] ?? 'venta';
        $tipo = ($esFactura ? 'F1' : 'F2');
        if ($tipoDoc === 'abono') $tipo = ($esFactura ? 'R1' : 'R5');

        // Formatear correctamente la serie para que coincida con lo que la AEAT devuelve
        // y con lo que analizarResultadoLinea busca. Sin esto se generaba el XML con el
        // numero_ticket raw (ej: "1020315") y el sistema no encontraba la respuesta → error 500.
        $ticketFormateado = self::formatTicketNumber($ticket, $v['fecha'], $esFactura && !empty($v['nif_cliente']), $tipoDoc);

        // Borrar envío previo en cola para que no haya duplicados locales
        DBPDO::ejecutarConsulta("DELETE FROM cola_envios WHERE id_venta = :id", [':id' => $idVenta]);

        // Formatear fecha a d-m-Y (según XSD)
        $fechaFmt = date('d-m-Y', strtotime($v['fecha']));

        self::procesarVeriFactu(
            $db, $idVenta, $ticketFormateado, $tipo,
            (float)$v['total'], (float)$v['iva_amt'], $fechaFmt,
            $v['nif_cliente'] ?? '', $v['nombre_cliente'] ?? 'Cliente General',
            '', '', '', '', 'S', $idVenta, // Pasamos el ID para el encadenamiento relativo
            $v['aeat_id_type'] ?? '01', $v['aeat_codigo_pais'] ?? 'ES'
        );
    }
}
