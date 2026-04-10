<?php

/**
 * Controlador: cClientes
 * Pantalla de administración de clientes y socios.
 */

// Solo usuarios autenticados
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

// Solo administradores
if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Navegación global
if (isset($_REQUEST['salir'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irTPV'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPrivado';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irMiPerfil'])) {
    $_SESSION['paginaEnCurso'] = 'MiPerfil';
    header('Location: index.php');
    exit;
}

// Navegación básica (volver al Dashboard)
if (isset($_REQUEST['volver']) || isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

require_once 'model/ClientePDO.php';
require_once 'model/RolClientePDO.php';

// Paginación
$pag = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit = 50;
$offset = ($pag - 1) * $limit;

$totalClientes = ClientePDO::contarTodos();
$totalPaginas = ceil($totalClientes / $limit);

$avClientes = [
    'lista' => ClientePDO::listarTodos($limit, $offset),
    'roles' => RolClientePDO::listarRoles(),
    'paginacion' => [
        'actual' => $pag,
        'total' => $totalPaginas,
        'totalRegistros' => $totalClientes,
        'limit' => $limit
    ]
];

require_once $view['layout'];
