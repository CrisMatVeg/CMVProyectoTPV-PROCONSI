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
$aErrores = [
    'nombre_completo' => null,
    'pass1' => null,
    'pass2' => null
];

$entradaOK = true;

// Procesar cambio de perfil
if (isset($_REQUEST['guardarCambios'])) {
    
    $aErrores['nombre_completo'] = validacionFormularios::comprobarAlfabetico($_REQUEST['nombre_completo'], 100, 3, 1);
    
    if (!empty($_REQUEST['pass1'])) {
        $aErrores['pass1'] = validacionFormularios::validarPassword($_REQUEST['pass1'], 20, 4, 1, 1);
        if ($_REQUEST['pass1'] !== $_REQUEST['pass2']) {
            $aErrores['pass2'] = "Las contraseñas no coinciden.";
        }
    }

    foreach ($aErrores as $e) {
        if ($e != null) $entradaOK = false;
    }

    if ($entradaOK) {
        $nombre = $_REQUEST['nombre_completo'];
        $pass1 = $_REQUEST['pass1'];

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

// Navegación Global
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

if (isset($_REQUEST['irCierreCaja'])) {
    $_SESSION['paginaEnCurso'] = 'cierreCaja';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['irMiPerfil'])) {
    $_SESSION['paginaEnCurso'] = 'MiPerfil';
    header('Location: index.php');
    exit;
}

if (isset($_REQUEST['volver']) || isset($_REQUEST['irDashboard'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}


$avMiPerfil = [
    'usuario' => $_SESSION['usuarioActualTPV'],
    'error'   => $error,
    'success' => $success,
    'aErrores' => $aErrores
];

require_once $view['layout'];
?>
