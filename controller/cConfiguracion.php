<?php
// cConfiguracion.php
require_once 'model/ConfiguracionPDO.php';
require_once 'model/TipoIVAPDO.php';

// Solo administradores o gestores de configuración pueden acceder
if (!isset($_SESSION['usuarioActualTPV']) || !$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_configuracion')) {
    header('Location: index.php?irDashboard=1');
    exit;
}

if (isset($_REQUEST['volver'])) {
    $_SESSION['paginaEnCurso'] = 'Dashboard';
    header('Location: index.php');
    exit;
}

$aErrores = [];
$showSuccess = false;

// Manejar guardado de configuración
if (isset($_POST['guardarConfiguracion'])) {

    // Validaciones básicas (pueden ser más exhaustivas)
    if (empty($_POST['empresa_nombre'])) {
        $aErrores['empresa_nombre'] = "El nombre de la empresa es obligatorio.";
    }

    // Si no hay errores, procedemos a guardar
    if (empty($aErrores)) {
        $todosLosCampos = [
            'empresa_nombre', 'empresa_razon_social', 'empresa_nif', 'empresa_direccion',
            'empresa_telefono', 'empresa_email', 'empresa_web', 'empresa_registro',
            'social_instagram', 'social_facebook', 'ticket_pie_pagina', 'ticket_politica',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure',
            'empresa_aplica_re',
            'verifactu_productor_nombre', 'verifactu_productor_nif', 'verifactu_id_sistema', 'verifactu_version_sistema'
        ];

        $configuracionesAGuardar = [];
        foreach ($todosLosCampos as $campo) {
            if (isset($_POST[$campo])) {
                $configuracionesAGuardar[$campo] = trim($_POST[$campo]);
            }
        }

        if (!empty($configuracionesAGuardar)) {
            $exito = ConfiguracionPDO::guardarConfiguracion($configuracionesAGuardar);
            if ($exito) {
                LogPDO::addLog('UPDATE_CONFIGURACION', "Configuración del sistema actualizada", [
                    'campos_modificados' => array_keys($configuracionesAGuardar),
                ]);
                $showSuccess = true;
            } else {
                $aErrores['general'] = "Hubo un error al guardar la configuración.";
            }
        }
    }
}

// 2. Gestionar Logs del Sistema
$fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-d');
$fechaFin = $_GET['fechaFin'] ?? date('Y-m-d');

if (isset($_GET['descargarLogs'])) {
    $logsExport = LogPDO::getLogs($fechaInicio, $fechaFin);
    LogPDO::exportToCSV($logsExport);
    exit;
}

// 3. Gestionar Copia de Seguridad
if (isset($_GET['descargarBackup'])) {
    require_once 'model/BackupPDO.php';
    BackupPDO::descargarBackup();
    exit;
}

$logs = LogPDO::getLogs($fechaInicio, $fechaFin);
$tiposIva = TipoIVAPDO::listarVigentesActuales();

// Cargar configuración actual para mostrar en la vista
$config = ConfiguracionPDO::obtenerConfiguracion();

// Inicializamos array con valores vacíos si no existe la clave para evitar warnings
$campos = [
    'empresa_nombre',
    'empresa_razon_social',
    'empresa_nif',
    'empresa_direccion',
    'empresa_telefono',
    'empresa_email',
    'empresa_web',
    'empresa_registro',
    'social_instagram',
    'social_facebook',
    'ticket_pie_pagina',
    'ticket_politica',
    'smtp_host',
    'smtp_port',
    'smtp_user',
    'smtp_pass',
    'smtp_secure',
    'empresa_aplica_re',
    'verifactu_productor_nombre',
    'verifactu_productor_nif',
    'verifactu_id_sistema',
    'verifactu_version_sistema'
];

$avConfig = [];
foreach ($campos as $campo) {
    // Si hubo intento de guardado, preferimos el valor de POST si existe, sino el de BD
    if (isset($_POST['guardarConfiguracion'])) {
        $avConfig[$campo] = $_POST[$campo] ?? ($config[$campo] ?? '');
    } else {
        $avConfig[$campo] = $config[$campo] ?? '';
    }
}

require_once $view['layout'];
