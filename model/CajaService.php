<?php

require_once __DIR__ . '/../config/constantes.php';

/**
 * CajaService
 * Lógica de negocio relacionada con el cierre y resumen de caja.
 */
class CajaService
{
    public static function calcularResumenCaja(array $ventas): array
    {
        $r = [
            'totalVentas'   => 0,
            'totalEfectivo' => 0.0,
            'totalTarjeta'  => 0.0,
            'totalBizum'    => 0.0,
            'totalIVA'      => 0.0,
            'totalBruto'    => 0.0,
        ];

        foreach ($ventas as $v) {
            if (($v['estado'] ?? '') === ESTADO_VENTA_ANULADA) continue;
            $r['totalVentas']++;
            $r['totalBruto'] += (float)$v['total'];
            $r['totalIVA']   += (float)$v['iva_amt'];
            $m = $v['metodo_pago'];
            if ($m === METODO_PAGO_EFECTIVO)      $r['totalEfectivo'] += (float)$v['total'];
            elseif ($m === METODO_PAGO_TARJETA)   $r['totalTarjeta']  += (float)$v['total'];
            elseif ($m === METODO_PAGO_BIZUM)     $r['totalBizum']    += (float)$v['total'];
        }

        return $r;
    }
}
