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

    /**
     * Obtiene el siguiente número de ticket disponible.
     * @return int
     */
    public static function obtenerSiguienteTicket(): int {
        $sql = "SELECT COALESCE(MAX(numero_ticket), 1000) + 1 AS siguiente FROM ventas";
        $q = DBPDO::ejecutarConsulta($sql);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return (int)$row['siguiente'];
    }

    /**
     * Guarda una venta completa con sus líneas en la base de datos.
     * @param array $datos Datos de la venta (del JSON del frontend)
     * @param int $idCajero ID del usuario cajero
     * @return int Número de ticket asignado
     */
    public static function guardarVenta(array $datos, int $idCajero): int {
        $numTicket = self::obtenerSiguienteTicket();

        // Calcular totales en servidor (no confiamos al 100% en los del JS)
        $subtotal    = round((float)$datos['subtotal'], 2);
        $descPct     = round((float)($datos['descuentoPct'] ?? 0), 2);
        $descAmt     = round($subtotal * $descPct / 100, 2);
        $base        = round($subtotal - $descAmt, 2);
        $ivaPct      = 21.00;
        $ivaAmt      = round($base * $ivaPct / 100, 2);
        $total       = round($base + $ivaAmt, 2);

        $tipoCliente    = in_array($datos['tipoCliente'] ?? '', ['particular', 'empresa']) ? $datos['tipoCliente'] : 'particular';
        $nombreCliente  = isset($datos['nombreCliente']) ? mb_substr(trim($datos['nombreCliente']), 0, 100) : null;
        $nifCliente     = isset($datos['nifCliente']) ? mb_substr(trim($datos['nifCliente']), 0, 20) : null;
        $metodoPago     = in_array($datos['metodoPago'] ?? '', ['efectivo', 'tarjeta']) ? $datos['metodoPago'] : 'efectivo';

        // Insertar la venta
        $sqlVenta = "INSERT INTO ventas 
            (numero_ticket, tipo_cliente, nombre_cliente, nif_cliente, metodo_pago,
             subtotal, descuento_pct, descuento_amt, base_imponible, iva_pct, iva_amt, total, id_cajero)
            VALUES (:ticket, :tipo, :nombre, :nif, :metodo,
             :subtotal, :descPct, :descAmt, :base, :ivaPct, :ivaAmt, :total, :cajero)";

        $paramsVenta = [
            ':ticket'  => $numTicket,
            ':tipo'    => $tipoCliente,
            ':nombre'  => $nombreCliente,
            ':nif'     => $nifCliente,
            ':metodo'  => $metodoPago,
            ':subtotal'=> $subtotal,
            ':descPct' => $descPct,
            ':descAmt' => $descAmt,
            ':base'    => $base,
            ':ivaPct'  => $ivaPct,
            ':ivaAmt'  => $ivaAmt,
            ':total'   => $total,
            ':cajero'  => $idCajero
        ];

        DBPDO::ejecutarConsulta($sqlVenta, $paramsVenta);

        // Obtener el ID insertado usando otra consulta
        $qId = DBPDO::ejecutarConsulta("SELECT id FROM ventas WHERE numero_ticket = :t", [':t' => $numTicket]);
        $rowId = $qId->fetch(PDO::FETCH_ASSOC);
        $idVenta = (int)$rowId['id'];

        // Insertar líneas de venta
        $sqlLinea = "INSERT INTO lineas_venta
            (id_venta, id_producto, nombre_producto, codigo_producto, precio_unitario, cantidad, total_linea)
            VALUES (:venta, :prod, :nombre, :codigo, :precio, :qty, :total)";

        foreach ($datos['lineas'] as $linea) {
            $precioUnit = round((float)$linea['price'], 2);
            $qty        = (int)$linea['qty'];
            $totalLinea = round($precioUnit * $qty, 2);

            DBPDO::ejecutarConsulta($sqlLinea, [
                ':venta'  => $idVenta,
                ':prod'   => $linea['id'] ?? null,
                ':nombre' => mb_substr($linea['name'], 0, 100),
                ':codigo' => mb_substr($linea['codigo'], 0, 50),
                ':precio' => $precioUnit,
                ':qty'    => $qty,
                ':total'  => $totalLinea
            ]);
        }

        return $numTicket;
    }

    /**
     * Devuelve todas las ventas del día actual con sus totales.
     * @return array
     */
    public static function obtenerVentasHoy(): array {
        $sql = "SELECT * FROM ventas WHERE DATE(creado_en) = CURDATE() ORDER BY creado_en ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una venta completa por número de ticket, incluyendo sus líneas.
     * @param int $numTicket
     * @return array|null
     */
    public static function obtenerVentaPorTicket(int $numTicket): ?array {
        $sqlVenta = "SELECT * FROM ventas WHERE numero_ticket = :t";
        $q = DBPDO::ejecutarConsulta($sqlVenta, [':t' => $numTicket]);
        $venta = $q->fetch(PDO::FETCH_ASSOC);
        if (!$venta) return null;

        $sqlLineas = "SELECT * FROM lineas_venta WHERE id_venta = :v ORDER BY id ASC";
        $qL = DBPDO::ejecutarConsulta($sqlLineas, [':v' => $venta['id']]);
        $venta['lineas'] = $qL->fetchAll(PDO::FETCH_ASSOC);

        return $venta;
    }
}
