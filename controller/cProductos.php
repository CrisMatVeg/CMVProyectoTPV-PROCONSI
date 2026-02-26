<?php
/**
 * Controller: cProductos.php
 * Gestiona la lista de productos para administración.
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

// Volver al Dashboard
if (isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

// Obtener la lista completa de productos (activos e inactivos para gestión)
// Nota: ProductoPDO::listarProductos solo devuelve activos. 
// Deberíamos crear una función listarTodos() en ProductoPDO o usar la API.
// Para simplificar esta primera versión, usaremos una consulta directa si ProductoPDO no tiene el método.
$sql = "SELECT * FROM productos ORDER BY categoria ASC, nombre ASC";
$consulta = DBPDO::ejecutarConsulta($sql);
$listaProductos = $consulta->fetchAll(PDO::FETCH_ASSOC);

$avProductos = [
    'productos' => $listaProductos,
    'usuario'   => $_SESSION['usuarioActualTPV']->getNombreCompleto()
];

require_once $view['layout'];
?>
