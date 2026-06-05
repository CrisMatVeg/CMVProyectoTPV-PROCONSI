<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: ajusteUnificado.php
 * Ajuste masivo de precios unificado: tipos percent | amount | margin | round.
 * Soporta plazo de aplicación diferida.
 */

require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/ProductoPDO.php';
require_once __DIR__ . '/../model/Usuario.php';
require_once __DIR__ . '/../model/LogPDO.php';

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método no permitido', 405);

    if (!isset($_SESSION['usuarioActualTPV']) || !$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_tarifas')) {
        throw new Exception('No autorizado', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $tipo      = trim($input['tipo'] ?? 'percent');
    $valor     = isset($input['valor']) ? (float)$input['valor'] : null;
    $redondeoA = isset($input['redondeo_a']) ? (float)$input['redondeo_a'] : 1.0;
    $categoria = trim($input['categoria'] ?? '');
    $preview   = !empty($input['preview']);
    $excepciones  = (array)($input['excepciones'] ?? []);
    $motivo       = trim($input['motivo'] ?? '');
    $fechaAplicacion = trim($input['fecha_aplicacion'] ?? '');

    if (!in_array($tipo, ['percent', 'amount', 'margin', 'round'], true)) {
        throw new Exception('Tipo de ajuste no válido');
    }
    if ($valor === null) throw new Exception('Valor de ajuste no especificado');
    if ($categoria === '' || $categoria === 'all') $categoria = null;

    if ($preview) {
        // Solo contar productos afectados sin aplicar
        $needsCoste = ($tipo === 'margin');
        $where  = $needsCoste ? "p.es_pack = 0 AND p.activo = 1 AND p.precio_coste > 0"
                              : "es_pack = 0 AND activo = 1";
        $params = [];
        if ($categoria) {
            $where .= $needsCoste ? " AND p.categoria = ?" : " AND categoria = ?";
            $params[] = $categoria;
        }
        $ids = array_filter($excepciones, 'is_numeric');
        if (!empty($ids)) {
            $phs = implode(',', array_fill(0, count($ids), '?'));
            $cond = $needsCoste ? "p.id NOT IN ($phs)" : "id NOT IN ($phs)";
            $where .= " AND $cond";
            foreach ($ids as $id) $params[] = $id;
        }
        $table = $needsCoste ? "productos p" : "productos";
        $total = (int)DBPDO::ejecutarConsulta("SELECT COUNT(*) FROM {$table} WHERE {$where}", $params)->fetchColumn();
        echo json_encode(['ok' => true, 'total' => $total]);
        exit;
    }

    if (empty($motivo)) throw new Exception('El motivo del ajuste es obligatorio');

    $resultado = ProductoPDO::ajusteMasivoUnificado(
        $tipo, $valor, $categoria, $excepciones, $motivo, $redondeoA,
        $fechaAplicacion ?: null
    );

    if (!empty($resultado['pendiente'])) {
        echo json_encode([
            'ok'     => true,
            'pendiente' => true,
            'mensaje' => "Ajuste programado para el {$resultado['fecha_aplicacion']}. Se aplicará automáticamente.",
        ]);
        exit;
    }

    // Registrar en log global (ya aplicado)
    $catNombre = 'Todas';
    if ($categoria) {
        $qCat = DBPDO::ejecutarConsulta("SELECT nombre FROM categorias WHERE codigo = :c OR id = :c", [':c' => $categoria]);
        $rowCat = $qCat->fetch();
        if ($rowCat) $catNombre = $rowCat['nombre'];
    }

    ProductoPDO::registrarLogAjusteGlobal([
        'id_usuario'          => $_SESSION['usuarioActualTPV']->getId(),
        'tipo_operacion'      => 'ajuste_' . $tipo,
        'valor'               => $valor,
        'tipo_valor'          => $tipo,
        'categoria_nom'       => $catNombre,
        'motivo'              => $motivo,
        'productos_afectados' => $resultado['actualizados'],
    ]);

    LogPDO::addLog('AJUSTE_MASIVO_PRECIOS', "Ajuste {$tipo} de {$valor} aplicado a {$resultado['actualizados']} producto(s) (cat: {$catNombre})", [
        'tipo' => $tipo, 'valor' => $valor, 'categoria' => $catNombre,
        'motivo' => $motivo, 'productos_afectados' => $resultado['actualizados'],
    ]);

    $msg = "Ajuste aplicado a {$resultado['actualizados']} producto(s).";
    if (!empty($resultado['omitidos'])) {
        $msg .= " {$resultado['omitidos']} omitido(s) (sin coste o precio inválido).";
    }

    echo json_encode([
        'ok'          => true,
        'actualizados' => $resultado['actualizados'],
        'omitidos'    => $resultado['omitidos'] ?? 0,
        'mensaje'     => $msg,
    ]);

} catch (Throwable $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
