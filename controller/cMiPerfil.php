<?php
/**
 * Controller: cMiPerfil.php
 * Gestiona el perfil del usuario actual.
 */

// Solo usuarios autenticados
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

$error = null;
$success = null;

// Procesar cambio de perfil
if (isset($_REQUEST['guardarCambios'])) {
    $nombre = trim($_REQUEST['nombre_completo']);
    $pass1 = $_REQUEST['pass1'];
    $pass2 = $_REQUEST['pass2'];

    if (empty($nombre)) {
        $error = "El nombre no puede estar vacío.";
    } elseif (!empty($pass1) && $pass1 !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // En un sistema real, actualizaríamos la BD aquí.
        // Implementaremos UsuarioPDO::editarPerfil para esto.
        try {
            // Actualizar en BD
            $id = $_SESSION['usuarioActualTPV']->getId();
            
            // Actualizamos nombre siempre
            DBPDO::ejecutarConsulta("UPDATE usuarios SET nombre_completo = :nom WHERE id = :id", [':nom' => $nombre, ':id' => $id]);
            
            // Si hay password, la actualizamos
            if (!empty($pass1)) {
                DBPDO::ejecutarConsulta("UPDATE usuarios SET password = SHA2(:pass,256) WHERE id = :id", [':pass' => $pass1, ':id' => $id]);
            }
            
            // Actualizar objeto en sesión
            $_SESSION['usuarioActualTPV']->setNombreCompleto($nombre);
            $success = "Perfil actualizado correctamente.";
        } catch (Exception $e) {
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// Volver al Dashboard
if (isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

$avMiPerfil = [
    'usuario' => $_SESSION['usuarioActualTPV'],
    'error'   => $error,
    'success' => $success
];

require_once $view['layout'];
?>
