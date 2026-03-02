<?php
/**
 * Clase: VentaPDO
 * Gestiona la persistencia de las ventas mediante DBPDO.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
require_once 'DBPDO.php';
require_once 'Venta.php';

class VentaPDO {

    public static function obtenerSiguienteTicket(): int {
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
    public static function guardarVenta(array $datos, int $idUsuario): int {
        $numTicket = self::obtenerSiguienteTicket();

        $subtotal    = round((float)$datos['subtotal'], 2);
        $descPct     = round((float)($datos['descuentoPct'] ?? 0), 2);
        $descAmt     = round($subtotal * $descPct / 100, 2);
        $base        = round($subtotal - $descAmt, 2);
        
        // Si no viene el total, lo calculamos (Base + 21% IVA estándar)
        if (!isset($datos['total']) || (float)$datos['total'] <= 0) {
            $total = round($base * 1.21, 2);
        } else {
            $total = round((float)$datos['total'], 2);
        }
        
        $ivaAmt = round($total - $base, 2);

        $tipoCliente    = in_array($datos['tipoCliente'] ?? '', ['particular', 'empresa']) ? $datos['tipoCliente'] : 'particular';
        $nombreCliente  = isset($datos['nombreCliente']) ? mb_substr(trim($datos['nombreCliente']), 0, 100) : null;
        $nifCliente     = isset($datos['nifCliente']) ? mb_substr(trim($datos['nifCliente']), 0, 20) : null;
        $metodoPago     = in_array($datos['metodoPago'] ?? '', ['efectivo', 'tarjeta']) ? $datos['metodoPago'] : 'efectivo';
        $idCliente      = isset($datos['idCliente']) ? (int)$datos['idCliente'] : null;
        $efectivoRecibido = ($metodoPago === 'efectivo') ? round((float)($datos['efectivo']['recibido'] ?? $datos['efectivoRecibido'] ?? 0), 2) : 0;

        $sqlVenta = "INSERT INTO ventas 
            (numero_ticket, fecha, id_usuario, id_cliente, tipo_cliente, nombre_cliente, nif_cliente, metodo_pago,
             subtotal, descuento_pct, descuento_amt, base_imponible, iva_amt, total, efectivo_recibido)
            VALUES (:ticket, NOW(), :usuario, :cliente, :tipo, :nombre, :nif, :metodo,
             :subtotal, :descPct, :descAmt, :base, :ivaAmt, :total, :efectivo)";

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
            ':base'     => $base,
            ':ivaAmt'   => $ivaAmt,
            ':total'    => $total,
            ':efectivo' => $efectivoRecibido
        ];

        DBPDO::ejecutarConsulta($sqlVenta, $paramsVenta);

        $qId = DBPDO::ejecutarConsulta("SELECT id FROM ventas WHERE numero_ticket = :t", [':t' => $numTicket]);
        $rowId = $qId->fetch(PDO::FETCH_ASSOC);
        $idVenta = (int)$rowId['id'];

        foreach ($datos['lineas'] as $linea) {
            $precioUnit = round((float)$linea['price'], 2);
            $qty        = (int)$linea['qty'];
            $totalLinea = round($precioUnit * $qty, 2);
            $ivaAplicado = (float)($linea['iva'] ?? 21.00);
            
            // Obtener garantía del producto (snapshot)
            $mesesGarantia = 24; // Valor por defecto
            if (isset($linea['id'])) {
                require_once 'ProductoPDO.php';
                $prodData = ProductoPDO::obtenerProductoPorId((int)$linea['id']);
                if ($prodData) {
                    $mesesGarantia = (int)($prodData['meses_garantia'] ?? 24);
                }
            }

            $sqlLinea = "INSERT INTO lineas_venta
                (id_venta, id_producto, nombre_producto, codigo_producto, precio_unitario, iva_aplicado, cantidad, meses_garantia, total_linea, numero_serie)
                VALUES (:venta, :prod, :nombre, :codigo, :precio, :iva, :qty, :garantia, :total, :serial)";

            DBPDO::ejecutarConsulta($sqlLinea, [
                ':venta'  => $idVenta,
                ':prod'   => $linea['id'] ?? null,
                ':nombre' => mb_substr($linea['name'], 0, 100),
                ':codigo' => mb_substr($linea['referencia'] ?? $linea['codigo'] ?? '', 0, 50),
                ':precio' => $precioUnit,
                ':iva'    => $ivaAplicado,
                ':qty'    => $qty,
                ':garantia' => $mesesGarantia,
                ':total'  => $totalLinea,
                ':serial' => isset($linea['serials']) ? json_encode($linea['serials']) : null
            ]);

            // Gestión de Números de Serie si vienen en la línea
            if (isset($linea['numeros_serie']) && is_array($linea['numeros_serie'])) {
                // Obtener el ID de la línea recién insertada
                $qlId = DBPDO::ejecutarConsulta("SELECT id FROM lineas_venta WHERE id_venta = :v ORDER BY id DESC LIMIT 1", [':v' => $idVenta]);
                $idLineaVenta = $qlId->fetch(PDO::FETCH_ASSOC)['id'];

                foreach ($linea['numeros_serie'] as $nsId) {
                    DBPDO::ejecutarConsulta("INSERT INTO lineas_serie_venta (id_linea_venta, id_numero_serie) VALUES (:lv, :ns)", [':lv' => $idLineaVenta, ':ns' => $nsId]);
                    DBPDO::ejecutarConsulta("UPDATE numeros_serie SET estado = 'vendido' WHERE id = :ns", [':ns' => $nsId]);
                }
            }

            if (isset($linea['id'])) {
                require_once 'ProductoPDO.php';
                ProductoPDO::reducirStock((int)$linea['id'], $qty);
            }
        }

        return $numTicket;
    }

    public static function obtenerVentasHoy(): array {
        $sql = "SELECT v.*, u.nombre AS nombre_cajero 
                FROM ventas v 
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                WHERE DATE(v.fecha) = CURDATE() ORDER BY v.fecha ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerVentaPorTicket(int $numTicket): ?array {
        $sqlVenta = "SELECT v.*, u.nombre AS nombre_cajero
                     FROM ventas v
                     LEFT JOIN usuarios u ON v.id_usuario = u.id
                     WHERE v.numero_ticket = :t";
        $q = DBPDO::ejecutarConsulta($sqlVenta, [':t' => $numTicket]);
        $venta = $q->fetch(PDO::FETCH_ASSOC);
        if (!$venta) return null;

        $sqlLineas = "SELECT lv.*, GROUP_CONCAT(ns.numero_serie) as numeros_serie
                      FROM lineas_venta lv
                      LEFT JOIN lineas_serie_venta lsv ON lv.id = lsv.id_linea_venta
                      LEFT JOIN numeros_serie ns ON lsv.id_numero_serie = ns.id
                      WHERE lv.id_venta = :v 
                      GROUP BY lv.id
                      ORDER BY lv.id ASC";
        $qL = DBPDO::ejecutarConsulta($sqlLineas, [':v' => $venta['id']]);
        $venta['lineas'] = $qL->fetchAll(PDO::FETCH_ASSOC);

        return $venta;
    }

    public static function buscarVentas(string $desde, string $hasta, ?int $idUsuario = null): array {
        $sql = "SELECT v.*, u.nombre as nombre_cajero 
                FROM ventas v
                LEFT JOIN usuarios u ON v.id_usuario = u.id
                WHERE DATE(v.fecha) BETWEEN :desde AND :hasta";
        
        $params = [':desde' => $desde, ':hasta' => $hasta];
        
        if ($idUsuario) {
            $sql .= " AND v.id_usuario = :usuario";
            $params[':usuario'] = $idUsuario;
        }
        
        $sql .= " ORDER BY v.fecha DESC";
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marca una línea de venta como devuelta y repone stock.
     */
    public static function devolverLinea(int $idLinea, ?string $motivo = null): bool {
        // 1. Obtener datos de la línea
        $sql = "SELECT id_producto, cantidad, devuelta, id_venta FROM lineas_venta WHERE id = :id";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idLinea]);
        $l = $q->fetch(PDO::FETCH_ASSOC);

        if (!$l || $l['devuelta']) return false;

        // 2. Marcar como devuelta con motivo y fecha
        $sqlUpd = "UPDATE lineas_venta 
                   SET devuelta = 1, motivo_devolucion = :motivo, fecha_devolucion = NOW() 
                   WHERE id = :id";
        DBPDO::ejecutarConsulta($sqlUpd, [
            ':id' => $idLinea,
            ':motivo' => $motivo ? mb_substr($motivo, 0, 255) : 'Devolución estándar'
        ]);

        // 3. Reponer stock
        if ($l['id_producto']) {
            require_once 'ProductoPDO.php';
            ProductoPDO::aumentarStock((int)$l['id_producto'], (int)$l['cantidad']);
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

            if ($stats 
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
    public static function devolverVenta(int $numTicket, ?string $motivo = null): bool {
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
    public static function obtenerKPIs(string $desde, string $hasta): array {
        $sql = "SELECT 
                    COUNT(v.id) as total_tickets,
                    COALESCE(SUM(v.total), 0) as total_ventas,
                    COALESCE(SUM(v.base_imponible), 0) as total_base,
                    COALESCE((SELECT SUM((lv.precio_unitario - p.precio_coste) * lv.cantidad)
                     FROM lineas_venta lv
                     JOIN productos p ON lv.id_producto = p.id
                     JOIN ventas v2 ON lv.id_venta = v2.id
                     WHERE v2.estado = 'completada' 
                     AND DATE(v2.fecha) BETWEEN :desde AND :hasta), 0) as margen_estimado
                FROM ventas v
                WHERE v.estado = 'completada' AND DATE(v.fecha) BETWEEN :desde2 AND :hasta2";
        
        $params = [
            ':desde'  => $desde, ':hasta'  => $hasta,
            ':desde2' => $desde, ':hasta2' => $hasta
        ];
        
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas agrupadas por método de pago.
     */
    public static function obtenerVentasPorMetodo(string $desde, string $hasta): array {
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
    public static function obtenerVentasPorFecha(string $desde, string $hasta): array {
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
    public static function obtenerVentasPorCajero(string $desde, string $hasta): array {
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
    public static function obtenerVentasPorCategoria(string $desde, string $hasta): array {
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
    public static function obtenerTopProductos(string $desde, string $hasta, int $limite = 10): array {
        $sql = "SELECT lv.nombre_producto, lv.codigo_producto, 
                       SUM(lv.cantidad) as unidades, 
                       SUM(lv.total_linea) as total_recaudado
                FROM lineas_venta lv
                JOIN ventas v ON lv.id_venta = v.id
                WHERE v.estado = 'completada' AND lv.devuelta = 0
                AND DATE(v.fecha) BETWEEN :desde AND :hasta
                GROUP BY lv.id_producto, lv.nombre_producto, lv.codigo_producto
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
    public static function obtenerMargenesDetallados(string $desde, string $hasta): array {
        $sql = "SELECT DATE(v.fecha) as fecha,
                       SUM(lv.total_linea) as ingresos,
                       SUM(p.precio_coste * lv.cantidad) as costes,
                       SUM(lv.total_linea - (p.precio_coste * lv.cantidad)) as beneficio
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
}
