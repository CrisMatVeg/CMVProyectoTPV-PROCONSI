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
                    'aeat_id_type'     => $cli['aeat_id_type'] ?? '01',
                    'aeat_codigo_pais' => $cli['aeat_codigo_pais'] ?? 'ES',
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
        $aeat_id_type = $input['aeat_id_type'] ?? '01';
        $aeat_codigo_pais = $input['aeat_codigo_pais'] ?? 'ES';

        if ($nombre === '' || $nif === '') {
            throw new Exception('Nombre y NIF son obligatorios para registrar un cliente');
        }

        if ($aeat_id_type === '01' && !Validador::validarDocumento($nif)) {
            throw new Exception('El NIF/CIF proporcionado no tiene un formato válido.');
        }

        $cli = ClientePDO::obtenerPorNif($nif);
        if ($cli) {
            $cli['nombre']   = $nombre;
            $cli['tipo']     = $tipo;
            $cli['rol']      = $rol;
            $cli['aeat_id_type'] = $aeat_id_type;
            $cli['aeat_codigo_pais'] = $aeat_codigo_pais;
            ClientePDO::actualizar((int)$cli['id'], $cli);
            $nuevoId = (int)$cli['id'];
        } else {
            $nuevoId = ClientePDO::crear([
                'tipo'      => $tipo,
                'rol'       => $rol,
                'nombre'    => $nombre,
                'apellidos' => '',
                'nif'       => $nif,
                'aeat_id_type'   => $aeat_id_type,
                'aeat_codigo_pais' => $aeat_codigo_pais,
                'email'     => null,
                'telefono'  => null,
            ]);
        }

        echo json_encode(['ok' => true, 'id' => $nuevoId]);
        exit;
    }

    if ($accion === 'buscarTexto') {
        $term = trim($input['term'] ?? '');
        $limit = isset($input['limit']) ? (int)$input['limit'] : 20;
        $offset = isset($input['offset']) ? (int)$input['offset'] : 0;

        if ($term === '' && !isset($input['force'])) {
            throw new Exception('Debes indicar un texto para buscar');
        }

        $tipoFiltro = $input['tipo'] ?? null;
        $rolFiltro = $input['rol'] ?? null;

        $baseSql = "FROM clientes WHERE (fecha_baja IS NULL)";
        $params = [];
        if (in_array($tipoFiltro, ['particular', 'empresa'], true)) {
            $baseSql .= " AND tipo = :tipo";
            $params[':tipo'] = $tipoFiltro;
        }
        if ($rolFiltro) {
            $baseSql .= " AND rol = :rol";
            $params[':rol'] = $rolFiltro;
        }

        if ($term !== '') {
            $baseSql .= " AND (nombre LIKE :t OR apellidos LIKE :t OR nif LIKE :t OR email LIKE :t)";
            $params[':t'] = '%' . $term . '%';
        }

        // Obtener total para paginación
        $countSql = "SELECT COUNT(*) " . $baseSql;
        $qCount = DBPDO::ejecutarConsulta($countSql, $params);
        $total = (int)$qCount->fetchColumn();

        // Obtener datos paginados
        $dataSql = "SELECT id, tipo, rol, nombre, apellidos, nif, aeat_id_type, aeat_codigo_pais, email, telefono, puntos, fecha_alta " . $baseSql . " ORDER BY id ASC LIMIT :limit OFFSET :offset";
        $dataSql = str_replace([':limit', ':offset'], [(int)$limit, (int)$offset], $dataSql);

        $q = DBPDO::ejecutarConsulta($dataSql, $params);
        $lista = $q->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'lista' => $lista, 'total' => $total]);
        exit;
    }

    if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_clientes')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No tienes permiso para gestionar clientes']);
        exit;
    }

    if ($accion === 'historial') {
        $id = isset($input['id']) ? (int)$input['id'] : null;
        if (!$id) throw new Exception('ID obligatorio para el historial');
        
        $limit = isset($input['limit']) ? (int)$input['limit'] : 50;
        $offset = isset($input['offset']) ? (int)$input['offset'] : 0;

        require_once __DIR__ . '/../model/VentaPDO.php';
        require_once __DIR__ . '/../model/ValePDO.php';
        $ventas = VentaPDO::obtenerVentasPorCliente($id, $limit, $offset);
        $vales  = ValePDO::obtenerValesPorCliente($id);

        $cliente = ClientePDO::obtenerPorId($id);
        echo json_encode(['ok' => true, 'ventas' => $ventas, 'vales' => $vales, 'cliente' => $cliente]);
        exit;
    }

    if ($accion === 'listar') {
        $limit = isset($input['limit']) ? (int)$input['limit'] : 50;
        $offset = isset($input['offset']) ? (int)$input['offset'] : 0;
        
        $lista = ClientePDO::listarTodos($limit, $offset);
        $total = ClientePDO::contarTodos();
        
        echo json_encode(['ok' => true, 'lista' => $lista, 'total' => $total]);
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

    $aeat_id_type = $input['aeat_id_type'] ?? '01';
    $aeat_codigo_pais = $input['aeat_codigo_pais'] ?? 'ES';

    $nifInput = trim($input['nif'] ?? '');
    if ($aeat_id_type === '01' && $nifInput !== '' && !Validador::validarDocumento($nifInput)) {
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
        'aeat_id_type'     => $input['aeat_id_type'] ?? '01',
        'aeat_codigo_pais' => $input['aeat_codigo_pais'] ?? 'ES',
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
