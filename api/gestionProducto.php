<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: gestionProducto.php
 * Gestiona las operaciones CRUD de productos (solo admins).
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
    require_once __DIR__ . '/../model/MovimientoStockPDO.php';
    require_once __DIR__ . '/../core/231018libreriaValidacion.php';

    // session_start(); // Handled by csrf_check.php

    // Solo POST
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $datos['accion'] ?? $_GET['accion'] ?? '';

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


    if ($accion === 'añadir' || $accion === 'editar') {
        // Sanitizar campos solo si están presentes pero vacíos. Si no vienen, se respetan los valores actuales del modelo.
        if (isset($datos['precio_venta']) && ($datos['precio_venta'] === '' || $datos['precio_venta'] === null)) {
            $datos['precio_venta'] = 0;
        }
        if (isset($datos['precio_coste']) && ($datos['precio_coste'] === '' || $datos['precio_coste'] === null)) {
            $datos['precio_coste'] = 0;
        }
        if (isset($datos['stock_actual']) && ($datos['stock_actual'] === '' || $datos['stock_actual'] === null)) {
            $datos['stock_actual'] = 0;
        }

        $aErrores = [
            'icono' => null,
            'referencia' => validacionFormularios::comprobarAlfaNumerico($datos['referencia'] ?? '', 50, 3, 0),
            'nombre' => validacionFormularios::comprobarAlfaNumerico($datos['nombre'] ?? '', 100, 3, 1),
            'precio_venta' => validacionFormularios::comprobarFloat($datos['precio_venta'] ?? '', 1000000, 0, 0), // Opcional
            'precio_coste' => validacionFormularios::comprobarFloat($datos['precio_coste'] ?? '', 1000000, 0, 0), // Opcional
            'stock_actual' => validacionFormularios::comprobarEntero($datos['stock_actual'] ?? '', 1000000, 0, 0), // Opcional
            'stock_minimo' => validacionFormularios::comprobarEntero($datos['stock_minimo'] ?? '', 1000000, 0, 0),
            'meses_garantia' => validacionFormularios::comprobarEntero($datos['meses_garantia'] ?? '', 120, 0, 1),
            'id_proveedor' => validacionFormularios::comprobarEntero($datos['id_proveedor'] ?? '', 1000000, 0, 0),
            'margen' => validacionFormularios::comprobarFloat($datos['margen'] ?? '', 1000, 0, 0)
        ];

        // Lógica de producto inactivo por defecto si falta precio o stock (solo al añadir)
        if ($accion === 'añadir') {
            $hasPrice = !empty($datos['precio_venta']) && (float)$datos['precio_venta'] > 0;
            $hasStock = !empty($datos['stock_actual']) && (int)$datos['stock_actual'] > 0;
            
            if (!$hasPrice || !$hasStock) {
                $datos['activo'] = 0;
            } else {
                $datos['activo'] = 1;
            }
        }

        foreach ($aErrores as $clave => $error) {
            if ($error !== null && trim($error) === '') {
                $aErrores[$clave] = null;
            }
        }

        if ((float)($datos['precio_venta'] ?? 0) < 0) {
            echo json_encode(['ok' => false, 'error' => 'El precio de venta no puede ser negativo', 'aErrores' => ['precio_venta' => 'El precio de venta no puede ser negativo']]);
            exit;
        }

        $entradaOK = true;
        foreach ($aErrores as $e) {
            if ($e != null) $entradaOK = false;
        }

        if (!$entradaOK) {
            echo json_encode(['ok' => false, 'error' => 'Error de validación en los datos del formulario', 'aErrores' => $aErrores]);
            exit;
        }
    }

    switch ($accion) {
        case 'listar':
            $limit = isset($datos['limit']) ? (int)$datos['limit'] : 50;
            $offset = isset($datos['offset']) ? (int)$datos['offset'] : 0;
            $term = isset($datos['term']) ? trim($datos['term']) : '';
            $cat = isset($datos['cat']) ? trim($datos['cat']) : '';

            $lista = ProductoPDO::listarProductos(false, $limit, $offset, $term, $cat);
            $total = ProductoPDO::contarProductos(false, $term, $cat);

            $formatted = array_map(function($p) {
                $icono = $p->getIcono();
                if ($icono && strlen($icono) > 10) {
                    $icono = 'data:image/png;base64,' . base64_encode($icono);
                }

                return [
                    'id' => $p->getId(),
                    'nombre' => $p->getNombre(),
                    'referencia' => $p->getReferencia(),
                    'icono' => $icono,
                    'es_pack' => (int)$p->isPack(),
                    'precio_venta' => (float)$p->getPrecioVenta(),
                    'categoria' => $p->getCategoria(),
                    'stock' => (int)$p->getStockActual(),
                    'stock_minimo' => (int)$p->getStockMinimo(),
                    'activo' => (int)$p->getActivo(),
                    'atributos' => $p->getAtributos(),
                    'componentes_pack' => $p->isPack() ? ProductoPDO::obtenerComponentesPack($p->getId()) : []
                ];
            }, $lista);
            echo json_encode(['ok' => true, 'productos' => $formatted, 'total' => $total]);
            break;

        case 'añadir':
            $nuevo = ProductoPDO::añadirProducto($datos);
            echo json_encode([
                'ok'       => true,
                'producto' => [
                    'id'       => (int)$nuevo['id'],
                    'name'     => $nuevo['nombre'],
                    'codigo'   => $nuevo['referencia'],
                    'price'    => (float)$nuevo['precio_venta'],
                    'icono'    => $nuevo['icono'],
                    'cat'      => $nuevo['categoria'],
                    'stock'    => !empty($nuevo['es_pack']) ? ProductoPDO::calcularStockPack((int)$nuevo['id']) : (int)$nuevo['stock_actual'],
                    'inactive' => false,
                    'es_pack'  => (int)($nuevo['es_pack'] ?? 0),
                    'componentes_pack' => !empty($nuevo['es_pack']) ? ProductoPDO::obtenerComponentesPack((int)$nuevo['id']) : []
                ]
            ]);
            break;

        case 'editar':
            $id = (int)($datos['id'] ?? 0);
            if (!$id) throw new InvalidArgumentException('ID de producto inválido');
            ProductoPDO::editarProducto($id, $datos);
            echo json_encode(['ok' => true]);
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

        case 'ajuste_stock':
            $id = (int)($datos['id'] ?? 0);
            $cantidad = (int)($datos['cantidad'] ?? 0);
            $motivo = trim($datos['motivo'] ?? '');
            
            if (!$id) throw new InvalidArgumentException('ID de producto inválido');
            if ($cantidad === 0) throw new InvalidArgumentException('La cantidad no puede ser 0');
            if (empty($motivo)) throw new InvalidArgumentException('El motivo es obligatorio para ajustes manuales');

            $idUsuario = $_SESSION['usuarioActualTPV']->getId();
            
            if ($cantidad > 0) {
                ProductoPDO::aumentarStock($id, $cantidad);
            } else {
                ProductoPDO::reducirStock($id, abs($cantidad));
            }
            
            MovimientoStockPDO::registrarMovimiento($id, 'ajuste', $cantidad, $idUsuario, $motivo);
            echo json_encode(['ok' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => "Acción desconocida: $accion"]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
