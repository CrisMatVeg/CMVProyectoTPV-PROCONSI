<?php
/**
 * API: gestionCliente.php
 * - Desde TPV: buscar / registrar socio rápido (acciones "buscar" / "registrar")
 * - Desde panel admin: alta/edición de clientes completos (sin "accion", usa campos de formulario).
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/ClientePDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    session_start();
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $input['accion'] ?? '';

    // ── Flujo TPV: búsqueda / alta rápida de socio ────────────────────────
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
                    'es_socio' => (int)$cli['es_socio'] === 1,
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
        if ($nombre === '' || $nif === '') {
            throw new Exception('Nombre y NIF son obligatorios para registrar un socio');
        }

        // Si ya existe un cliente con ese NIF lo reutilizamos y lo marcamos como socio
        $cli = ClientePDO::obtenerPorNif($nif);
        if ($cli) {
            $cli['nombre']   = $nombre;
            $cli['es_socio'] = 1;
            ClientePDO::actualizar((int)$cli['id'], $cli);
            $nuevoId = (int)$cli['id'];
        } else {
            $nuevoId = ClientePDO::crear([
                'tipo'      => 'particular',
                'nombre'    => $nombre,
                'apellidos' => '',
                'nif'       => $nif,
                'email'     => null,
                'telefono'  => null,
                'es_socio'  => 1,
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

        $sql = "SELECT id, tipo, nombre, apellidos, nif, es_socio 
                FROM clientes 
                WHERE (fecha_baja IS NULL OR fecha_baja IS NULL)";
        $params = [];

        if (in_array($tipoFiltro, ['particular','empresa'], true)) {
            $sql .= " AND tipo = :tipo";
            $params[':tipo'] = $tipoFiltro;
        }

        $sql .= " AND (nombre LIKE :t OR apellidos LIKE :t OR nif LIKE :t)
                  ORDER BY nombre, apellidos 
                  LIMIT 20";
        $params[':t'] = '%' . $term . '%';

        $q = DBPDO::ejecutarConsulta($sql, $params);
        $lista = $q->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'lista' => $lista]);
        exit;
    }

    // ── Flujo ADMIN: alta/edición completa de clientes ────────────────────
    if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $id   = isset($input['id']) && $input['id'] !== '' ? (int)$input['id'] : null;
    $tipo = $input['tipo'] ?? 'particular';
    $nombre = trim($input['nombre'] ?? '');

    if ($nombre === '') {
        throw new Exception('El nombre es obligatorio');
    }

    $data = [
        'tipo'      => $tipo,
        'nombre'    => $nombre,
        'apellidos' => $input['apellidos'] ?? '',
        'nif'       => $input['nif'] ?? null,
        'email'     => $input['email'] ?? null,
        'telefono'  => $input['telefono'] ?? null,
        'direccion' => $input['direccion'] ?? null,
        'cp'        => $input['cp'] ?? null,
        'poblacion' => $input['poblacion'] ?? null,
        'provincia' => $input['provincia'] ?? null,
        'es_socio'  => !empty($input['es_socio']) ? 1 : 0,
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
