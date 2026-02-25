<?php
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

if ($_SESSION['usuarioActualTPV']->getRol() == "admin") {
    $esAdmin = true;
} else {
    $esAdmin = false;
}

if (isset($_REQUEST['atras'])) {
    session_unset();
    session_destroy();
    session_start();
    /* UsuarioPDO::guardarToken($codUsuario, null); */
    $_SESSION['paginaAnterior'] = $_REQUEST['paginaAnterior'];
    $_SESSION['paginaEnCurso'] = $_SESSION['paginaAnterior'];
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irCierreCaja'])) {
    $_SESSION['paginaEnCurso'] = 'cierreCaja';
    header('Location: index.php');
    exit;
}

// Carga de productos desde la base de datos
$oProductos = ProductoPDO::listarProductos();
$aProductos = [];
foreach ($oProductos as $oProducto) {
    $aProductos[] = [
        "id" => $oProducto->getId(),
        "name" => $oProducto->getNombre(),
        "codigo" => $oProducto->getCodigo(),
        "price" => (float)$oProducto->getPrecio(),
        "icono" => $oProducto->getIcono(),
        "cat" => $oProducto->getCategoria(),
        "inactive" => !$oProducto->getActivo()
    ];
}

// Preparación de los datos del usuario para la vista
$avInicioPrivado = [
    "nombre_completo" => $_SESSION['usuarioActualTPV']->getNombreCompleto(),
    "username" => $_SESSION['usuarioActualTPV']->getUsername(),
    "password" => $_SESSION['usuarioActualTPV']->getPassword(),
    "rol" => $_SESSION['usuarioActualTPV']->getRol(),
    "esAdmin" => $esAdmin,
    "productos" => $aProductos
];
$_SESSION['arrayDatosusuarioActualTPV'] = $avInicioPrivado;
// Carga la vista layout principal
require_once $view["layout"];
