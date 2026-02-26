<?php
require_once './core/231018libreriaValidacion.php';
require_once './model/Usuario.php';
require_once './model/UsuarioPDO.php';
require_once './model/Producto.php';
require_once './model/ProductoPDO.php';
require_once './model/Venta.php';
require_once './model/VentaPDO.php';
$controller = [
    "inicioPublico" => "controller/cInicioPublico.php",
    "Login" => "controller/cLogin.php",
    "inicioPrivado" => "controller/cInicioPrivado.php",
    "cierreCaja" => "controller/cCierreCaja.php",
    // "Detalle" => "controller/cDetalle.php",
    // "MiCuenta" => "controller/cMiCuenta.php",
    // "BorrarCuenta" => "controller/cBorrarCuenta.php",
    // "CambiarPassword" => "controller/cCambiarPassword.php",
    "Registro" => "controller/cRegistro.php",
    // "WIP" => "controller/cWIP.php",
    "error" => "controller/cError.php",
    // "REST" => "controller/cREST.php",
    // "DetallesNasa" => "controller/cDetallesNasa.php",
    // "DetallesDog" => "controller/cDetallesDog.php",
    // "MtoDepartamentos" => "controller/cMtoDepartamentos.php",
    // "MtoUsuarios" => "controller/cMtoUsuarios.php",
    // "MtoUsuariosAPI" => "controller/cMtoUsuariosAPI.php",
    // "ConsultarDepartamentos" => "controller/cConsultarDepartamentos.php",
    // "EditarDepartamento" => "controller/cEditarDepartamento.php",
    // "AltaDepartamento" => "controller/cAltaDepartamento.php",
    // "ConsultarUsuario" => "controller/cConsultarUsuario.php",
    // "EditarUsuario" => "controller/cEditarUsuario.php",
    // "EliminarUsuario" => "controller/cEliminarUsuario.php",
    // "EliminarDepartamento" => "controller/cEliminarDepartamento.php"
];
$view = [
    "inicioPublico" => "view/vInicioPublico.php",
    "inicioPrivado" => "view/vInicioPrivado.php",
    "cierreCaja" => "view/vCierreCaja.php",
    "layout" => "view/layout.php",
    "Login" => "view/vLogin.php",
    // "Detalle" => "view/vDetalle.php",
    // "MiCuenta" => "view/vMiCuenta.php",
    // "BorrarCuenta" => "view/vBorrarCuenta.php",
    // "CambiarPassword" => "view/vCambiarPassword.php",
    "Registro" => "view/vRegistro.php",
    // "WIP" => "view/vWIP.php",
    "error" => "view/vError.php",
    // "REST" => "view/vREST.php",
    // "DetallesNasa" => "view/vDetallesNasa.php",
    // "DetallesDog" => "view/vDetallesDog.php",
    // "MtoDepartamentos" => "view/vMtoDepartamentos.php",
    // "MtoUsuarios" => "view/vMtoUsuarios.php",
    // "MtoUsuariosAPI" => "view/vMtoUsuariosAPI.php",
    // "ConsultarDepartamentos" => "view/vConsultarDepartamentos.php",
    // "EditarDepartamento" => "view/vEditarDepartamento.php",
    // "AltaDepartamento" => "view/vAltaDepartamento.php",
    // "ConsultarUsuario" => "view/vConsultarUsuario.php",
    // "EditarUsuario" => "view/vEditarUsuario.php",
    // "EliminarUsuario" => "view/vEliminarUsuario.php",
    // "EliminarDepartamento" => "view/vEliminarDepartamento.php"
];
