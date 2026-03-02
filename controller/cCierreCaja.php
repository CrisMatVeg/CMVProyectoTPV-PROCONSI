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

// Obtener turno actual (si existe)
$turnoActual = CajaTurnoPDO::obtenerTurnoAbierto();

// Apertura de caja
if (isset($_POST['abrirCaja']) && !$turnoActual) {
    $fondoInicial = max(0, (float)($_POST['fondoInicial'] ?? 0));
    $idTurno = CajaTurnoPDO::abrirTurno($_SESSION['usuarioActualTPV']->getId(), $fondoInicial);
    $turnoActual = CajaTurnoPDO::obtenerTurnoAbierto();
}

// Registrar retirada de efectivo durante el turno
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
        // Refrescar datos del turno
        $turnoActual = CajaTurnoPDO::obtenerTurnoAbierto();
    }
}

// Procesar Cierre Definitivo
if (isset($_POST['doCierre'])) {
    $totalEfectivoReal = max(0, (float)$_POST['realEfectivo']);
    $totalTarjeta      = max(0, (float)$_POST['totalTarjeta']); // Tarjeta suele ser lo que dice el TPV (datáfono externo)
    $totalGeneral      = $totalEfectivoReal + $totalTarjeta;

    // Registrar cierre fiscal (Reporte Z)
    $idCierre = CierreFiscalPDO::realizarCierre(
        $_SESSION['usuarioActualTPV']->getId(),
        $totalEfectivoReal,
        $totalTarjeta,
        $totalGeneral
    );

    // Si hay faltante (real < esperado), registrar deuda de caja
    $diferencia = $totalEfectivoReal - $esperadoEfectivoTurno;
    if ($diferencia < -0.009) { // margen pequeño para decimales
        $importeDeuda = abs($diferencia);
        CajaDeudaPDO::crearDeuda(
            $idCierre,
            $_SESSION['usuarioActualTPV']->getId(),
            $importeDeuda,
            'Faltante de caja detectado en cierre'
        );
    }

    // Cierre de turno de caja (si existe)
    if ($turnoActual) {
        $fondoSiguiente = max(0, (float)($_POST['fondoSiguiente'] ?? 0));
        CajaTurnoPDO::cerrarTurno(
            (int)$turnoActual['id'],
            $_SESSION['usuarioActualTPV']->getId(),
            $totalEfectivoReal,
            $fondoSiguiente
        );
        $turnoActual = null;
    }

    $mensajeExito = "Cierre de caja registrado correctamente. Reporte Z generado.";
}

$esAdmin = $_SESSION['usuarioActualTPV']->getRol() === 'admin';

// Registrar la página actual en sesión (para data-page en el layout)
$_SESSION['paginaEnCurso'] = 'cierreCaja';

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

// Cálculo de efectivo esperado según turno
$fondoInicial = $turnoActual ? (float)$turnoActual['fondo_inicial'] : 0.0;
$totalRetirado = $turnoActual ? (float)$turnoActual['total_retirado'] : 0.0;
$esperadoEfectivoTurno = max(0, $fondoInicial + $resumen['totalEfectivo'] - $totalRetirado);

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
