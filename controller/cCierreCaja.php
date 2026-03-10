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
// Navegación Global
if (isset($_REQUEST['salir'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irTPV']) || isset($_REQUEST['irInicio'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPrivado';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irMiPerfil'])) {
    $_SESSION['paginaEnCurso'] = 'MiPerfil';
    header('Location: index.php');
    exit;
}

require_once 'model/CierreFiscalPDO.php';
require_once 'model/CajaTurnoPDO.php';
require_once 'model/CajaDeudaPDO.php';
require_once 'model/VentaPDO.php';

// 1. Definir funciones auxiliares
if (!function_exists('calcularResumenCaja')) {
    function calcularResumenCaja($ventas)
    {
        $r = [
            'totalVentas'     => count($ventas),
            'totalEfectivo'   => 0.0,
            'totalTarjeta'    => 0.0,
            'totalBizum'      => 0.0,
            'totalFinanciado' => 0.0,
            'totalIVA'        => 0.0,
            'totalBruto'      => 0.0,
        ];

        foreach ($ventas as $v) {
            $r['totalBruto'] += (float)$v['total'];
            $r['totalIVA']   += (float)$v['iva_amt'];
            $m = $v['metodo_pago'];
            if ($m === 'efectivo') $r['totalEfectivo'] += (float)$v['total'];
            elseif ($m === 'tarjeta') $r['totalTarjeta'] += (float)$v['total'];
            elseif ($m === 'bizum') $r['totalBizum'] += (float)$v['total'];
            elseif ($m === 'financiado') $r['totalFinanciado'] += (float)$v['total'];
        }
        return $r;
    }
}

// 2. Obtener datos base
$turnoActual = CajaTurnoPDO::obtenerTurnoAbierto();
if ($turnoActual) {
    $ventasHoy = VentaPDO::obtenerVentasPorTurno((int)$turnoActual['id']);
} else {
    $ventasHoy = VentaPDO::obtenerVentasHoy();
}
$resumen = calcularResumenCaja($ventasHoy);

// 3. Procesar acciones de apertura y retiradas
if (isset($_POST['abrirCaja']) && !$turnoActual) {
    $fondoInicialInput = max(0, (float)($_POST['fondoInicial'] ?? 0));
    CajaTurnoPDO::abrirTurno($_SESSION['usuarioActualTPV']->getId(), $fondoInicialInput);
    $turnoActual = CajaTurnoPDO::obtenerTurnoAbierto();
}

if (isset($_POST['registrarRetiro']) && $turnoActual) {
    $importe = max(0, (float)($_POST['importeRetiro'] ?? 0));
    $concepto = trim($_POST['conceptoRetiro'] ?? '');
    if ($importe > 0) {
        CajaTurnoPDO::registrarRetiro(
            (int)$turnoActual['id'],
            $_SESSION['usuarioActualTPV']->getId(),
            $importe,
            $concepto
        );
        $turnoActual = CajaTurnoPDO::obtenerTurnoAbierto();
    }
}

// 4. Calcular estado esperado de la caja
$fondoInicial = $turnoActual ? (float)$turnoActual['fondo_inicial'] : 0.0;
$totalRetirado = $turnoActual ? (float)$turnoActual['total_retirado'] : 0.0;
$esperadoEfectivoTurno = max(0, $fondoInicial + $resumen['totalEfectivo'] - $totalRetirado);

// 5. Procesar CIERRES
// A. Cierre de Turno (Solo el turno actual)
if (isset($_POST['doCierreTurno'])) {
    if ($turnoActual) {
        $realEfectivoForm = max(0, (float)($_POST['realEfectivo'] ?? 0));
        $fondoSiguiente = max(0, (float)($_POST['fondoSiguiente'] ?? 0));

        CajaTurnoPDO::cerrarTurno(
            (int)$turnoActual['id'],
            $_SESSION['usuarioActualTPV']->getId(),
            $realEfectivoForm,
            $fondoSiguiente
        );
        $turnoActual = null;
        $mensajeExito = "Turno de caja cerrado correctamente.";
    }
}

// B. Cierre de Caja (Cierre definitivo de la jornada fiscal)
if (isset($_POST['doCierreZ'])) {
    $resumenZ = CierreFiscalPDO::obtenerResumenParaCierre();

    if ($resumenZ['total_general'] > 0 || $turnoActual) {
        $totalEfectivo = (float)($resumenZ['total_efectivo'] ?? 0);
        $totalTarjeta  = (float)($resumenZ['total_tarjeta'] ?? 0);
        $totalBizum    = (float)($resumenZ['total_bizum'] ?? 0);
        $totalFinan    = (float)($resumenZ['total_financiado'] ?? 0);
        $totalGeneral  = (float)($resumenZ['total_general'] ?? 0);

        // Registrar el cierre fiscal
        $idZ = CierreFiscalPDO::realizarCierre(
            $_SESSION['usuarioActualTPV']->getId(),
            $totalEfectivo,
            $totalTarjeta,
            $totalBizum,
            $totalFinan,
            $totalGeneral
        );

        // Si hay un turno abierto, también lo cerramos
        if ($turnoActual) {
            $realEfectivoForm = max(0, (float)($_POST['realEfectivo'] ?? 0));
            $fondoSiguiente = max(0, (float)($_POST['fondoSiguiente'] ?? 0));

            CajaTurnoPDO::cerrarTurno(
                (int)$turnoActual['id'],
                $_SESSION['usuarioActualTPV']->getId(),
                $realEfectivoForm,
                $fondoSiguiente
            );
            $turnoActual = null;
        }

        $mensajeExito = "Cierre de caja realizado correctamente. Jornada fiscal concluida.";
    } else {
        $mensajeError = "No hay ventas pendientes de cierre de caja.";
    }
}

$esAdmin = $_SESSION['usuarioActualTPV']->getRol() === 'admin';
$_SESSION['paginaEnCurso'] = 'cierreCaja';

$avCierreCaja = [
    'nombre_completo' => $_SESSION['usuarioActualTPV']->getNombreCompleto(),
    'rol'             => $_SESSION['usuarioActualTPV']->getRol(),
    'esAdmin'         => $esAdmin ?? false,
    'ventas'          => $ventasHoy,
    'resumen'         => $resumen,
    'fecha'           => date('d/m/Y'),
    'mensajeExito'    => $mensajeExito ?? null,
    'turno'           => $turnoActual,
    'fondoInicial'    => $fondoInicial,
    'totalRetirado'   => $totalRetirado,
    'esperadoTurno'   => $esperadoEfectivoTurno,
];

// Reusamos el layout pero asignamos $avInicioPrivado para las variables JS
$avInicioPrivado = $avCierreCaja;

require_once $view['layout'];
