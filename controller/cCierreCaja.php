<?php
/**
 * Controller: cCierreCaja.php
 * Obtiene todas las ventas del día y las pasa a la vista de cierre.
 */

// Solo usuarios autenticados
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

// Volver al TPV
if (isset($_REQUEST['irInicio'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPrivado';
    header('Location: index.php');
    exit;
}

$esAdmin = $_SESSION['usuarioActualTPV']->getRol() === 'admin';

// Obtener ventas del día
$ventasHoy = VentaPDO::obtenerVentasHoy();

// Calcular resumen
$resumen = [
    'totalVentas'   => count($ventasHoy),
    'totalEfectivo' => 0.0,
    'totalTarjeta'  => 0.0,
    'totalIVA'      => 0.0,
    'totalBruto'    => 0.0,
];

foreach ($ventasHoy as $v) {
    $resumen['totalBruto'] += (float)$v['total'];
    $resumen['totalIVA']   += (float)$v['iva_amt'];
    if ($v['metodo_pago'] === 'efectivo') {
        $resumen['totalEfectivo'] += (float)$v['total'];
    } else {
        $resumen['totalTarjeta'] += (float)$v['total'];
    }
}

$avCierreCaja = [
    'nombre_completo' => $_SESSION['usuarioActualTPV']->getNombreCompleto(),
    'rol'             => $_SESSION['usuarioActualTPV']->getRol(),
    'esAdmin'         => $esAdmin ?? false,
    'ventas'          => $ventasHoy,
    'resumen'         => $resumen,
    'fecha'           => date('d/m/Y'),
];

// Reusamos el layout pero asignamos $avInicioPrivado para las variables JS
$avInicioPrivado = $avCierreCaja;

require_once $view['layout'];
