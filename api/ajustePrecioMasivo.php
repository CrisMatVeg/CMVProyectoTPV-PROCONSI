<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: ajustePrecioMasivo.php
 * Aplica un ajuste de precio (subida/bajada) a todos los productos o a una categoría específica.
 */

require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/ProductoPDO.php';
require_once __DIR__ . '/../model/Usuario.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    if (!isset($_SESSION['usuarioActualTPV']) || !$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_tarifas')) {
        throw new Exception('No autorizado', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $valor     = isset($input['valor'])     ? (float)$input['valor']     : null;
    $tipo      = isset($input['tipo'])      ? trim($input['tipo'])       : 'percent'; // 'percent' o 'amount'
    $categoria = isset($input['categoria']) ? trim($input['categoria'])   : null;
    $preview   = isset($input['preview'])   && $input['preview'];
    $excepciones = isset($input['excepciones']) ? (array)$input['excepciones'] : [];
    $motivo    = isset($input['motivo'])    ? trim($input['motivo'])      : null;

    if ($categoria === '' || $categoria === 'TODOS' || $categoria === 'all') $categoria = null;

    if ($valor === null) {
        throw new Exception('Valor de ajuste no especificado');
    }

    if ($preview) {
        $where = "es_pack = 0 AND activo = 1";
        $params = [];
        if ($categoria) {
            $where .= " AND categoria = ?";
            $params[] = $categoria;
        }

        if (!empty($excepciones)) {
            $ids = [];
            foreach ($excepciones as $ex) if (is_numeric($ex)) $ids[] = $ex;
            if (!empty($ids)) {
                $phs = implode(',', array_fill(0, count($ids), '?'));
                $where .= " AND id NOT IN ($phs)";
                foreach ($ids as $id) $params[] = $id;
            }
        }

        $q = DBPDO::ejecutarConsulta("SELECT COUNT(*) FROM productos WHERE {$where}", $params);
        $total = (int)$q->fetchColumn();
        echo json_encode(['ok' => true, 'total' => $total]);
        exit;
    }

    if (empty($motivo)) {
        throw new Exception('El motivo del ajuste es obligatorio');
    }

    $resultado = ProductoPDO::ajustePrecioMasivo($valor, $tipo, $categoria, $excepciones);
    
    // Registrar en el log global
    $catNombre = 'Todas';
    if ($categoria) {
        $qCat = DBPDO::ejecutarConsulta("SELECT nombre FROM categorias WHERE codigo = :c OR id = :c", [':c' => $categoria]);
        $rowCat = $qCat->fetch();
        if ($rowCat) $catNombre = $rowCat['nombre'];
    }

    ProductoPDO::registrarLogAjusteGlobal([
        'id_usuario' => $_SESSION['usuarioActualTPV']->getId(),
        'tipo_operacion' => 'ajuste_masivo',
        'valor' => $valor,
        'tipo_valor' => $tipo,
        'categoria_nom' => $catNombre,
        'motivo' => $motivo,
        'productos_afectados' => $resultado['actualizados']
    ]);

    echo json_encode([
        'ok' => true,
        'actualizados' => $resultado['actualizados'],
        'mensaje' => "Ajuste aplicado correctamente a {$resultado['actualizados']} productos."
    ]);

} catch (Throwable $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
