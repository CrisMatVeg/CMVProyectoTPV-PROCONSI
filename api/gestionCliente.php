<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: gestionCliente.php
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/ClientePDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/Validador.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $input['accion'] ?? '';

    if ($accion === 'buscar') {
        $nif = trim($input['nif'] ?? '');
        if ($nif === '') {
            throw new Exception('Debes indicar un NIF/DNI para buscar');
        }
        $cli = ClientePDO::obtenerPorNif($nif);
        if ($cli) {
            echo json_encode([
                'ok' => true,
                'cliente' => [
                    'id'       => (int)$cli['id'],
                    'nombre'   => $cli['nombre'],
                    'nif'      => $cli['nif'],
                    'rol'      => $cli['rol'] ?? 'general',
                    'puntos'   => $cli['puntos'] ?? 0,
                ],
            ]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'No encontrado']);
        }
        exit;
    }

    if ($accion === 'registrar') {
        $nombre = trim($input['nombre'] ?? '');
        $nif    = trim($input['nif'] ?? '');
        $tipo   = $input['tipo'] ?? 'particular';
        $rol    = $input['rol'] ?? 'general';

        if ($nombre === '' || $nif === '') {
            throw new Exception('Nombre y NIF son obligatorios para registrar un cliente');
        }

        if (!Validador::validarDocumento($nif)) {
            throw new Exception('El NIF/CIF proporcionado no tiene un formato válido.');
        }

        $cli = ClientePDO::obtenerPorNif($nif);
        if ($cli) {
            $cli['nombre']   = $nombre;
            $cli['tipo']     = $tipo;
            $cli['rol']      = $rol;
            ClientePDO::actualizar((int)$cli['id'], $cli);
            $nuevoId = (int)$cli['id'];
        } else {
            $nuevoId = ClientePDO::crear([
                'tipo'      => $tipo,
                'rol'       => $rol,
                'nombre'    => $nombre,
                'apellidos' => '',
                'nif'       => $nif,
                'email'     => null,
                'telefono'  => null,
            ]);
        }

        echo json_encode(['ok' => true, 'id' => $nuevoId]);
        exit;
    }

    if ($accion === 'buscarTexto') {
        $term = trim($input['term'] ?? '');
        if ($term === '') {
            throw new Exception('Debes indicar un texto para buscar');
        }
        $tipoFiltro = $input['tipo'] ?? null;

        $sql = "SELECT id, tipo, rol, nombre, apellidos, nif, puntos FROM clientes WHERE (fecha_baja IS NULL)";
        $params = [];
        if (in_array($tipoFiltro, ['particular', 'empresa'], true)) {
            $sql .= " AND tipo = :tipo";
            $params[':tipo'] = $tipoFiltro;
        }
        $sql .= " AND (nombre LIKE :t OR apellidos LIKE :t OR nif LIKE :t) ORDER BY nombre, apellidos LIMIT 20";
        $params[':t'] = '%' . $term . '%';

        $q = DBPDO::ejecutarConsulta($sql, $params);
        $lista = $q->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'lista' => $lista]);
        exit;
    }

    if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    if ($accion === 'historial') {
        $id = isset($input['id']) ? (int)$input['id'] : null;
        if (!$id) throw new Exception('ID obligatorio para el historial');

        require_once __DIR__ . '/../model/VentaPDO.php';
        require_once __DIR__ . '/../model/ValePDO.php';
        $ventas = VentaPDO::obtenerVentasPorCliente($id);
        $vales  = ValePDO::obtenerValesPorCliente($id);

        $cliente = ClientePDO::obtenerPorId($id);
        echo json_encode(['ok' => true, 'ventas' => $ventas, 'vales' => $vales, 'cliente' => $cliente]);
        exit;
    }

    if ($accion === 'eliminar') {
        $id = isset($input['id']) ? (int)$input['id'] : null;
        if (!$id) throw new Exception('ID obligatorio para eliminar');
        ClientePDO::marcarBaja($id);
        echo json_encode(['ok' => true]);
        exit;
    }

    $id   = isset($input['id']) && $input['id'] !== '' ? (int)$input['id'] : null;
    $tipo = $input['tipo'] ?? 'particular';
    $nombre = trim($input['nombre'] ?? '');

    if ($nombre === '') {
        throw new Exception('El nombre es obligatorio');
    }

    $nifInput = trim($input['nif'] ?? '');
    if ($nifInput !== '' && !Validador::validarDocumento($nifInput)) {
        throw new Exception('El NIF/CIF proporcionado no tiene un formato válido.');
    }

    $telefonoInput = trim($input['telefono'] ?? '');
    if ($telefonoInput !== '' && !Validador::validarTelefono($telefonoInput)) {
        throw new Exception('El teléfono proporcionado no tiene un formato válido.');
    }

    $data = [
        'tipo'      => $tipo,
        'rol'       => $input['rol'] ?? 'general',
        'nombre'    => $nombre,
        'apellidos' => $input['apellidos'] ?? '',
        'nif'       => $nifInput ?: null,
        'email'     => $input['email'] ?? null,
        'telefono'  => $telefonoInput ?: null,
        'direccion' => $input['direccion'] ?? null,
        'cp'        => $input['cp'] ?? null,
        'poblacion' => $input['poblacion'] ?? null,
        'provincia' => $input['provincia'] ?? null,
        'notas'     => $input['notas'] ?? null,
    ];

    if ($id) {
        ClientePDO::actualizar($id, $data);
        echo json_encode(['ok' => true, 'id' => $id]);
    } else {
        $nuevoId = ClientePDO::crear($data);
        echo json_encode(['ok' => true, 'id' => $nuevoId]);
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
