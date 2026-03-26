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
        2, // Tipo 2: Permite números y letras
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

        $login = $_REQUEST['username'] == null ? '' : $_REQUEST['username'];
        $password = $_REQUEST['password'] == null ? '' : $_REQUEST['password'];

        // Llamar al modelo
        $usuario = UsuarioPDO::validarUsuario($login, $password);

        if ($usuario) {
            // Guardar usuario en sesión para la app
            $_SESSION['usuarioActualTPV'] = $usuario;

            // Log: LOGIN
            LogPDO::addLog('LOGIN', 'El usuario ha iniciado sesión correctamente');

            // Redirigir al Dashboard
            $_SESSION['paginaEnCurso'] = 'Dashboard';
            header('Location: index.php');
            exit;
        } else {
            // Usuario o contraseña incorrecta
            $aErrores['username'] = 'El nombre de usuario o la contraseña no son correctos.';
            $aRespuestas['username'] = ''; // Borrado de datos de inputs
            $aRespuestas['password'] = '';
            $entradaOK = false;
        }
    }
} else {
    // Si no se ha enviado el formulario
    $entradaOK = false;
}

require_once $view['layout'];