<?php
/**
 * cRecuperarPassword.php
 * Controlador para la recuperación de contraseña olvidada.
 */
require_once 'model/UsuarioPDO.php';
require_once 'model/ConfiguracionPDO.php';
require_once 'core/PHPMailerMinimal.php';

use PHPMailer\PHPMailer\PHPMailer;

// Si ya está logueado, al dashboard
if (isset($_SESSION['usuarioActualTPV'])) {
    header('Location: index.php?irDashboard=1');
    exit;
}

$aErrores = [
    'email' => null,
    'password' => null,
    'password_confirm' => null
];

$showSuccess = false;
$step = 'solicitar'; // 'solicitar' o 'restablecer'
$tokenValido = false;
$idUsuario = null;

// 1. Determinar el paso actual
if (isset($_REQUEST['token'])) {
    $step = 'restablecer';
    $token = $_REQUEST['token'];
    $idUsuario = UsuarioPDO::validarToken($token);
    if ($idUsuario) {
        $tokenValido = true;
    } else {
        $aErrores['general'] = "El enlace de recuperación es inválido o ha expirado.";
    }
}

// 2. Procesar Formulario de Solicitar
if (isset($_POST['enviarSolicitud'])) {
    $email = trim($_POST['email'] ?? '');
    
    // Validación básica de email
    if (empty($email)) {
        $aErrores['email'] = "Introduce tu correo electrónico.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $aErrores['email'] = "El formato del correo no es válido.";
    }

    if (empty($aErrores['email'])) {
        // 1. Intentar buscar por el email introducido
        $usuario = UsuarioPDO::buscarPorEmail($email);
        
        // 2. Si no se encuentra por email, intentamos buscar si el valor introducido coincide con un LOGIN
        // (Esto permite a usuarios sin email o que no lo recuerdan usar su nombre de usuario)
        if (!$usuario) {
            $usuario = UsuarioPDO::validarUsuario($email, null, false);
        }
        
        if ($usuario) {
            // Determinar el email de destino
            $emailDestino = $usuario->getEmail();
            
            // Si el usuario no tiene email asignado, usamos el que acaba de introducir
            if (empty($emailDestino)) {
                $emailDestino = $email;
                // Opcionalmente actualizamos el email del usuario para futuras ocasiones
                DBPDO::ejecutarConsulta("UPDATE usuarios SET email = :email WHERE id = :id", [
                    ':email' => $email,
                    ':id' => $usuario->getId()
                ]);
            }
            
            // Generar Token
            $token = bin2hex(random_bytes(32));
            $expiracion = date('Y-m-d H:i:s', strtotime('+2 hours'));
            
            UsuarioPDO::guardarTokenRecuperacion($usuario->getId(), $token, $expiracion);

            // Cargar Configuración SMTP
            $conf = ConfiguracionPDO::obtenerConfiguracion();
            $empresa = $conf['empresa_nombre'] ?? 'ElectroBazar TPV';
            
            // Enviar Correo
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = $conf['smtp_host'] ?? '';
            $mail->Port = $conf['smtp_port'] ?? 587;
            $mail->SMTPAuth = true;
            $mail->Username = $conf['smtp_user'] ?? '';
            $mail->Password = $conf['smtp_pass'] ?? '';
            $mail->SMTPSecure = $conf['smtp_secure'] ?? 'tls';
            
            $mail->setFrom($conf['smtp_user'] ?? 'no-reply@tpv.com', $empresa);
            $mail->addAddress($emailDestino, $usuario->getNombre());
            $mail->isHTML(true);
            $mail->Subject = "Recuperación de contraseña - $empresa";
            
            $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]";
            $link = "$url?menu=RestablecerPassword&token=$token";
            
            $mail->Body = "
                <h1>Recuperación de Contraseña</h1>
                <p>Hola " . htmlspecialchars($usuario->getNombre()) . ",</p>
                <p>Has solicitado restablecer tu contraseña en el sistema TPV de $empresa.</p>
                <p>Haz clic en el siguiente enlace para elegir una nueva contraseña (expira en 2 horas):</p>
                <p><a href='$link' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Restablecer mi contraseña</a></p>
                <p>Si no has solicitado este cambio, puedes ignorar este correo.</p>
            ";
            
            if ($mail->send()) {
                $showSuccess = true;
                LogPDO::addLog('PASSWORD_REQUEST', "Solicitud de recuperación para el usuario: " . $usuario->getLogin() . " enviada a $emailDestino");
            } else {
                $aErrores['general'] = "Error al enviar el correo: " . $mail->getErrorInfo();
                error_log($mail->getErrorInfo());
            }
        } else {
            // Por seguridad, mostramos el mismo mensaje de éxito aunque no exista el usuario
            $showSuccess = true;
            LogPDO::addLog('PASSWORD_REQUEST_ATTEMPT', "Intento fallido de recuperación para: $email (No existe)");
        }
    }
}

// 3. Procesar Formulario de Restablecer
if (isset($_POST['cambiarPassword']) && $tokenValido) {
    $pass1 = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';
    
    if (strlen($pass1) < 4) {
        $aErrores['password'] = "La contraseña debe tener al menos 4 caracteres.";
    } elseif ($pass1 !== $pass2) {
        $aErrores['password_confirm'] = "Las contraseñas no coinciden.";
    }
    
    if (empty($aErrores['password']) && empty($aErrores['password_confirm'])) {
        if (UsuarioPDO::cambiarPassword($idUsuario, $pass1)) {
            $showSuccess = true;
            $step = 'completado';
            LogPDO::addLog('PASSWORD_CHANGED', "Contraseña actualizada mediante token de recuperación.");
        } else {
            $aErrores['general'] = "Error de base de datos al cambiar la contraseña.";
        }
    }
}

// Cargar vista según el paso (ahora via layout)
if ($step === 'solicitar' || $step === 'restablecer') {
    require_once $view['layout'];
} elseif ($step === 'completado') {
     // Redirigir al login con mensaje de éxito (podemos usar una sesión temporal)
     $_SESSION['pass_changed'] = true;
     header('Location: index.php?menu=Login');
     exit;
}
