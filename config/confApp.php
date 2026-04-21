<?php
require_once './core/231018libreriaValidacion.php';
require_once __DIR__ . '/config.php';

require_once './model/Usuario.php';
require_once './model/UsuarioPDO.php';
require_once './model/Producto.php';
require_once './model/ProductoPDO.php';
require_once './model/Venta.php';
require_once './model/VentaPDO.php';
require_once './model/ClientePDO.php';
require_once './model/RolPDO.php';
require_once './model/LogPDO.php';
$controller = [
    "inicioPublico" => "controller/cInicioPublico.php",
    "Login" => "controller/cLogin.php",
    "inicioPrivado" => "controller/cInicioPrivado.php",
    "cierreCaja" => "controller/cCierreCaja.php",
    "Dashboard" => "controller/cDashboard.php",
    "Usuarios" => "controller/cUsuarios.php",
    "Roles" => "controller/cRoles.php",
    "Clientes" => "controller/cClientes.php",
    // "Detalle" => "controller/cDetalle.php",
    // "MiCuenta" => "controller/cMiCuenta.php",
    // "BorrarCuenta" => "controller/cBorrarCuenta.php",
    // "CambiarPassword" => "controller/cCambiarPassword.php",
    "Registro" => "controller/cRegistro.php",
    // "WIP" => "controller/cWIP.php",
    "error" => "controller/cError.php",
    "Productos" => "controller/cProductos.php",
    "Promociones" => "controller/cPromociones.php",
    "TiposIVA" => "controller/cTiposIVA.php",
    "Tarifas" => "controller/cTarifas.php",
    "Historial" => "controller/cHistorial.php",
    "MiPerfil" => "controller/cMiPerfil.php",
    "Analitica" => "controller/cAnalitica.php",
    "Proveedores" => "controller/cProveedores.php",
    "Compras" => "controller/cCompras.php",
    // "REST" => "controller/cREST.php",
    // "DetallesNasa" => "controller/cDetallesNasa.php",
    // "DetallesDog" => "controller/cDetallesDog.php",
    "MtoDepartamentos" => "controller/cMtoDepartamentos.php",
    "Configuracion" => "controller/cConfiguracion.php",
    "RecuperarPassword" => "controller/cRecuperarPassword.php",
    "RestablecerPassword" => "controller/cRecuperarPassword.php"
];
$view = [
    "inicioPublico" => "view/vInicioPublico.php",
    "inicioPrivado" => "view/vInicioPrivado.php",
    "cierreCaja" => "view/vCierreCaja.php",
    "Dashboard" => "view/vDashboard.php",
    "Usuarios" => "view/vUsuarios.php",
    "Roles" => "view/vRoles.php",
    "Clientes" => "view/vClientes.php",
    "layout" => "view/layout.php",
    "Login" => "view/vLogin.php",
    // "Detalle" => "view/vDetalle.php",
    // "MiCuenta" => "view/vMiCuenta.php",
    // "BorrarCuenta" => "view/vBorrarCuenta.php",
    // "CambiarPassword" => "view/vCambiarPassword.php",
    "Registro" => "view/vRegistro.php",
    // "WIP" => "view/vWIP.php",
    "error" => "view/vError.php",
    "Productos" => "view/vProductos.php",
    "Promociones" => "view/vPromociones.php",
    "TiposIVA" => "view/vTiposIVA.php",
    "Tarifas" => "view/vTarifas.php",
    "Historial" => "view/vHistorial.php",
    "MiPerfil" => "view/vMiPerfil.php",
    "Analitica" => "view/vAnalitica.php",
    "Proveedores" => "view/vProveedores.php",
    "Compras" => "view/vCompras.php",
    // "REST" => "view/vREST.php",
    // "DetallesNasa" => "view/vDetallesNasa.php",
    // "DetallesDog" => "view/vDetallesDog.php",
    "MtoDepartamentos" => "view/vMtoDepartamentos.php",
    "Configuracion" => "view/vConfiguracion.php",
    "RecuperarPassword" => "view/vRecuperarPassword.php",
    "RestablecerPassword" => "view/vRestablecerPassword.php"
];
