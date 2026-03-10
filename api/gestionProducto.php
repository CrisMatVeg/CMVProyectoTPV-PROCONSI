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
    try {
        DBPDO::ejecutarConsulta("ALTER TABLE productos ADD COLUMN IF NOT EXISTS iva DECIMAL(5,2) DEFAULT 21.00");
    } catch (Throwable $e) {
    }
    try {
        DBPDO::ejecutarConsulta("ALTER TABLE productos ADD COLUMN IF NOT EXISTS meses_garantia INT DEFAULT 24");
    } catch (Throwable $e) {
    }

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
            'icono' => null, // Icono es opcional ahora
            'referencia' => validacionFormularios::comprobarAlfaNumerico($datos['referencia'] ?? '', 50, 3, 0),
            'nombre' => validacionFormularios::comprobarAlfaNumerico($datos['nombre'] ?? '', 100, 3, 1),
            'precio_proveedor' => validacionFormularios::comprobarFloat($datos['precio_proveedor'] ?? '', 1000000, 0, 1),
            'precio_venta' => validacionFormularios::comprobarFloat($datos['precio_venta'] ?? '', 1000000, 0, 1),
            'iva' => validacionFormularios::comprobarFloat($datos['iva'] ?? '', 100, 0, 0), // Optional as it might use codigo_iva
            'stock_actual' => validacionFormularios::comprobarEntero($datos['stock_actual'] ?? '', 1000000, 0, 0),
            'stock_minimo' => validacionFormularios::comprobarEntero($datos['stock_minimo'] ?? '', 1000000, 0, 0),
            'meses_garantia' => validacionFormularios::comprobarEntero($datos['meses_garantia'] ?? '', 120, 0, 1),
            'id_proveedor' => validacionFormularios::comprobarEntero($datos['id_proveedor'] ?? '', 1000000, 0, 0)
        ];



        // Limpiar errores (convertir strings vacíos a null)
        foreach ($aErrores as $clave => $error) {
            if ($error !== null && trim($error) === '') {
                $aErrores[$clave] = null;
            }
        }

        // Extra: prices must not be negative
        if ((float)($datos['precio_venta'] ?? 0) < 0) {
            echo json_encode(['ok' => false, 'error' => 'El precio de venta no puede ser negativo', 'aErrores' => ['precio_venta' => 'El precio de venta no puede ser negativo']]);
            exit;
        }
        if ((float)($datos['precio_coste'] ?? 0) < 0) {
            echo json_encode(['ok' => false, 'error' => 'El precio de coste no puede ser negativo', 'aErrores' => ['precio_coste' => 'El precio de coste no puede ser negativo']]);
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
                    'stock'    => !empty($nuevo['es_pack']) ? ProductoPDO::calcularStockPack((int)$nuevo['id']) : (int)$nuevo['stock_actual'],
                    'inactive' => false,
                    'variantes' => json_decode($nuevo['variantes'] ?? '[]', true),
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

        case 'reparar_precios':
            // Maintenance: Fix any negative prices in the DB by taking absolute value
            DBPDO::ejecutarConsulta("UPDATE productos SET precio_venta = ABS(precio_venta) WHERE precio_venta < 0");
            DBPDO::ejecutarConsulta("UPDATE productos SET precio_coste = ABS(precio_coste) WHERE precio_coste < 0");
            DBPDO::ejecutarConsulta("UPDATE producto_variantes SET precio_venta = ABS(precio_venta) WHERE precio_venta < 0");
            echo json_encode(['ok' => true, 'mensaje' => 'Precios negativos corregidos a su valor absoluto']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => "Acción desconocida: $accion"]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
