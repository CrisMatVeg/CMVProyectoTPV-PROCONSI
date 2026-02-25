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
    // ⚠️ Usuario.php debe cargarse ANTES de session_start()
    // para que PHP pueda deserializar el objeto guardado en $_SESSION
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/Venta.php';
    require_once __DIR__ . '/../model/VentaPDO.php';

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

    // Leer y validar el JSON del cuerpo
    $json = file_get_contents('php://input');
    $datos = json_decode($json, true);

    if (!$datos || empty($datos['lineas'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Datos de venta inválidos o carrito vacío']);
        exit;
    }

    // Guardar la venta en BD
    $idCajero  = $_SESSION['usuarioActualTPV']->getId();
    $numTicket = VentaPDO::guardarVenta($datos, $idCajero);

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

