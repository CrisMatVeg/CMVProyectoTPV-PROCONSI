<?php
if (isset($_REQUEST['login'])) {
    $_SESSION['paginaAnterior'] = $_SESSION['paginaEnCurso'];
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

// Carga la vista layout principal
require_once $view["layout"];
