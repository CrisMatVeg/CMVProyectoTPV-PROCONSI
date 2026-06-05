<?php

// Estados de caja
define('ESTADO_CAJA_ABIERTO',  'abierto');
define('ESTADO_CAJA_CERRADO',  'cerrado');

// Estados de venta
define('ESTADO_VENTA_COMPLETADA',           'completada');
define('ESTADO_VENTA_ANULADA',              'anulada');
define('ESTADO_VENTA_PENDIENTE_PAGO',       'pendiente_pago');
define('ESTADO_VENTA_DEVUELTA',             'devuelta');
define('ESTADO_VENTA_PARCIAL_DEVUELTA',     'parcialmente_devuelta');

// Métodos de pago
define('METODO_PAGO_EFECTIVO', 'efectivo');
define('METODO_PAGO_TARJETA',  'tarjeta');
define('METODO_PAGO_BIZUM',    'bizum');
define('METODO_PAGO_A_CUENTA', 'a_cuenta');
define('METODO_PAGO_MIXTO',    'mixto');

// Configuración temporal y plazos
define('TIMEZONE_DEFAULT',              'Europe/Madrid');
define('DIAS_MAX_DEVOLUCION',           30);
define('DIAS_GARANTIA_RECTIFICATIVA',   1460); // 4 años fiscales
