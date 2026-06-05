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

// Solo administradores o gestores de clientes
if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_clientes')) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['volver'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

require_once 'model/ClientePDO.php';
require_once 'model/RolClientePDO.php';

// Paginación
['pag' => $pag, 'limit' => $limit, 'offset' => $offset] = obtenerPaginacion(50);

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
