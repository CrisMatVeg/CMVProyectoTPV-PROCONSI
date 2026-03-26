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
        }
        return $r;
    }
}

// 2. Obtener datos base
$turnoActual = CajaTurnoPDO::obtenerTurnoAbierto();
$avCierreCaja = []; // Inicializar para evitar advertencias de linter

// Si estamos gestionando un arqueo pendiente (ya sea al empezar o al confirmar el cierre final)
if (isset($_POST['idTurnoPendiente']) && (int)$_POST['idTurnoPendiente'] > 0) {
    $idPendiente = (int)$_POST['idTurnoPendiente'];
    // Buscamos ese turno específico para trabajar sobre él en lugar del turno abierto por defecto
    $q = DBPDO::ejecutarConsulta("SELECT * FROM caja_turnos WHERE id = :id", [':id' => $idPendiente]);
    $turnoRecuperado = $q->fetch(PDO::FETCH_ASSOC);
    if ($turnoRecuperado) {
        $turnoActual = $turnoRecuperado;
        $avCierreCaja['modoEdicionPendiente'] = true;
    }
}

// (Data fetching moved below closure processing, but we need an initial fetch for intermediate calculations)
$ventasIniciales = $turnoActual ? VentaPDO::obtenerVentasPorTurno((int)$turnoActual['id']) : [];
$resumen = calcularResumenCaja($ventasIniciales);


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
        LogPDO::addLog('MOVIMIENTO_DINERO', "Retiro/Gasto de caja: " . number_format($importe, 2, ',', '.') . "€ - Concepto: $concepto");
        $turnoActual = CajaTurnoPDO::obtenerTurnoAbierto();
    }
}

if (isset($_POST['registrarIngreso']) && $turnoActual) {
    $importe = max(0, (float)($_POST['importeIngreso'] ?? 0));
    $concepto = trim($_POST['conceptoIngreso'] ?? '');
    if ($importe > 0) {
        CajaTurnoPDO::registrarIngreso(
            (int)$turnoActual['id'],
            $_SESSION['usuarioActualTPV']->getId(),
            $importe,
            $concepto
        );
        LogPDO::addLog('MOVIMIENTO_DINERO', "Ingreso manual a caja: " . number_format($importe, 2, ',', '.') . "€ - Concepto: $concepto");
        $turnoActual = CajaTurnoPDO::obtenerTurnoAbierto();
    }
}

// 4. Calcular estado esperado de la caja
$fondoInicial = $turnoActual ? (float)$turnoActual['fondo_inicial'] : 0.0;
$totalRetirado = $turnoActual ? (float)$turnoActual['total_retirado'] : 0.0;
$totalIngresado = $turnoActual ? (float)$turnoActual['total_ingresado'] : 0.0;
$esperadoEfectivoTurno = max(0, $fondoInicial + $resumen['totalEfectivo'] + $totalIngresado - $totalRetirado);

// 4.1 Permisos para cierres
$canCerrarTurno = $_SESSION['usuarioActualTPV']->tienePermiso('cerrar_turno');
$canCerrarCaja = $_SESSION['usuarioActualTPV']->tienePermiso('cerrar_caja');

// 5. Procesar CIERRES
// A. Cierre de Turno (Solo el turno actual)
if (isset($_POST['doCierreTurno'])) {
    if (!$canCerrarTurno) {
        $mensajeError = "No tienes permiso para cerrar el turno.";
    } elseif ($turnoActual) {
        $realEfectivoForm = max(0, (float)($_POST['realEfectivo'] ?? 0));
        $fondoSiguiente = max(0, (float)($_POST['fondoSiguiente'] ?? 0));

        CajaTurnoPDO::cerrarTurno(
            (int)$turnoActual['id'],
            $_SESSION['usuarioActualTPV']->getId(),
            $realEfectivoForm,
            $fondoSiguiente
        );

        // [NUEVO] Si era un arqueo pendiente y hay OTRO turno abierto, transferir el fondo
        if (isset($avCierreCaja['modoEdicionPendiente']) && $avCierreCaja['modoEdicionPendiente']) {
            $idOriginal = (int)$turnoActual['id'];
            $transferido = CajaTurnoPDO::sumarFondoCajaAbierta($fondoSiguiente, $_SESSION['usuarioActualTPV']->getId());
            if ($transferido) {
                $mensajeExito = "Arqueo pendiente resuelto. El fondo de " . number_format($fondoSiguiente, 2, ',', '.') . " € se ha sumado a la caja abierta actual.";
            } else {
                $mensajeExito = "Arqueo pendiente resuelto correctamente.";
            }
        } else {
            $mensajeExito = "Turno de caja cerrado correctamente.";
        }

        LogPDO::addLog('CIERRE_TURNO', "Cierre de turno realizado. Efectivo real: " . number_format($realEfectivoForm, 2, ',', '.') . "€, Fondo siguiente: " . number_format($fondoSiguiente, 2, ',', '.') . "€");

        $turnoActual = null;
    }
}

