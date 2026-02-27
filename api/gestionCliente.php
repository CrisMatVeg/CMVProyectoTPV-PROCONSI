<?php
/**
 * API: gestionCliente.php
 * Gestiona la búsqueda y registro de clientes/socios.
 */
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/ClientePDO.php';

    session_start();

    if (!isset($_SESSION['usuarioActualTPV'])) {
        throw new Exception('No autenticado');
    }

    $datos = json_decode(file_get_contents('php://input'), true);
    $accion = $datos['accion'] ?? '';

    switch ($accion) {
        case 'buscar':
            $nif = trim($datos['nif'] ?? '');
            $cliente = ClientePDO::buscarClientePorNif($nif);
            if ($cliente) {
                echo json_encode([
                    'ok' => true, 
                    'cliente' => [
                        'id' => $cliente->getId(),
                        'nombre' => $cliente->getNombre(),
                        'nif' => $cliente->getNif(),
                        'es_socio' => $cliente->getEsSocio()
                    ]
                ]);
            } else {
                echo json_encode(['ok' => false, 'error' => 'No encontrado']);
            }
            break;

        case 'registrar':
            $id = ClientePDO::registrarCliente([
                'nombre'   => $datos['nombre'],
                'nif'      => $datos['nif'],
                'email'    => $datos['email'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'es_socio' => true
            ]);
            echo json_encode(['ok' => true, 'id' => $id]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
