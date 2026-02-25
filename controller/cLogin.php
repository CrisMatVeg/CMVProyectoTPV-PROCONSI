<?php
if (isset($_REQUEST['atras'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPublico';
    header('Location: index.php');
    exit;
}

/* if (isset($_REQUEST['registro'])) {
    $_SESSION['paginaAnterior'] = $_SESSION['paginaEnCurso'];
    $_SESSION['paginaEnCurso'] = 'Registro';
    header('Location: index.php');
    exit;
} */

$aErrores = [
    'username' => null,
    'password' => null
];

$aRespuestas = [
    'username' => '',
    'password' => ''
];

$entradaOK = true;

if (isset($_REQUEST['acceder'])) {

    $_SESSION['paginaAnterior'] = $_SESSION['paginaEnCurso'];

    // Validaciones usando la librería
    $aErrores['username'] = validacionFormularios::comprobarAlfaNumerico(
        $_REQUEST['username'],
        10,
        4,
        1
    );

    $aErrores['password'] = validacionFormularios::validarPassword(
        $_REQUEST['password'],
        20,
        4,
        1,
        1
    );

    // Guardar respuestas
    $aRespuestas['username'] = $_REQUEST['username'];
    $aRespuestas['password'] = $_REQUEST['password'];

    // Comprobar si hay errores
    foreach ($aErrores as $error) {
        if ($error != null) {
            $entradaOK = false;
        }
    }

    if ($entradaOK) {

        $username = $_REQUEST['username'] == null ? '' : $_REQUEST['username'];
        $password = $_REQUEST['password'] == null ? '' : $_REQUEST['password'];

        // Llamar al modelo
        $usuarioPDO = new UsuarioPDO();
        $usuario = $usuarioPDO->validarUsuario($username, $password);

        if ($usuario) {
            // Guardar usuario en sesión para la app
            $_SESSION['usuarioActualTPV'] = $usuario;

            // Redirigir a la app
            $_SESSION['paginaEnCurso'] = 'inicioPrivado';
            header('Location: index.php');
            exit;
        } else {
            // Usuario o contraseña incorrecta
            $aErrores['username'] = 'Usuario o contraseña incorrecta';
            $entradaOK = false;
        }
    }
} else {
    // Si no se ha enviado el formulario
    $entradaOK = false;
}

require_once $view['layout'];