// B. Cierre de Caja (Cierre definitivo de la jornada fiscal)
if (isset($_POST['doCierreZ'])) {
    $db = DBPDO::getPDO();
    $db->beginTransaction();
    try {
        if (!$canCerrarCaja) {
            $mensajeError = "No tienes permiso para realizar el cierre de caja.";
        } else {
            $resumenParaZ = CierreFiscalPDO::obtenerResumenParaCierre();

            if (($resumenParaZ['total_general'] ?? $resumenParaZ['totalBruto'] ?? 0) > 0 || $turnoActual) {
                // ... (rest of the logic inside if)
                // (Note: identifying lines 152 to 184)
                $totalEfectivo = (float)($resumenParaZ['total_efectivo'] ?? $resumenParaZ['totalEfectivo'] ?? 0);
                $totalTarjeta  = (float)($resumenParaZ['total_tarjeta'] ?? $resumenParaZ['totalTarjeta'] ?? 0);
                $totalBizum    = (float)($resumenParaZ['total_bizum'] ?? $resumenParaZ['totalBizum'] ?? 0);
                $totalGeneral  = (float)($resumenParaZ['total_general'] ?? $resumenParaZ['totalBruto'] ?? 0);

                if ($turnoActual) {
                    $realEfectivoForm = max(0, (float)($_POST['realEfectivo'] ?? 0));
                    $fondoSiguiente   = max(0, (float)($_POST['fondoSiguiente'] ?? 0));

                    // 1. Primero cerrar el turno
                    CajaTurnoPDO::cerrarTurno(
                        (int)$turnoActual['id'],
                        $_SESSION['usuarioActualTPV']->getId(),
                        $realEfectivoForm,
                        $fondoSiguiente
                    );
                }

                // 2. Luego hacer el cierre Z
                $idZ = CierreFiscalPDO::realizarCierre(
                    $_SESSION['usuarioActualTPV']->getId(),
                    $totalEfectivo,
                    $totalTarjeta,
                    $totalBizum,
                    $totalGeneral,
                    $turnoActual ? (int)$turnoActual['id'] : null
                );

                if ($turnoActual) {
                    if (isset($avCierreCaja['modoEdicionPendiente']) && $avCierreCaja['modoEdicionPendiente']) {
                        $transferido = CajaTurnoPDO::sumarFondoCajaAbierta($fondoSiguiente, $_SESSION['usuarioActualTPV']->getId());
                        $mensajeExito = $transferido
                            ? "Cierre de arqueo pendiente realizado. Fondo de " . number_format($fondoSiguiente, 2, ',', '.') . " € transferido a la caja activa."
                            : "Cierre de arqueo pendiente realizado.";
                    } else {
                        $mensajeExito = "Cierre de caja realizado correctamente. Jornada fiscal concluida.";
                    }

                    LogPDO::addLog('CIERRE_CAJA', "Cierre de caja Z realizado. Total: " . number_format($totalGeneral, 2, ',', '.') . "€");
                    $turnoActual = null;
                    $avCierreCaja['modoEdicionPendiente'] = false;
                }
            } else {
                // Solo mostramos error si tampoco hay arqueos pendientes
                $pendientes = CajaTurnoPDO::obtenerTurnosPendientesArqueo();
                if (empty($pendientes)) {
                    $mensajeError = "No hay ventas pendientes de cierre de caja.";
                }
            }
        }
        $db->commit();
    } catch (\Throwable $e) {
        $db->rollBack();
        $mensajeError = "Error al realizar el cierre: " . $e->getMessage();
    }
}

