<?php
require_once ("./config/confAPP.php");
require_once ("./config/confDBPDO.php");
session_start();

if (!isset($_SESSION['paginaEnCurso'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPublico';
}

require_once ($controller[$_SESSION['paginaEnCurso']]);
?>