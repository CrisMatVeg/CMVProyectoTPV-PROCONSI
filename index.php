<?php
ob_start();
date_default_timezone_set('Europe/Madrid');

require_once("./config/confAPP.php");
require_once("./config/confDBPDO.php");
require_once("./model/Usuario.php");
require_once("./core/Language.php");
session_start();

// Determinar idioma (Prioridad: URL > Session > Usuario Logueado > Default 'es')
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en', 'fr', 'it', 'de', 'eu', 'ca', 'gl', 'ru', 'zh', 'ja'])) {
    $_SESSION['lang'] = $_GET['lang'];
    if (isset($_SESSION['usuarioActualTPV'])) {
        require_once("./model/UsuarioPDO.php");
        UsuarioPDO::cambiarIdioma($_SESSION['usuarioActualTPV']->getId(), $_GET['lang']);
        $_SESSION['usuarioActualTPV']->setIdioma($_GET['lang']);
    }
}
$lang = $_SESSION['lang'] ?? (isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getIdioma() : 'es');
Language::init($lang);

// Generar Token CSRF global si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['paginaEnCurso']) || $_SESSION['paginaEnCurso'] === 'error') {
    $_SESSION['paginaEnCurso'] = 'inicioPublico';
}

// Global Menu Redirect
if (isset($_GET['menu']) && isset($controller[$_GET['menu']])) {
    // Si es una página privada y no está logueado, ignorar (o redirigir a login)
    $paginasPublicas = ['inicioPublico', 'Login', 'RecuperarPassword', 'RestablecerPassword'];
    if (in_array($_GET['menu'], $paginasPublicas) || isset($_SESSION['usuarioActualTPV'])) {
        $_SESSION['paginaEnCurso'] = $_GET['menu'];
    }
}

// NAVIGATION: Handle global navigation requests before loading controllers
if (isset($_SESSION['usuarioActualTPV'])) {
    // Centralizar estado de caja para toda la App
    require_once 'model/CajaTurnoPDO.php';
    // CajaTurnoPDO::verificarYRealizarCierreAutomatico();

    // [NUEVO] Procesar apertura de caja ANTES de obtener el turno abierto
    // Esto evita que el usuario tenga que pulsar dos veces para ver el TPV abierto
    if (isset($_POST['abrirCaja'])) {
        // [NUEVO] Bloquear apertura si hay arqueos pendientes
        $pendientes = CajaTurnoPDO::obtenerTurnosPendientesArqueo();
        if (!empty($pendientes)) {
            $_SESSION['mensajeErrorCaja'] = "No puedes abrir un nuevo turno porque existen arqueos pendientes de d&iacute;as anteriores. Por favor, resu&eacute;lvelos primero.";
        } else {
            $fondoInicial = (float)($_POST['fondoInicial'] ?? 0);
            
            if ($fondoInicial <= 0) {
                $_SESSION['mensajeErrorCaja'] = L('cash_open_error_zero');
                $_SESSION['paginaEnCurso'] = 'inicioPrivado';
                header('Location: index.php');
                exit;
            } else {
                CajaTurnoPDO::abrirTurno($_SESSION['usuarioActualTPV']->getId(), $fondoInicial);
                LogPDO::addLog('APERTURA_CAJA', "Apertura de caja con fondo inicial de " . number_format($fondoInicial, 2, ',', '.') . "€");

                // [NUEVO] Redirigir explícitamente al TPV tras abrir la caja para evitar quedarse en el Dashboard
                $_SESSION['paginaEnCurso'] = 'inicioPrivado';
                header('Location: index.php');
                exit;
            }
        }
    }

    $turnoCajaGlobal = CajaTurnoPDO::obtenerTurnoAbierto();
    $_SESSION['cajaAbierta'] = (bool)$turnoCajaGlobal;
    $_SESSION['turnoSesion'] = $turnoCajaGlobal;

    if (isset($_REQUEST['salir'])) {
        LogPDO::addLog('LOGOUT', 'El usuario ha cerrado la sesión');
        session_destroy();
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irDashboard'])) {
        $_SESSION['paginaEnCurso'] = 'Dashboard';
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irTPV']) && !isset($_POST['abrirCaja'])) {
        $_SESSION['paginaEnCurso'] = 'inicioPrivado';
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irMiPerfil'])) {
        $_SESSION['paginaEnCurso'] = 'MiPerfil';
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irHistorial']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['paginaEnCurso'] = 'Historial';
        $params = $_GET;
        unset($params['irHistorial']);
        $qs = http_build_query($params);
        header('Location: index.php' . ($qs ? '?' . $qs : ''));
        exit;
    }
    if (isset($_REQUEST['irCierreCaja']) && ($_SESSION['usuarioActualTPV']->tienePermiso('cerrar_caja') || $_SESSION['usuarioActualTPV']->tienePermiso('cerrar_turno')) 
        && !isset($_POST['doCierre']) && !isset($_POST['doCierreZ']) && !isset($_POST['doCierreTurno']) && !isset($_POST['abrirCaja']) && !isset($_POST['realizarArqueoPendiente']) && !isset($_REQUEST['accion'])) {
        $_SESSION['paginaEnCurso'] = 'cierreCaja';
        header('Location: index.php');
        exit;
    }
    if (isset($_REQUEST['irProveedores']) && $_SESSION['usuarioActualTPV']->tienePermiso('gestionar_proveedores') && !isset($_REQUEST['accion'])) {
        if ($_SESSION['paginaEnCurso'] !== 'Proveedores') {
            $_SESSION['paginaEnCurso'] = 'Proveedores';
            header('Location: index.php');
            exit;
        }
    }
    if (isset($_REQUEST['irCompras']) && $_SESSION['usuarioActualTPV']->tienePermiso('gestionar_inventario') && !isset($_REQUEST['accion'])) {
        if ($_SESSION['paginaEnCurso'] !== 'Compras') {
            $_SESSION['paginaEnCurso'] = 'Compras';
            header('Location: index.php');
            exit;
        }
    }
    if (isset($_REQUEST['irConfiguracion']) && $_SESSION['usuarioActualTPV']->tienePermiso('gestionar_configuracion') && !isset($_REQUEST['accion'])) {
        if ($_SESSION['paginaEnCurso'] !== 'Configuracion') {
            $_SESSION['paginaEnCurso'] = 'Configuracion';
            header('Location: index.php');
            exit;
        }
    }
    if (isset($_REQUEST['irUsuarios']) && $_SESSION['usuarioActualTPV']->tienePermiso('gestionar_usuarios') && !isset($_REQUEST['accion'])) {
        if ($_SESSION['paginaEnCurso'] !== 'Usuarios') {
            $_SESSION['paginaEnCurso'] = 'Usuarios';
            header('Location: index.php');
            exit;
        }
    }

    if (isset($_REQUEST['irAnalitica']) && $_SESSION['usuarioActualTPV']->tienePermiso('ver_analitica') && !isset($_REQUEST['accion'])) {
        if ($_SESSION['paginaEnCurso'] !== 'Analitica') {
            $_SESSION['paginaEnCurso'] = 'Analitica';
            header('Location: index.php');
            exit;
        }
    }
}

require_once($controller[$_SESSION['paginaEnCurso']]);