// 6. RE-OBTENER DATOS PARA LA VISTA (Para que reflejen el estado tras el cierre/retiro)
$isPendingMod = ($avCierreCaja['modoEdicionPendiente'] ?? false);

if ($isPendingMod && $turnoActual) {
    // Si estamos en modo arqueo de turno antiguo, mantenemos ESE turno para la vista
    $turnoAbiertoFinal = $turnoActual;
} else {
    $turnoAbiertoFinal = CajaTurnoPDO::obtenerTurnoAbierto();
}

if ($turnoAbiertoFinal) {
    $ventasHoy = VentaPDO::obtenerVentasPorTurno((int)$turnoAbiertoFinal['id']);
    $fondoInicial = (float)$turnoAbiertoFinal['fondo_inicial'];
    $totalRetirado = (float)$turnoAbiertoFinal['total_retirado'];
    $totalIngresado = (float)$turnoAbiertoFinal['total_ingresado'];
} else {
    // Si no hay turno abierto e intentamos mostrar el último turno cerrado de hoy 
    // para evitar mezclar ventas de distintas sesiones en el mismo día.
    $ultimoCerradoHoy = CajaTurnoPDO::obtenerUltimoTurnoCerrado();
    if ($ultimoCerradoHoy && date('Y-m-d', strtotime($ultimoCerradoHoy['fecha_apertura'])) === date('Y-m-d')) {
        $ventasHoy = VentaPDO::obtenerVentasPorTurno((int)$ultimoCerradoHoy['id']);
    } else {
        $ventasHoy = [];
    }
    $fondoInicial = 0;
    $totalRetirado = 0;
    $totalIngresado = 0;
}
$resumen = calcularResumenCaja($ventasHoy);
$esperadoEfectivoTurno = max(0, $fondoInicial + $resumen['totalEfectivo'] + $totalIngresado - $totalRetirado);
$ultimoFondoSugerido = CajaTurnoPDO::obtenerUltimoFondoSugerido();

$esAdmin = $_SESSION['usuarioActualTPV']->getRol() === 'admin';
$turnoActual = $turnoAbiertoFinal; // Actualizar para la vista
$_SESSION['paginaEnCurso'] = 'cierreCaja';

// 6. Detectar si la próxima apertura será un relevo o un nuevo día
$ultimoCerrado = CajaTurnoPDO::obtenerUltimoTurnoCerrado();
$esRelevo = false;
if ($ultimoCerrado && is_null($ultimoCerrado['num_z'])) {
    $esRelevo = true;
}

$avCierreCaja = array_merge($avCierreCaja, [
    'nombre_completo' => $_SESSION['usuarioActualTPV']->getNombreCompleto(),
    'rol'             => $_SESSION['usuarioActualTPV']->getRol(),
    'esAdmin'         => $esAdmin ?? false,
    'ventas'          => $ventasHoy,
    'resumen'         => $resumen,
    'fecha'           => date('d/m/Y'),
    'mensajeExito'    => $mensajeExito ?? null,
    'turno'           => $turnoActual,
    'pendientesArqueo' => CajaTurnoPDO::obtenerTurnosPendientesArqueo(),
    'fondoInicial'    => $fondoInicial,
    'totalRetirado'   => $totalRetirado,
    'esperadoTurno'   => $esperadoEfectivoTurno,
    'mensajeError'    => $mensajeError ?? null,
    'canCerrarTurno'  => $canCerrarTurno,
    'canCerrarCaja'   => $canCerrarCaja,
    'fondoSugerido'   => $ultimoFondoSugerido,
    'esRelevo'        => $esRelevo,
]);

// Reusamos el layout pero asignamos $avInicioPrivado para las variables JS
$avInicioPrivado = $avCierreCaja;

require_once $view['layout'];
