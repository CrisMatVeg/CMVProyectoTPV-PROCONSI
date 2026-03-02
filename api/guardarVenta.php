<?php
/**
 * API: guardarVenta.php
 * Recibe la venta desde el frontend (JSON via fetch),
 * la valida y la guarda en la base de datos.
 * Devuelve JSON con el número de ticket generado.
 */

// Suprimir errores PHP para que nunca contaminen el JSON
ini_set('display_errors', 0);
error_reporting(0);

// Cabecera JSON siempre, incluso en caso de error
header('Content-Type: application/json; charset=utf-8');

try {
    // Bootstrap: config, BD y modelos
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    
    // MIGRACIÓN AUTOMÁTICA (Provisional para estabilizar el sistema)
    try {
        DBPDO::ejecutarConsulta("ALTER TABLE ventas ADD COLUMN IF NOT EXISTS efectivo_recibido DECIMAL(10,2) DEFAULT 0.00");
    } catch (Throwable $e) { /* Ya existe o error menor */ }

    // ⚠️ Usuario.php debe cargarse ANTES de session_start()
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/Venta.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/CajaTurnoPDO.php';
    require_once __DIR__ . '/../model/ClientePDO.php';

    // Iniciar sesión para verificar autenticación
    session_start();

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
    $metodo_pago        = $datos['metodoPago'] ?? 'efectivo';
    $id_cliente         = $datos['idCliente'] ?? null;
    $nombre_cliente     = $datos['nombreCliente'] ?? null;
    $nif_cliente        = null; // Inicializar nif_cliente
    $total              = $datos['total'] ?? 0;
    $subtotal           = $datos['subtotal'] ?? 0;
    $descuento_pct      = $datos['descuentoPct'] ?? 0;
    $lineas             = $datos['lineas'] ?? [];

    if (isset($datos['tipoCliente']) && $datos['tipoCliente'] === 'empresa') {
        $aErrores['empresaNombre'] = validacionFormularios::comprobarAlfaNumerico($datos['nombreCliente'] ?? '', 100, 3, 1);
        $nifRaw = strtoupper(trim($datos['nifCliente'] ?? ''));
        $nif_cliente = $nifRaw; // Asignar nif_cliente aquí
        $aErrores['empresaNif'] = validacionFormularios::comprobarNoVacio($nifRaw);
        if (!$aErrores['empresaNif']) {
            // Validar NIF/CIF español:
            // - CIF empresa: letra [ABCDEFGHJNPQRSUVW] + 7 dígitos + dígito o letra de control
            // - DNI (autónomo): 8 dígitos + letra de control
            // - NIE (extranjero): X, Y o Z + 7 dígitos + letra de control
            $esCIF = preg_match('/^[ABCDEFGHJNPQRSUVW]\d{7}[0-9A-J]$/i', $nifRaw);
            $esDNI = preg_match('/^\d{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/i', $nifRaw);
            $esNIE = preg_match('/^[XYZ]\d{7}[TRWAGMYFPDXBNJZSQVHLCKE]$/i', $nifRaw);
            if (!$esCIF && !$esDNI && !$esNIE) {
                $aErrores['empresaNif'] = 'El CIF/NIF no tiene un formato válido (ej: B12345678, 12345678A, X1234567A).';
            }
        }

    }

    $entradaOK = true;
    foreach ($aErrores as $e) {
        if ($e != null) $entradaOK = false;
    }

    if (!$entradaOK) {
        echo json_encode(['ok' => false, 'aErrores' => $aErrores]);
        exit;
    }

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

    // Guardar la venta en BD
    $idUsuario  = $_SESSION['usuarioActualTPV']->getId();
    if ($clienteIdParaVenta) {
        $datos['idCliente'] = $clienteIdParaVenta;
    }
    $numTicket = VentaPDO::guardarVenta($datos, $idUsuario);

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

