<?php
if (isset($_REQUEST['atras'])) {
    $_SESSION['paginaEnCurso'] = 'inicioPublico';
    header('Location: index.php');
    exit;
}

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
        // Primero comprobamos si el usuario existe pero está de baja
        $usuarioCheck = UsuarioPDO::validarUsuario($login, null, false);

        if ($usuarioCheck && !$usuarioCheck->getActivo()) {
            $aErrores['username'] = 'Este usuario está dado de baja.';
            $aRespuestas['username'] = '';
            $aRespuestas['password'] = '';
            $entradaOK = false;
        } else {
            // Si el usuario no existe o está activo, intentamos validar las credenciales
            $usuario = UsuarioPDO::validarUsuario($login, $password);

            if ($usuario) {
                // Guardar usuario en sesión para la app
                $_SESSION['usuarioActualTPV'] = $usuario;

                // Log: LOGIN
                LogPDO::addLog('LOGIN', 'El usuario ha iniciado sesión correctamente');

                // [VERIFACTU] Log técnico de inicio de uso del sistema
                require_once __DIR__ . '/../model/VeriFactuEventService.php';
                VeriFactuEventService::logStartup();

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
    }
} else {
    // Si no se ha enviado el formulario
    $entradaOK = false;
}

require_once $view['layout'];