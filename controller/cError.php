<?php
/**
 * Controlador: cError
 * 
 * Gestiona la visualización de errores capturados en la aplicación.
 */

// Recuperamos el error de la sesión
$error = $_SESSION['error'] ?? null;

// Si no hay error, redirigimos al inicio
if (!$error) {
    header('Location: index.php');
    exit;
}

// Cargamos la vista de error
require_once $view['layout'];
?>
