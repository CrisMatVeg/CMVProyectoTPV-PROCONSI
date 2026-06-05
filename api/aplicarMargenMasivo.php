<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: aplicarMargenMasivo.php
 * Aplica un margen bruto a todos los productos o a una categoría específica.
 * Los productos con precio_coste = 0 se omiten automáticamente.
 */

require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/ProductoPDO.php';

header('Content-Type: application/json; charset=utf-8');

// Seguridad
if (!isset($_SESSION['usuarioActualTPV']) || !$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_tarifas')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

// Leer body JSON o POST convencional
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$margen    = isset($input['margen'])    ? (float)$input['margen']    : null;
$categoria = isset($input['categoria']) ? trim($input['categoria'])   : null;
if ($categoria === '' || $categoria === 'TODOS' || $categoria === 'all') $categoria = null;

// Validar
if ($margen === null || $margen < 0 || $margen > 500) {
    echo json_encode(['ok' => false, 'mensaje' => 'Margen inválido. Debe estar entre 0% y 500%.']);
    exit;
}

// Modo preview: solo contar, no aplicar
$preview = isset($input['preview']) && $input['preview'];
$excepciones = isset($input['excepciones']) ? (array)$input['excepciones'] : [];

if ($preview) {
    // Contar cuántos se actualizarían y cuántos se omitirían
    $where = "es_pack = 0 AND activo = 1";
    $params = [];
    if ($categoria) {
        $where .= " AND categoria = ?";
        $params[] = $categoria;
    }

    if (!empty($excepciones)) {
        $ids = [];
        $refs = [];
        foreach ($excepciones as $ex) {
            if (is_numeric($ex)) $ids[] = $ex;
            $refs[] = $ex; // Tratar como ref siempre, incluso si es numérico
        }
        $conds = [];
        if (!empty($ids)) {
            $phs = implode(',', array_fill(0, count($ids), '?'));
            $conds[] = "id IN ($phs)";
        }
        if (!empty($refs)) {
            $phs = implode(',', array_fill(0, count($refs), '?'));
            $conds[] = "referencia IN ($phs)";
        }
        if (!empty($conds)) {
            $where .= " AND NOT (" . implode(" OR ", $conds) . ")";
            if (!empty($ids)) foreach ($ids as $id) $params[] = $id;
            if (!empty($refs)) foreach ($refs as $ref) $params[] = $ref;
        }
    }

    $q = DBPDO::ejecutarConsulta("SELECT COUNT(*) FROM productos WHERE {$where} AND precio_coste > 0", $params);
    $actualizados = (int)$q->fetchColumn();

    $qO = DBPDO::ejecutarConsulta("SELECT COUNT(*) FROM productos WHERE {$where} AND precio_coste <= 0", $params);
    $omitidos = (int)$qO->fetchColumn();

    echo json_encode(['ok' => true, 'actualizados' => $actualizados, 'omitidos' => $omitidos]);
    exit;
}

// Aplicar

try {
    $motivo = isset($input['motivo']) ? trim($input['motivo']) : null;
    if (empty($motivo)) {
        echo json_encode(['ok' => false, 'mensaje' => 'El motivo del cambio es obligatorio.']);
        exit;
    }

    $resultado = ProductoPDO::aplicarMargenMasivo($margen, $categoria, $excepciones);

    // Registrar en el log global
    $catNombre = 'Todas';
    if ($categoria) {
        $qCat = DBPDO::ejecutarConsulta("SELECT nombre FROM categorias WHERE codigo = :c OR id = :c", [':c' => $categoria]);
        $rowCat = $qCat->fetch();
        if ($rowCat) $catNombre = $rowCat['nombre'];
    }

    $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;
    if ($idUsuario) {
        ProductoPDO::registrarLogAjusteGlobal([
            'id_usuario' => $idUsuario,
            'tipo_operacion' => 'margen_masivo',
            'valor' => $margen,
            'tipo_valor' => 'percent',
            'categoria_nom' => $catNombre,
            'motivo' => $motivo,
            'productos_afectados' => $resultado['actualizados']
        ]);
    }

    echo json_encode([
        'ok'          => true,
        'actualizados' => $resultado['actualizados'],
        'omitidos'    => $resultado['omitidos'],
        'mensaje'     => "Margen del {$margen}% aplicado a {$resultado['actualizados']} producto(s). {$resultado['omitidos']} omitido(s) por tener coste 0€."
    ]);
} catch (Throwable $e) {
    error_log("aplicarMargenMasivo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()]);
}
