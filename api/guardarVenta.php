<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: guardarVenta.php
 * Recibe la venta desde el frontend (JSON via fetch),
 * la valida y la guarda en la base de datos.
 * Devuelve JSON con el número de ticket generado.
 */

// Suprimir errores PHP para que nunca contaminen el JSON
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cabecera JSON siempre, incluso en caso de error
header('Content-Type: application/json; charset=utf-8');

try {
    // Bootstrap: config, BD y modelos
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';

    // ⚠️ Usuario.php debe cargarse ANTES de session_start()
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/Venta.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/CajaTurnoPDO.php';
    require_once __DIR__ . '/../model/ClientePDO.php';
    require_once __DIR__ . '/../model/Validador.php';
    require_once __DIR__ . '/../model/ProductoPDO.php';


    // Iniciar sesión para verificar autenticación
    // session_start(); // Handled by csrf_check.php

    // Solo POST permitido
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    // Solo usuarios autenticados
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado. Por favor, inicia sesión.']);
        exit;
    }

    // Verificar que la caja está abierta (existe un turno de caja abierto)
    $turnoActual = CajaTurnoPDO::obtenerTurnoAbierto();
    if (!$turnoActual) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'No hay una caja abierta. Debes abrir la caja antes de registrar ventas.']);
        exit;
    }

    require_once __DIR__ . '/../core/231018libreriaValidacion.php';

    // Leer y validar el JSON del cuerpo
    $json = file_get_contents('php://input');
    $datos = json_decode($json, true);

    if (!$datos || empty($datos['lineas'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Datos de venta inválidos o carrito vacío']);
        exit;
    }

    // Validaciones
    $aErrores = [];
    $metodo_pago    = $datos['metodoPago'] ?? 'efectivo';
    $total          = $datos['total'] ?? 0;
    $subtotal       = $datos['subtotal'] ?? 0;
    
    // Extraer datos con fallback para estructura modular (anidada) o plana (legacy)
    $tipoCliente    = $datos['tipoCliente'] ?? ($datos['cliente']['tipo'] ?? 'particular');
    $nombre_cliente = $datos['nombreCliente'] ?? ($datos['cliente']['nombre'] ?? ($datos['socio']['nombre'] ?? null));
    $nif_cliente    = strtoupper(trim($datos['nifCliente'] ?? ($datos['cliente']['nif'] ?? ($datos['socio']['nif'] ?? ''))));
    $id_cliente     = $datos['idCliente'] ?? ($datos['cliente']['id'] ?? ($datos['socio']['id'] ?? null));
    $aeat_id_type   = $datos['aeatIdType'] ?? ($datos['cliente']['aeat_id_type'] ?? '01');
    $aeat_codigo_pais = $datos['aeatCodigoPais'] ?? ($datos['cliente']['aeat_codigo_pais'] ?? 'ES');
    $esFactura      = (!empty($datos['esFactura']) || $total >= 3000) ? 1 : 0;

    // Validación obligatoria para Empresas o Facturas nominativas
    if ($tipoCliente === 'empresa' || $esFactura == 1) {
        $errNombre = validacionFormularios::comprobarAlfaNumerico($nombre_cliente ?? '', 100, 3, 1);
        if ($errNombre) $aErrores['empresaNombre'] = $errNombre;

        if (empty($nif_cliente)) {
            $aErrores['empresaNif'] = 'El NIF/CIF es obligatorio para empresas o facturas.';
        } else {
            // Validación específica según tipo
            if ($aeat_id_type === '01' || empty($aeat_id_type)) {
                if (!Validador::validarDocumento($nif_cliente)) {
                    $aErrores['empresaNif'] = 'El CIF/NIF no tiene un formato válido (ej: B12345678, 12345678A, X1234567A).';
                }
            } elseif ($aeat_id_type === '02') {
                if (!Validador::validarNifIva($nif_cliente, $aeat_codigo_pais)) {
                    $aErrores['empresaNif'] = "El NIF-IVA no es válido para el país $aeat_codigo_pais. Debe empezar por el prefijo del país.";
                }
            }
        }
    }

    $entradaOK = empty($aErrores);

    if (!$entradaOK) {
        // Consolidar errores en un solo string para el toast del frontend
        $errorMsg = implode(" | ", array_filter($aErrores));
        echo json_encode(['ok' => false, 'error' => $errorMsg, 'aErrores' => $aErrores]);
        exit;
    }

    // Normalizar datos para los siguientes pasos
    $datos['tipoCliente']   = $tipoCliente;
    $datos['nombreCliente'] = $nombre_cliente;
    $datos['nifCliente']    = $nif_cliente;
    $datos['idCliente']     = $id_cliente;
    $datos['aeatIdType']    = $aeat_id_type;
    $datos['aeatCodigoPais'] = $aeat_codigo_pais;
    $datos['esFactura']     = $esFactura;

    // Resolver / crear cliente en tabla clientes
    // - Si viene idCliente desde el TPV (socio ya seleccionado), se usa directamente
    // - Si es empresa con NIF, se busca por NIF y, si no existe, se crea
    // - Para particulares sin datos, se puede dejar null (venta anónima)
    $clienteIdParaVenta = null;
    $tipoCliente = $datos['tipoCliente'] ?? 'particular';

    if (!empty($id_cliente)) {
        $clienteIdParaVenta = (int)$id_cliente;
    } elseif ($tipoCliente === 'empresa') {
        $nombreEmpresa = trim($datos['nombreCliente'] ?? '');
        $nifEmpresa    = trim($nif_cliente ?? ($datos['nifCliente'] ?? ''));

        if ($nifEmpresa !== '') {
            $cli = ClientePDO::obtenerPorNif($nifEmpresa);
            if ($cli) {
                $clienteIdParaVenta = (int)$cli['id'];
            } elseif ($nombreEmpresa !== '') {
                $clienteIdParaVenta = ClientePDO::crear([
                    'tipo'      => 'empresa',
                    'nombre'    => $nombreEmpresa,
                    'apellidos' => '',
                    'nif'       => $nifEmpresa,
                    'aeat_id_type'   => $aeat_id_type,
                    'aeat_codigo_pais' => $aeat_codigo_pais,
                    'email'     => null,
                    'telefono'  => null,
                    'es_socio'  => 0,
                ]);
            }
        }
    } elseif ($tipoCliente === 'socio') {
        // Para socios siempre debería llegar idCliente desde el buscador/registro del TPV
        if (!empty($id_cliente)) {
            $clienteIdParaVenta = (int)$id_cliente;
        }
    }

    // Si se proporcionó un NIF nuevo (cliente sin NIF previo), actualizarlo en su ficha
    if ($clienteIdParaVenta && !empty($datos['nifEsNuevo']) && !empty($nif_cliente)) {
        DBPDO::ejecutarConsulta(
            "UPDATE clientes SET nif = :nif, aeat_id_type = :idtype, aeat_codigo_pais = :pais WHERE id = :id AND (nif IS NULL OR nif = '')",
            [':nif' => $nif_cliente, ':idtype' => $aeat_id_type, ':pais' => $aeat_codigo_pais, ':id' => $clienteIdParaVenta]
        );
    }

    // Guardar la venta en BD
    $idUsuario  = $_SESSION['usuarioActualTPV']->getId();
    if ($clienteIdParaVenta) {
        $datos['idCliente'] = $clienteIdParaVenta;
    }
    $datos['idTurno'] = (int)$turnoActual['id'];

    // [REMOVIDO] La validación y consumo de vales ahora se gestiona internamente en VentaPDO::guardarVenta
    // para soportar pagos mixtos y asegurar la atomicidad de la transacción.

    $numTicket = VentaPDO::guardarVenta($datos, $idUsuario);

    // El consumo del vale se realiza ahora dentro de VentaPDO::guardarVenta

    // [NUEVO] Registrar Log
    require_once __DIR__ . '/../model/LogPDO.php';
    $descLog = "Venta registrada (#$numTicket) por importe de " . number_format($total, 2, ',', '.') . "€";
    $tipoLog = 'VENTA';
    
    if (($datos['descuentoAmt'] ?? 0) > 0) {
        $tipoLog = 'DESCUENTO';
        $descLog .= " (CON DESCUENTO de " . number_format($datos['descuentoAmt'], 2, ',', '.') . "€)";
    }
    
    LogPDO::addLog($tipoLog, $descLog, ['numTicket' => $numTicket, 'total' => $total, 'metodo' => $metodo_pago]);

    // Obtener la venta completa para devolver al frontend
    $ventaCompleta = VentaPDO::obtenerVentaPorTicket($numTicket);

    echo json_encode([
        'ok'     => true,
        'ticket' => $numTicket,
        'venta'  => $ventaCompleta
    ]);
} catch (Throwable $e) {
    // Captura cualquier error PHP o excepción y lo devuelve como JSON
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
