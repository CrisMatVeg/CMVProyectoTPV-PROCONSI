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

// Preparación de los datos del usuario para la vista
$avInicioPrivado = [
    "nombre_completo" => $_SESSION['usuarioActualTPV']->getNombreCompleto(),
    "username" => $_SESSION['usuarioActualTPV']->getUsername(),
    "password" => $_SESSION['usuarioActualTPV']->getPassword(),
    "rol" => $_SESSION['usuarioActualTPV']->getRol(),
    "esAdmin" => $esAdmin
];
$_SESSION['arrayDatosusuarioActualTPV'] = $avInicioPrivado;
// Pasamos el estado de admin a una variable global de JS
echo "<script>const IS_ADMIN_BACKEND = " . ($esAdmin ? 'true' : 'false') . ";</script>";

// Carga la vista layout principal
require_once $view["layout"];
