<?php
// cConfiguracion.php
require_once 'model/ConfiguracionPDO.php';

// Solo administradores pueden acceder
if (!isset($_SESSION['usuarioActualTPV']) || $_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
    header('Location: index.php?irDashboard=1');
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
        $configuracionesAGuardar = [
            'empresa_nombre' => trim($_POST['empresa_nombre'] ?? ''),
            'empresa_razon_social' => trim($_POST['empresa_razon_social'] ?? ''),
            'empresa_nif' => trim($_POST['empresa_nif'] ?? ''),
            'empresa_direccion' => trim($_POST['empresa_direccion'] ?? ''),
            'empresa_telefono' => trim($_POST['empresa_telefono'] ?? ''),
            'empresa_email' => trim($_POST['empresa_email'] ?? ''),
            'empresa_web' => trim($_POST['empresa_web'] ?? ''),
            'empresa_registro' => trim($_POST['empresa_registro'] ?? ''),
            'social_instagram' => trim($_POST['social_instagram'] ?? ''),
            'social_facebook' => trim($_POST['social_facebook'] ?? ''),
            'ticket_pie_pagina' => trim($_POST['ticket_pie_pagina'] ?? ''),
            'ticket_politica' => trim($_POST['ticket_politica'] ?? '')
        ];

        $exito = ConfiguracionPDO::guardarConfiguracion($configuracionesAGuardar);
        if ($exito) {
            $showSuccess = true;
        } else {
            $aErrores['general'] = "Hubo un error al guardar la configuración.";
        }
    }
}

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
    'ticket_politica'
];

$avConfig = [];
foreach ($campos as $campo) {
    // Si hubo error, mantenemos lo que el usuario escribió, sino cargamos de BD
    if (isset($_POST['guardarConfiguracion']) && !empty($aErrores)) {
        $avConfig[$campo] = $_POST[$campo] ?? '';
    } else {
        $avConfig[$campo] = $config[$campo] ?? '';
    }
}

require_once $view['layout'];
