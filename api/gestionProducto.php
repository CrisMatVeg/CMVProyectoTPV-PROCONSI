<?php
/**
 * API: gestionProducto.php
 * Gestiona las operaciones CRUD de productos (solo admins).
 * Acciones: añadir | editar | eliminar | baja
 * Devuelve JSON con el resultado.
 */
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/Producto.php';
    require_once __DIR__ . '/../model/ProductoPDO.php';
    require_once __DIR__ . '/../core/231018libreriaValidacion.php';

    // Auto-migración: crear columnas si no existen
    try { DBPDO::ejecutarConsulta("ALTER TABLE productos ADD COLUMN IF NOT EXISTS iva DECIMAL(5,2) DEFAULT 21.00"); } catch (Throwable $e) {}
    try { DBPDO::ejecutarConsulta("ALTER TABLE productos ADD COLUMN IF NOT EXISTS meses_garantia INT DEFAULT 24"); } catch (Throwable $e) {}
    try { DBPDO::ejecutarConsulta("ALTER TABLE productos ADD COLUMN IF NOT EXISTS requiere_serial TINYINT(1) DEFAULT 0"); } catch (Throwable $e) {}
    try { DBPDO::ejecutarConsulta("ALTER TABLE lineas_venta ADD COLUMN IF NOT EXISTS numero_serie VARCHAR(100) DEFAULT NULL"); } catch (Throwable $e) {}

    session_start();


    // Solo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    // Solo usuarios autenticados
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    // Solo administradores
    if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Acceso restringido a administradores']);
        exit;
    }

    $datos = json_decode(file_get_contents('php://input'), true);
    $accion = $datos['accion'] ?? '';

    if ($accion === 'añadir' || $accion === 'editar') {
        $aErrores = [
            'icono' => validacionFormularios::comprobarNoVacio($datos['icono'] ?? ''),
            'referencia' => validacionFormularios::comprobarAlfaNumerico($datos['referencia'] ?? '', 50, 3, 1),
            'nombre' => validacionFormularios::comprobarAlfaNumerico($datos['nombre'] ?? '', 100, 3, 1),
            'precio_coste' => validacionFormularios::comprobarFloat($datos['precio_coste'] ?? '', 1000000, 0, 1),
            'precio_venta' => validacionFormularios::comprobarFloat($datos['precio_venta'] ?? '', 1000000, 0, 1),
            'iva' => validacionFormularios::comprobarFloat($datos['iva'] ?? '', 100, 0, 1),
            'stock_actual' => validacionFormularios::comprobarEntero($datos['stock_actual'] ?? '', 1000000, 0, 1),
            'stock_minimo' => validacionFormularios::comprobarEntero($datos['stock_minimo'] ?? '', 1000000, 0, 1),
            'meses_garantia' => validacionFormularios::comprobarEntero($datos['meses_garantia'] ?? '', 120, 0, 1)
        ];

        $entradaOK = true;
        foreach ($aErrores as $e) {
            if ($e != null) $entradaOK = false;
        }

        if (!$entradaOK) {
            echo json_encode(['ok' => false, 'aErrores' => $aErrores]);
            exit;
        }
    }

    switch ($accion) {

        case 'añadir':
            $nuevo = ProductoPDO::añadirProducto($datos);
            echo json_encode([
                'ok'       => true,
                'producto' => [
                    'id'       => (int)$nuevo['id'],
                    'name'     => $nuevo['nombre'],
                    'codigo'   => $nuevo['referencia'],
                    'price'    => (float)$nuevo['precio_venta'],
                    'iva'      => (float)$nuevo['iva'],
                    'icono'    => $nuevo['icono'],
                    'cat'      => $nuevo['categoria'],
                    'stock'    => (int)$nuevo['stock_actual'],
                    'inactive' => false,
                ]
            ]);
            break;

        case 'editar':
            $id = (int)($datos['id'] ?? 0);
            if (!$id) throw new InvalidArgumentException('ID de producto inválido');
            ProductoPDO::editarProducto($id, $datos);
            // DEBUG: Leer valores reales de BD tras el UPDATE
            $qCheck = DBPDO::ejecutarConsulta("SELECT iva, meses_garantia, nombre, precio_venta FROM productos WHERE id = :id", [':id' => $id]);
            $rowCheck = $qCheck->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'ok' => true,
                '_debug' => [
                    'enviado_iva' => $datos['iva'] ?? 'NO_ENVIADO',
                    'enviado_meses' => $datos['meses_garantia'] ?? 'NO_ENVIADO',
                    'bd_iva' => $rowCheck['iva'] ?? 'ERROR',
                    'bd_meses' => $rowCheck['meses_garantia'] ?? 'ERROR',
                    'bd_nombre' => $rowCheck['nombre'] ?? 'ERROR',
                    'bd_precio' => $rowCheck['precio_venta'] ?? 'ERROR',
                ]
            ]);
            break;

        case 'eliminar':
            $id = (int)($datos['id'] ?? 0);
            if (!$id) throw new InvalidArgumentException('ID de producto inválido');
            ProductoPDO::eliminarProducto($id);
            echo json_encode(['ok' => true]);
            break;

        case 'baja':
            $id = (int)($datos['id'] ?? 0);
            if (!$id) throw new InvalidArgumentException('ID de producto inválido');
            $activo = ProductoPDO::toggleBaja($id);
            echo json_encode(['ok' => true, 'activo' => $activo]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => "Acción desconocida: $accion"]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
