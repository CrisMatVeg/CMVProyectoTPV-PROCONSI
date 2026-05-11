<?php

/**
 * Clase: ProductoPDO
 * Gestiona la persistencia de los productos mediante DBPDO.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
require_once __DIR__ . '/DBPDO.php';
require_once __DIR__ . '/Producto.php';
require_once __DIR__ . '/MovimientoStockPDO.php';

class ProductoPDO
{
    // Bloque de inicialización eliminado (Gestionado por scripts SQL externos)



    /**
     * Obtiene los productos de la base de datos.
     * @param bool $soloActivos Si es true, solo devuelve productos con activo=1. Default true.
     * @return Producto[]
     */
    public static function listarProductos(bool $soloActivos = true, int $limit = 50000, int $offset = 0, string $term = '', string $categoria = '', string $estado = 'all', $minPrice = null, $maxPrice = null, string $tag = '', bool $excluirPacks = false, string $sortBy = ''): array
    {
        $hoy = date('Y-m-d');

        $where = " WHERE 1=1 ";
        if ($excluirPacks) {
            $where .= " AND p.es_pack = 0 ";
        }
        $params = [];

        // Filtro de actividad / estado
        if ($estado === '1') {
            $where .= " AND p.activo = 1 ";
        } elseif ($estado === '0') {
            $where .= " AND p.activo = 0 ";
        } elseif ($estado === 'bajo_stock') {
            $where .= " AND p.stock_actual <= p.stock_minimo AND p.stock_minimo > 0 AND p.activo = 1 ";
        } elseif ($categoria === 'baja') {
            $where .= " AND p.activo = 0 ";
        } elseif ($soloActivos) {
            $where .= " AND p.activo = 1 ";
        }
        
        // Filtros de precio
        if ($minPrice !== null && $minPrice !== '') {
            $where .= " AND p.precio_venta >= :minPrice ";
            $params[':minPrice'] = (float)$minPrice;
        }
        if ($maxPrice !== null && $maxPrice !== '') {
            $where .= " AND p.precio_venta <= :maxPrice ";
            $params[':maxPrice'] = (float)$maxPrice;
        }

        // Filtro de búsqueda
        if ($term !== '') {
            $where .= " AND (p.nombre LIKE :term OR p.referencia LIKE :term) ";
            $params[':term'] = '%' . $term . '%';
        }

        // Filtro de categoría
        if ($categoria !== '' && $categoria !== 'all' && $categoria !== 'baja') {
            $where .= " AND p.categoria = :categoria ";
            $params[':categoria'] = $categoria;
        }

        // Filtro de Atributos (Tags)
        if (isset($tag) && $tag !== '') {
            // Intentamos usar JSON_CONTAINS si está disponible, o un LIKE como fallback seguro para el array JSON
            $where .= " AND (JSON_CONTAINS(p.atributos, JSON_QUOTE(:tag)) OR p.atributos LIKE :tagLike OR p.atributos = :tagPlain) ";
            $params[':tag'] = $tag;
            $params[':tagLike'] = '%"' . $tag . '"%';
            $params[':tagPlain'] = $tag;
        }

        $allowedSorts = [
            'name_asc'   => 'p.nombre ASC',
            'name_desc'  => 'p.nombre DESC',
            'price_asc'  => 'p.precio_venta ASC',
            'price_desc' => 'p.precio_venta DESC',
            'stock_asc'  => 'p.stock_actual ASC',
            'stock_desc' => 'p.stock_actual DESC',
        ];
        $orderBy = $allowedSorts[$sortBy] ?? 'p.id DESC';

        $sql = "SELECT p.id, p.referencia, p.nombre, p.descripcion, p.precio_coste, p.precio_venta,
                       p.stock_actual, p.stock_minimo, p.meses_garantia, p.icono, p.categoria,
                       p.atributos, p.activo, p.codigo_iva, p.es_pack, p.id_proveedor, p.margen, p.precio_proveedor, p.mantener_precision,
                       ti.codigo as codigo_iva_calculado, ti.porcentaje, pr.aplica_re
                FROM productos p
                LEFT JOIN tipos_iva ti ON ti.id = (
                    SELECT id FROM tipos_iva t2
                    WHERE t2.codigo = p.codigo_iva
                      AND t2.activo = 1
                      AND t2.fecha_inicio <= '$hoy'
                      AND (t2.fecha_fin IS NULL OR t2.fecha_fin >= '$hoy')
                    ORDER BY t2.fecha_inicio DESC
                    LIMIT 1
                )
                LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
                $where
                GROUP BY p.id
                ORDER BY {$orderBy}
                LIMIT :limit OFFSET :offset";
        
        $sql = str_replace([':limit', ':offset'], [(int)$limit, (int)$offset], $sql);
        $consulta = DBPDO::ejecutarConsulta($sql, $params);

        $productos = [];
        while ($registro = $consulta->fetch(PDO::FETCH_ASSOC)) {
            $esPack = $registro['es_pack'] ?? 0;
            
            if ($esPack) {
                $stock = self::calcularStockPack((int)$registro['id']);
            } else {
                $stock = (int)$registro['stock_actual'];
            }

            $productos[] = new Producto(
                $registro['id'],
                $registro['referencia'],
                $registro['nombre'],
                $registro['descripcion'],
                $registro['precio_coste'],
                $registro['precio_venta'],
                $stock,
                $registro['stock_minimo'],
                $registro['meses_garantia'],
                $registro['icono'],
                $registro['categoria'],
                $registro['atributos'],
                $registro['activo'],
                $registro['codigo_iva_calculado'] ?? $registro['codigo_iva'] ?? 'GENERAL',
                $esPack,
                $registro['aplica_re'] ?? 0,
                $registro['id_proveedor'] ?? null,
                $registro['porcentaje'] ?? 21.00,
                $registro['margen'] ?? 0.00,
                $registro['precio_proveedor'] ?? 0.00,
                $registro['mantener_precision'] ?? 0
            );
        }

        return $productos;
    }

    public static function listarPacks(): array
    {
        $hoy = date('Y-m-d');
        $sql = "SELECT p.*, ti.porcentaje, pr.aplica_re
                FROM productos p
                LEFT JOIN tipos_iva ti ON ti.id = (
                    SELECT id FROM tipos_iva t2
                    WHERE t2.codigo = p.codigo_iva
                      AND t2.activo = 1
                      AND t2.fecha_inicio <= '$hoy'
                      AND (t2.fecha_fin IS NULL OR t2.fecha_fin >= '$hoy')
                    ORDER BY t2.fecha_inicio DESC
                    LIMIT 1
                )
                LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
                WHERE p.es_pack = 1
                ORDER BY p.id DESC";

        $consulta = DBPDO::ejecutarConsulta($sql);
        $packs = [];
        while ($registro = $consulta->fetch(PDO::FETCH_ASSOC)) {
            $stock = self::calcularStockPack((int)$registro['id']);
            $p = new Producto(
                $registro['id'],
                $registro['referencia'],
                $registro['nombre'],
                $registro['descripcion'],
                $registro['precio_coste'],
                $registro['precio_venta'],
                $stock,
                $registro['stock_minimo'],
                $registro['meses_garantia'],
                $registro['icono'],
                $registro['categoria'],
                $registro['atributos'],
                $registro['activo'],
                $registro['codigo_iva'],
                1, // es_pack
                $registro['aplica_re'] ?? 0,
                $registro['id_proveedor'] ?? null,
                $registro['porcentaje'] ?? 21.00,
                $registro['margen'] ?? 0.00,
                $registro['precio_proveedor'] ?? 0.00,
                $registro['mantener_precision'] ?? 0
            );
            $packs[] = $p;
        }
        return $packs;
    }

    public static function contarProductos(bool $soloActivos = true, string $term = '', string $categoria = '', string $estado = 'all', $minPrice = null, $maxPrice = null, string $tag = '', bool $excluirPacks = false): int
    {
        $where = " WHERE 1=1 ";
        $params = [];

        if ($excluirPacks) {
            $where .= " AND es_pack = 0 ";
        }

        // Filtro de actividad / estado
        if ($estado === '1') {
            $where .= " AND activo = 1 ";
        } elseif ($estado === '0') {
            $where .= " AND activo = 0 ";
        } elseif ($estado === 'bajo_stock') {
            $where .= " AND stock_actual <= stock_minimo AND stock_minimo > 0 AND activo = 1 ";
        } elseif ($categoria === 'baja') {
            $where .= " AND activo = 0 ";
        } elseif ($soloActivos) {
            $where .= " AND activo = 1 ";
        }
        
        // Filtros de precio
        if ($minPrice !== null && $minPrice !== '') {
            $where .= " AND precio_venta >= :minPrice ";
            $params[':minPrice'] = (float)$minPrice;
        }
        if ($maxPrice !== null && $maxPrice !== '') {
            $where .= " AND precio_venta <= :maxPrice ";
            $params[':maxPrice'] = (float)$maxPrice;
        }

        if ($term !== '') {
            $where .= " AND (nombre LIKE :term OR referencia LIKE :term) ";
            $params[':term'] = '%' . $term . '%';
        }

        if ($categoria !== '' && $categoria !== 'all' && $categoria !== 'baja') {
            $where .= " AND categoria = :categoria ";
            $params[':categoria'] = $categoria;
        }

        // Filtro de Atributos (Tags)
        if (isset($tag) && $tag !== '') {
            $where .= " AND (JSON_CONTAINS(atributos, JSON_QUOTE(:tag)) OR atributos LIKE :tagLike OR atributos = :tagPlain) ";
            $params[':tag'] = $tag;
            $params[':tagLike'] = '%"' . $tag . '"%';
            $params[':tagPlain'] = $tag;
        }

        $sql = "SELECT COUNT(*) FROM productos $where";
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return (int)$q->fetchColumn();
    }

    /**
     * Calcula el stock disponible para un pack basado en sus productos individuales.
     */
    public static function calcularStockPack(int $id_pack): int
    {
        $componentes = self::obtenerComponentesPack($id_pack);
        if (empty($componentes)) return 0;

        $maxPacks = [];
        foreach ($componentes as $c) {
            $prodInfo = self::obtenerProductoPorId((int)$c['id_producto']);
            if (!$prodInfo) continue;

            $stockDisponible = (int)$prodInfo['stock_actual'];
            $cantidadNecesaria = (int)$c['cantidad'];

            if ($cantidadNecesaria > 0) {
                $maxPacks[] = floor($stockDisponible / $cantidadNecesaria);
            }
        }

        return !empty($maxPacks) ? (int)min($maxPacks) : 0;
    }

    public static function calcularPrecioCoste(float $precioProveedor, float $porcentajeRE): float
    {
        // Coste neto = precio_proveedor × (1 + RE/100)
        // El IVA soportado NO se incluye: queda neutralizado por el IVA repercutido al cliente.
        // Solo el recargo de equivalencia es un coste real no recuperable.
        return round($precioProveedor * (1 + ($porcentajeRE / 100)), 4);
    }

    /**
     * Añade un nuevo producto a la base de datos.
     */
    public static function añadirProducto(array $datos): array
    {
        $iconoDato = $datos['icono'] ?? '';
        if (strpos($iconoDato, 'data:image') === 0) {
            $parts = explode(',', $iconoDato);
            $iconoDato = base64_decode($parts[1]);
        }

        $ref = mb_substr(trim($datos['referencia'] ?? ''), 0, 50);
        if ($ref === '') {
            $ref = 'PACK-' . strtoupper(substr(uniqid(), -6));
        }

        $precioProveedor = (float)($datos['precio_proveedor'] ?? 0);
        $codigoIva = $datos['codigo_iva'] ?? 'GENERAL';
        $idProveedor = !empty($datos['id_proveedor']) ? (int)$datos['id_proveedor'] : null;
        $aplicaRE = 0;
        if ($idProveedor) {
            require_once __DIR__ . '/ProveedorPDO.php';
            $oProv = ProveedorPDO::buscarPorId($idProveedor);
            if ($oProv) {
                $aplicaRE = $oProv->getAplicaRe() ? 1 : 0;
            }
        }

        $precioCoste = (float)($datos['precio_coste'] ?? 0);

        $sql = "INSERT INTO productos (referencia, nombre, descripcion, precio_coste, precio_proveedor, precio_venta, stock_actual, stock_minimo, meses_garantia, icono, categoria, atributos, activo, codigo_iva, id_tipo_iva, es_pack, id_proveedor, margen, mantener_precision) 
                VALUES (:referencia, :nombre, :descripcion, :precio_coste, :precio_proveedor, :precio_venta, :stock_actual, :stock_minimo, :meses_garantia, :icono, :categoria, :atributos, :activo, :codigo_iva, :id_tipo_iva, :es_pack, :id_proveedor, :margen, :mantener_precision)";

        require_once __DIR__ . '/TipoIVAPDO.php';
        $oIva = TipoIVAPDO::obtenerVigentePorCodigo($codigoIva, date('Y-m-d'));
        $idTipoIva = $oIva['id'] ?? null;

        DBPDO::ejecutarConsulta($sql, [
            ':referencia'     => $ref,
            ':nombre'         => mb_substr(trim($datos['nombre']), 0, 100),
            ':descripcion'    => $datos['descripcion'] ?? '',
            ':precio_coste'   => $precioCoste,
            ':precio_venta'   => !empty($datos['mantener_precision']) ? (float)($datos['precio_venta'] ?? 0) : round((float)($datos['precio_venta'] ?? 0), 2),
            ':stock_actual'   => (int)($datos['stock_actual'] ?? 0),
            ':stock_minimo'   => (int)($datos['stock_minimo'] ?? 0),
            ':meses_garantia' => (int)($datos['meses_garantia'] ?? 24),
            ':icono'          => $iconoDato,
            ':categoria'      => mb_substr(trim($datos['categoria']), 0, 50),
            ':atributos'      => self::normalizarAtributos($datos['atributos'] ?? null),
            ':activo'         => isset($datos['activo']) ? (int)$datos['activo'] : 1,
            ':codigo_iva'     => $codigoIva,
            ':id_tipo_iva'    => $idTipoIva,
            ':es_pack'        => !empty($datos['es_pack']) ? 1 : 0,
            ':id_proveedor'   => $idProveedor,
            ':margen'         => (float)($datos['margen'] ?? 0),
            ':precio_proveedor' => $precioProveedor,
            ':mantener_precision' => !empty($datos['mantener_precision']) ? 1 : 0
        ]);


        $q = DBPDO::ejecutarConsulta("SELECT * FROM productos ORDER BY id DESC LIMIT 1");
        $p = $q->fetch(PDO::FETCH_ASSOC);

        // Guardar componentes si es pack
        if (!empty($datos['es_pack']) && isset($datos['componentes_pack']) && is_array($datos['componentes_pack'])) {
            self::sincronizarComponentesPack($p['id'], $datos['componentes_pack']);
        }

        // Registrar stock inicial si es > 0
        if (!empty($datos['stock_actual']) && (int)$datos['stock_actual'] > 0) {
            $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;
            MovimientoStockPDO::registrarMovimiento((int)$p['id'], 'inicial', (int)$datos['stock_actual'], $idUsuario, "Stock inicial");
            
            if ($precioCoste > 0) {
                // El registro de stock inicial requiere que la tabla entradas_stock exista (creada vía SQL)

                $sqlInsert = "INSERT INTO entradas_stock
                        (id_producto, cantidad, precio_coste, cmp_anterior, cmp_resultante,
                         stock_anterior, stock_nuevo, id_usuario, notas)
                      VALUES (:prod, :qty, :coste, 0, :cmpRes, 0, :stkNuevo, :usr, 'Coste inicial')";
                DBPDO::ejecutarConsulta($sqlInsert, [
                    ':prod'    => (int)$p['id'],
                    ':qty'     => (int)$datos['stock_actual'],
                    ':coste'   => $precioCoste,
                    ':cmpRes'  => $precioCoste,
                    ':stkNuevo' => (int)$datos['stock_actual'],
                    ':usr'     => $idUsuario
                ]);
            }
        }

        return $p;
    }

    /**
     * Actualiza los datos de un producto existente.
     */
    public static function editarProducto(int $id, array $datos): void
    {
        $iconoDato = $datos['icono'] ?? '';
        if (strpos($iconoDato, 'data:image') === 0) {
            $parts = explode(',', $iconoDato);
            $iconoDato = base64_decode($parts[1]);
        }

        $ref = mb_substr(trim($datos['referencia'] ?? ''), 0, 50);
        if ($ref === '') {
            $ref = 'PACK-' . strtoupper(substr(uniqid(), -6));
        }

        $codigoIva = $datos['codigo_iva'] ?? 'GENERAL';
        $idProveedor = !empty($datos['id_proveedor']) ? (int)$datos['id_proveedor'] : null;
        $aplicaRE = 0;
        if ($idProveedor) {
            require_once __DIR__ . '/ProveedorPDO.php';
            $oProv = ProveedorPDO::buscarPorId($idProveedor);
            if ($oProv) {
                $aplicaRE = $oProv->getAplicaRe() ? 1 : 0;
            }
        }

        $prodAntiguo = self::obtenerProductoPorId($id);
        $precioProveedor = isset($datos['precio_proveedor']) ? (float)$datos['precio_proveedor'] : (float)($prodAntiguo['precio_proveedor'] ?? 0);
        $precioCoste = isset($datos['precio_coste']) ? (float)$datos['precio_coste'] : (float)($prodAntiguo['precio_coste'] ?? 0);
        $stockActual = isset($datos['stock_actual']) ? (int)$datos['stock_actual'] : (int)($prodAntiguo['stock_actual'] ?? 0);
        
        $mantenerPrecision = isset($datos['mantener_precision']) ? (int)$datos['mantener_precision'] : (int)($prodAntiguo['mantener_precision'] ?? 0);
        $precioVentaNuevo = $mantenerPrecision ? (float)($datos['precio_venta'] ?? 0) : round((float)($datos['precio_venta'] ?? 0), 2);

        require_once 'TipoIVAPDO.php';
        $oIva = TipoIVAPDO::obtenerVigentePorCodigo($codigoIva, date('Y-m-d'));
        $idTipoIva = $oIva['id'] ?? null;

        $sql = "UPDATE productos
                SET referencia = :referencia, nombre = :nombre, descripcion = :descripcion, 
                    precio_coste = :precio_coste, precio_proveedor = :precio_proveedor, precio_venta = :precio_venta, 
                    stock_actual = :stock_actual, stock_minimo = :stock_minimo, meses_garantia = :meses_garantia, icono = :icono, 
                    categoria = :categoria, atributos = :atributos,
                    codigo_iva = :codigo_iva, id_tipo_iva = :id_tipo_iva,
                    id_proveedor = :id_proveedor, es_pack = :es_pack,
                    margen = :margen, mantener_precision = :mantener_precision
                WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':referencia'     => $ref,
            ':nombre'         => mb_substr(trim($datos['nombre']), 0, 100),
            ':descripcion'    => $datos['descripcion'] ?? '',
            ':precio_coste'   => $precioCoste,
            ':precio_venta'   => $precioVentaNuevo,
            ':stock_actual'   => $stockActual,
            ':stock_minimo'   => (int)($datos['stock_minimo'] ?? 0),
            ':meses_garantia' => (int)($datos['meses_garantia'] ?? 24),
            ':icono'          => $iconoDato,
            ':categoria'      => mb_substr(trim($datos['categoria']), 0, 50),
            ':atributos'      => self::normalizarAtributos($datos['atributos'] ?? null),
            ':codigo_iva'     => $codigoIva,
            ':id_tipo_iva'    => $idTipoIva,
            ':id_proveedor'   => $idProveedor,
            ':es_pack'        => !empty($datos['es_pack']) ? 1 : 0,
            ':margen'         => (float)($datos['margen'] ?? 0),
            ':precio_proveedor' => $precioProveedor,
            ':mantener_precision' => $mantenerPrecision,
            ':id'             => $id,
        ]);

        // Auditoría de cambio de precio
        if ($prodAntiguo && (float)$prodAntiguo['precio_venta'] !== $precioVentaNuevo) {
            $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;
            DBPDO::ejecutarConsulta(
                "INSERT INTO auditoria_precios_base (id_producto, precio_old, precio_new, motivo, id_usuario) 
                 VALUES (:p, :old, :new, :m, :u)",
                [
                    ':p'   => $id,
                    ':old' => (float)$prodAntiguo['precio_venta'],
                    ':new' => $precioVentaNuevo,
                    ':m'   => $datos['motivo_cambio_precio'] ?? 'Cambio manual en gestión',
                    ':u'   => $idUsuario
                ]
            );
        }

        // Auditoría de cambio de coste (CMP)
        if ($prodAntiguo && (float)$prodAntiguo['precio_coste'] !== $precioCoste) {
            $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;
            DBPDO::ejecutarConsulta(
                "INSERT INTO entradas_stock (id_producto, cantidad, precio_coste, cmp_anterior, cmp_resultante, stock_anterior, stock_nuevo, id_usuario, notas, fecha) 
                 VALUES (:prod, :qty, :coste, :cmpAnt, :cmpRes, :stkAnt, :stkNuevo, :usr, :notas, :fecha)",
                [
                    ':prod'      => $id,
                    ':qty'       => 0,
                    ':coste'     => $precioCoste,
                    ':cmpAnt'    => (float)$prodAntiguo['precio_coste'],
                    ':cmpRes'    => $precioCoste,
                    ':stkAnt'    => (int)$prodAntiguo['stock_actual'],
                    ':stkNuevo'  => (int)$prodAntiguo['stock_actual'],
                    ':usr'       => $idUsuario,
                    ':notas'     => "Ajuste manual de coste (CMP)",
                    ':fecha'     => date('Y-m-d H:i:s')
                ]
            );
        }

        // Registrar ajuste de stock si ha cambiado manualmente
        if ($prodAntiguo && (int)$prodAntiguo['stock_actual'] !== $stockActual) {
            $dif = $stockActual - (int)$prodAntiguo['stock_actual'];
            $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;
            MovimientoStockPDO::registrarMovimiento($id, 'ajuste', $dif, $idUsuario, "Ajuste manual en edición");
        }

        // Auditoría global de cambio de margen (solo para productos existentes)
        if ($prodAntiguo && isset($datos['margen'])) {
            $margenNuevo = (float)$datos['margen'];
            $margenAntiguo = (float)$prodAntiguo['margen'];
            
            if (abs($margenNuevo - $margenAntiguo) > 0.0001) {
                $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;
                self::registrarLogAjusteGlobal([
                    'id_usuario' => $idUsuario,
                    'tipo_operacion' => 'ajuste_manual_margen',
                    'valor' => $margenNuevo,
                    'tipo_valor' => 'percent',
                    'categoria_nom' => $prodAntiguo['categoria'],
                    'motivo' => $datos['motivo_cambio_precio'] ?? 'Ajuste manual',
                    'productos_afectados' => 1
                ]);
            }
        }

        // Guardar componentes si es pack
        if (!empty($datos['es_pack']) && isset($datos['componentes_pack']) && is_array($datos['componentes_pack'])) {
            self::sincronizarComponentesPack($id, $datos['componentes_pack']);
        }


    }

    /**
     * Recalcula el precio de coste de TODOS los productos.
     * Útil cuando cambian los tipos de IVA o sus recargos.
     */
    public static function recalcularPreciosCosteGlobal(): void
    {
        // Un solo UPDATE con JOIN en lugar de N queries individuales
        // Protección: no tocar productos con precio_proveedor=0 que ya tengan coste manual
        $sql = "UPDATE productos p
                LEFT JOIN proveedores pv ON p.id_proveedor = pv.id
                LEFT JOIN tipos_iva t ON p.id_tipo_iva = t.id
                SET p.precio_coste = ROUND(
                    p.precio_proveedor * (1 + COALESCE(IF(pv.aplica_re, t.recargo_equivalencia, 0), 0) / 100),
                    4
                )
                WHERE p.es_pack = 0
                AND NOT (p.precio_proveedor <= 0 AND p.precio_coste > 0)";
        DBPDO::ejecutarConsulta($sql);
    }

    public static function sincronizarComponentesPack(int $id_pack, array $componentes): void
    {
        DBPDO::ejecutarConsulta("DELETE FROM productos_pack WHERE id_pack = :id", [':id' => $id_pack]);
        foreach ($componentes as $comp) {
            $id_prod = (int)($comp['id_producto'] ?? 0);
            $qty = (int)($comp['cantidad'] ?? 1);
            if ($id_prod > 0 && $qty > 0) {
                DBPDO::ejecutarConsulta(
                    "INSERT INTO productos_pack (id_pack, id_producto, cantidad) VALUES (:p, :prod, :qty)",
                    [':p' => $id_pack, ':prod' => $id_prod, ':qty' => $qty]
                );
            }
        }
    }

    public static function obtenerComponentesPack(int $id_pack): array
    {
        $sql = "SELECT pp.*, p.nombre, p.referencia 
                FROM productos_pack pp 
                JOIN productos p ON pp.id_producto = p.id 
                WHERE pp.id_pack = :id";
        return DBPDO::ejecutarConsulta($sql, [':id' => $id_pack])->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function eliminarProducto(int $id): void
    {
        DBPDO::ejecutarConsulta("DELETE FROM productos WHERE id = :id", [':id' => $id]);
    }

    /**
     * Normaliza los atributos antes de guardarlos en BD.
     * Guarda el campo como un JSON Array simple Ej: ["Novedad", "Oferta"]
     */
    private static function normalizarAtributos($atributos): ?string
    {
        if ($atributos === null || $atributos === '' || $atributos === 'null' || $atributos === '[]') return null;

        $finalArray = [];

        if (is_string($atributos)) {
            $decoded = json_decode($atributos, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $finalArray = $decoded;
            } else {
                // Not a JSON array, treat as comma-separated string
                $finalArray = explode(',', $atributos);
            }
        } elseif (is_array($atributos)) {
            $finalArray = $atributos;
        }

        if (empty($finalArray)) return null;

        // Flatten (one level) and clean
        $clean = [];
        array_walk_recursive($finalArray, function($a) use (&$clean) {
            $val = trim((string)$a);
            if ($val !== '') $clean[] = $val;
        });

        $clean = array_values(array_unique($clean));

        if (count($clean) > 0) {
            return json_encode($clean, JSON_UNESCAPED_UNICODE);
        }
        
        return null;
    }

    public static function toggleBaja(int $id): bool
    {
        $q = DBPDO::ejecutarConsulta("SELECT activo FROM productos WHERE id = :id", [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $nuevoEstado = $row['activo'] ? 0 : 1;

        DBPDO::ejecutarConsulta("UPDATE productos SET activo = :activo WHERE id = :id", [':activo' => $nuevoEstado, ':id' => $id]);
        return (bool)$nuevoEstado;
    }

    public static function reducirStock(int $id, int $cantidad): void
    {
        $sql = "UPDATE productos SET stock_actual = stock_actual - :cantidad WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [':cantidad' => $cantidad, ':id' => $id]);
    }

    public static function aumentarStock(int $id, int $cantidad): void
    {
        $sql = "UPDATE productos SET stock_actual = stock_actual + :cantidad WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [':cantidad' => $cantidad, ':id' => $id]);
    }

    /**
     * Actualiza el stock y el Coste Medio Ponderado (CMP) tras una compra.
     * Formula: Nuevo CMP = (Stock * CMP_anterior + Cantidad * CosteAdquisicion) / (Stock + Cantidad)
     * CosteAdquisicion = precio_neto + RE (sin IVA — el IVA es un impuesto repercutido, no un coste)
     *
     * @param int $id ID del producto
     * @param int $cantidad Cantidad comprada
     * @param float $costeAdquisicion Coste unitario de esta entrada (neto + RE, sin IVA)
     * @param PDO|null $db Opcional: conexión PDO con transacción activa
     */
    public static function actualizarStockYCoste(int $id, int $cantidad, float $costeAdquisicion, $db = null): void
    {
        $producto = self::obtenerProductoPorId($id);
        if (!$producto) return;

        $stockAnterior = (int)$producto['stock_actual'];
        $costeAnterior = (float)$producto['precio_coste'];
        $pvpActual     = (float)$producto['precio_venta'];
        $margen        = (float)($producto['margen'] ?? 0);

        // Obtener IVA y RE del producto antes de calcular PVP
        $sqlIVA = "SELECT t.porcentaje, t.recargo_equivalencia, pr.aplica_re
                  FROM productos p
                  LEFT JOIN tipos_iva t ON p.id_tipo_iva = t.id
                  LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
                  WHERE p.id = :id";
        if ($db) {
            $stmtIVA = $db->prepare($sqlIVA);
            $stmtIVA->execute([':id' => $id]);
            $infoIVA = $stmtIVA->fetch(PDO::FETCH_ASSOC);
        } else {
            $qIVA = DBPDO::ejecutarConsulta($sqlIVA, [':id' => $id]);
            $infoIVA = $qIVA->fetch(PDO::FETCH_ASSOC);
        }
        $ivaPct = (float)($infoIVA['porcentaje'] ?? 21.0);
        $re_val = ($infoIVA['aplica_re'] && $infoIVA['recargo_equivalencia']) ? (float)$infoIVA['recargo_equivalencia'] : 0;

        // Calcular nuevo CMP
        if ($stockAnterior <= 0) {
            $nuevoCoste = $costeAdquisicion;
        } else {
            $nuevoCoste = (($stockAnterior * $costeAnterior) + ($cantidad * $costeAdquisicion)) / ($stockAnterior + $cantidad);
        }
        $nuevoCoste = round($nuevoCoste, 4);

        // PVP = CMP × (1 + margen%) × (1 + IVA%) — el CMP no incluye IVA
        $pvpNuevo = $pvpActual;
        if ($margen > 0) {
            $pvpNuevo = round($nuevoCoste * (1 + ($margen / 100)) * (1 + ($ivaPct / 100)), 2);
        }

        $sql = "UPDATE productos SET
                stock_actual  = stock_actual + :cantidad,
                precio_coste  = :nuevo_coste,
                precio_venta  = :pvp
                WHERE id = :id";

        $params = [
            ':cantidad'    => $cantidad,
            ':nuevo_coste' => round($nuevoCoste, 4),
            ':pvp'         => $pvpNuevo,
            ':id'          => $id
        ];

        // Revertir costeAdquisicion a precio_proveedor neto (sin RE, sin IVA)
        // costeAdquisicion = neto × (1 + RE%) → neto = costeAdquisicion / (1 + RE%)
        $neto = ($re_val > 0) ? $costeAdquisicion / (1 + ($re_val / 100)) : $costeAdquisicion;
        $sqlProv = "UPDATE productos SET precio_proveedor = :neto WHERE id = :id";

        if ($db) {
            $db->prepare($sql)->execute($params);
            $db->prepare($sqlProv)->execute([':neto' => round($neto, 4), ':id' => $id]);
        } else {
            DBPDO::ejecutarConsulta($sql, $params);
            DBPDO::ejecutarConsulta($sqlProv, [':neto' => round($neto, 4), ':id' => $id]);
        }

        // Auditoría si el PVP cambió
        if ($pvpNuevo !== $pvpActual && $margen > 0) {
            $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;
            $sqlAudit = "INSERT INTO auditoria_precios_base (id_producto, precio_old, precio_new, motivo, id_usuario) 
                         VALUES (:p, :old, :new, :m, :u)";
            $auditParams = [
                ':p'   => $id,
                ':old' => $pvpActual,
                ':new' => $pvpNuevo,
                ':m'   => "Ajuste automático por cambio de CMP (Margen: {$margen}%)",
                ':u'   => $idUsuario
            ];
            if ($db) {
                $db->prepare($sqlAudit)->execute($auditParams);
            } else {
                DBPDO::ejecutarConsulta($sqlAudit, $auditParams);
            }
        }
    }

    public static function obtenerProductoPorId(int $id): ?array
    {
        $hoy = date('Y-m-d');
        $sql = "SELECT p.*, ti.codigo as codigo_iva_calculado, ti.porcentaje, pr.aplica_re
                FROM productos p
                LEFT JOIN tipos_iva ti ON ti.id = (
                    SELECT id FROM tipos_iva t2
                    WHERE t2.codigo = p.codigo_iva
                      AND t2.activo = 1
                      AND t2.fecha_inicio <= '$hoy'
                      AND (t2.fecha_fin IS NULL OR t2.fecha_fin >= '$hoy')
                    ORDER BY t2.fecha_inicio DESC
                    LIMIT 1
                )
                LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
                WHERE p.id = :id";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (!empty($row['es_pack'])) {
                $row['stock_actual'] = self::calcularStockPack((int)$id);
            }
            return $row;
        }
        return null;
    }

    public static function obtenerProductoPorReferencia(string $referencia): ?array
    {
        $hoy = date('Y-m-d');
        $sql = "SELECT p.*, ti.codigo as codigo_iva_calculado, ti.porcentaje, pr.aplica_re
                FROM productos p
                LEFT JOIN tipos_iva ti ON ti.id = (
                    SELECT id FROM tipos_iva t2
                    WHERE t2.codigo = p.codigo_iva
                      AND t2.activo = 1
                      AND t2.fecha_inicio <= '$hoy'
                      AND (t2.fecha_fin IS NULL OR t2.fecha_fin >= '$hoy')
                    ORDER BY t2.fecha_inicio DESC
                    LIMIT 1
                )
                LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
                WHERE p.referencia = :referencia";
        $q = DBPDO::ejecutarConsulta($sql, [':referencia' => $referencia]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (!empty($row['es_pack'])) {
                $row['stock_actual'] = self::calcularStockPack((int)$row['id']);
            }
            return $row;
        }
        return null;
    }

    /**
     * Aplica un margen bruto masivo a todos los productos o a una categoría.
     * Recalcula precio_venta = precio_coste × (1 + margen/100).
     * Guarda el margen en el campo 'margen' del producto.
     * Skips productos con precio_coste = 0.
     *
     * @param float  $margen     Margen en porcentaje (ej. 25 para 25%)
     * @param string|null $categoria  Código de categoría, o null para aplicar a todos
     * @return array ['actualizados' => int, 'omitidos' => int]
     */
    public static function aplicarMargenMasivo(float $margen, ?string $categoria = null, array $excepciones = []): array
    {
        $where = "p.es_pack = 0 AND p.activo = 1 AND p.precio_coste > 0";
        $params = [];

        if ($categoria !== null && $categoria !== '') {
            $where .= " AND p.categoria = ?";
            $params[] = $categoria;
        }

        if (!empty($excepciones)) {
            $ids = [];
            $refs = [];
            foreach ($excepciones as $ex) {
                if (is_numeric($ex)) $ids[] = $ex;
                $refs[] = $ex;
            }
            $conds = [];
            if (!empty($ids)) {
                $phs = implode(',', array_fill(0, count($ids), '?'));
                $conds[] = "p.id IN ($phs)";
            }
            if (!empty($refs)) {
                $phs = implode(',', array_fill(0, count($refs), '?'));
                $conds[] = "p.referencia IN ($phs)";
            }
            if (!empty($conds)) {
                $where .= " AND NOT (" . implode(" OR ", $conds) . ")";
                if (!empty($ids)) foreach ($ids as $id) $params[] = $id;
                if (!empty($refs)) foreach ($refs as $ref) $params[] = $ref;
            }
        }

        $q = DBPDO::ejecutarConsulta(
            "SELECT p.id, p.precio_coste, p.precio_venta, COALESCE(t.porcentaje, 21.0) AS iva_pct
             FROM productos p
             LEFT JOIN tipos_iva t ON p.id_tipo_iva = t.id
             WHERE {$where}",
            $params
        );
        $productos = $q->fetchAll(PDO::FETCH_ASSOC);

        $actualizados = 0;
        $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;

        foreach ($productos as $p) {
            $ivaPct   = (float)($p['iva_pct'] ?? 21.0);
            $pvpNuevo = round((float)$p['precio_coste'] * (1 + ($margen / 100)) * (1 + ($ivaPct / 100)), 2);
            $pvpActual = (float)$p['precio_venta'];

            DBPDO::ejecutarConsulta(
                "UPDATE productos SET precio_venta = :pvp, margen = :margen WHERE id = :id",
                [':pvp' => $pvpNuevo, ':margen' => $margen, ':id' => $p['id']]
            );

            // Auditoría
            if ($pvpNuevo !== $pvpActual) {
                DBPDO::ejecutarConsulta(
                    "INSERT INTO auditoria_precios_base (id_producto, precio_old, precio_new, motivo, id_usuario) VALUES (:p, :old, :new, :m, :u)",
                    [
                        ':p'   => $p['id'],
                        ':old' => $pvpActual,
                        ':new' => $pvpNuevo,
                        ':m'   => "Margen masivo aplicado ({$margen}%)" . ($categoria ? " [Categoría: {$categoria}]" : " [Todos]"),
                        ':u'   => $idUsuario
                    ]
                );
            }
            $actualizados++;
        }

        // Contar los omitidos (precio_coste = 0)
        $whereOmit = "es_pack = 0 AND activo = 1 AND precio_coste <= 0";
        $paramsOmit = [];
        if ($categoria !== null && $categoria !== '') {
            $whereOmit .= " AND categoria = :cat";
            $paramsOmit[':cat'] = $categoria;
        }
        $qOmit = DBPDO::ejecutarConsulta("SELECT COUNT(*) as total FROM productos WHERE {$whereOmit}", $paramsOmit);
        $omitidos = (int)$qOmit->fetchColumn();

        return ['actualizados' => $actualizados, 'omitidos' => $omitidos];
    }

    public static function ajustePrecioMasivo(float $valor, string $tipo, ?string $categoria = null, array $excepciones = []): array
    {
        $where = "es_pack = 0 AND activo = 1";
        $params = [];
        
        if ($categoria !== null && $categoria !== '') {
            $where .= " AND categoria = ?";
            $params[] = $categoria;
        }

        if (!empty($excepciones)) {
            $ids = [];
            $refs = [];
            foreach ($excepciones as $ex) {
                if (is_numeric($ex)) $ids[] = $ex;
                $refs[] = $ex;
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

        $q = DBPDO::ejecutarConsulta("SELECT id, precio_venta FROM productos WHERE {$where}", $params);
        $productos = $q->fetchAll(PDO::FETCH_ASSOC);

        $actualizados = 0;
        $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;

        foreach ($productos as $p) {
            $pvpActual = (float)$p['precio_venta'];
            if ($tipo === 'percent') {
                $pvpNuevo = round($pvpActual * (1 + ($valor / 100.0)), 2);
            } else {
                $pvpNuevo = round($pvpActual + $valor, 2);
            }

            if ($pvpNuevo === $pvpActual) continue;

            // Recalcular el nuevo margen si el coste es mayor a 0
            // Margen = (PVP_sin_IVA / coste - 1) × 100
            $sqlProd = DBPDO::ejecutarConsulta(
                "SELECT p.precio_coste, COALESCE(t.porcentaje, 21.0) AS iva_pct
                 FROM productos p LEFT JOIN tipos_iva t ON p.id_tipo_iva = t.id WHERE p.id = ?",
                [$p['id']]
            );
            $rowProd = $sqlProd->fetch();
            $coste    = (float)($rowProd['precio_coste'] ?? 0);
            $ivaPct   = (float)($rowProd['iva_pct'] ?? 21.0);
            $pvpSinIva = $pvpNuevo / (1 + ($ivaPct / 100));
            $nuevoMargen = ($coste > 0) ? (($pvpSinIva / $coste) - 1) * 100 : 0;

            DBPDO::ejecutarConsulta(
                "UPDATE productos SET precio_venta = :pvp, margen = :margen WHERE id = :id",
                [':pvp' => $pvpNuevo, ':margen' => $nuevoMargen, ':id' => $p['id']]
            );

            // Auditoría
            DBPDO::ejecutarConsulta(
                "INSERT INTO auditoria_precios_base (id_producto, precio_old, precio_new, motivo, id_usuario, fecha) 
                 VALUES (:id, :old, :new, :m, :user, :fecha)",
                [
                    ':id'    => $p['id'],
                    ':old'   => $pvpActual,
                    ':new'   => $pvpNuevo,
                    ':m'     => "Ajuste masivo ({$valor}" . ($tipo === 'percent' ? '%' : '€') . ")",
                    ':user'  => $idUsuario,
                    ':fecha' => date('Y-m-d H:i:s')
                ]
            );
            $actualizados++;
        }
        return ['actualizados' => $actualizados];
    }

    public static function contarBajoStock()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND es_pack = 0 AND stock_actual <= stock_minimo";
            $consulta = DBPDO::ejecutarConsulta($sql);
            $registro = $consulta->fetch(PDO::FETCH_ASSOC);
            return (int)$registro['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Lista productos asignados a un proveedor.
     */
    public static function listarPorProveedor(int $idProveedor)
    {
        try {
            $sql = "SELECT id, referencia, nombre, stock_actual, precio_coste FROM productos WHERE id_proveedor = :id AND es_pack = 0 ORDER BY nombre ASC";
            $consulta = DBPDO::ejecutarConsulta($sql, [':id' => $idProveedor]);
            return $consulta->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Vincula masivamente una lista de productos a un proveedor.
     */
    public static function vincularAProveedor(int $idProveedor, array $idProductos)
    {
        if (empty($idProductos)) return true;
        try {
            $ids = implode(',', array_map('intval', $idProductos));
            $sql = "UPDATE productos SET id_proveedor = :id_prov WHERE id IN ($ids)";
            return DBPDO::ejecutarConsulta($sql, [':id_prov' => $idProveedor]) !== false;
        } catch (PDOException $e) {
            error_log("Error en vincularAProveedor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desvincula masivamente una lista de productos de cualquier proveedor.
     */
    public static function desvincularDeProveedor(array $idProductos)
    {
        if (empty($idProductos)) return true;
        try {
            $ids = implode(',', array_map('intval', $idProductos));
            $sql = "UPDATE productos SET id_proveedor = NULL WHERE id IN ($ids)";
            return DBPDO::ejecutarConsulta($sql) !== false;
        } catch (PDOException $e) {
            error_log("Error en desvincularDeProveedor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca productos del catálogo para vincularlos (opcionalmente excluyendo los que ya tiene el proveedor).
     */
    public static function buscarParaVincular(?string $termino = '', int $idProveedorExcluir = 0)
    {
        try {
            $sql = "SELECT id, nombre, referencia, precio_coste, stock_actual 
                    FROM productos 
                    WHERE es_pack = 0 AND activo = 1";
            $params = [];
            
            if ($termino) {
                $sql .= " AND (LOWER(nombre) LIKE LOWER(:term) OR LOWER(referencia) LIKE LOWER(:term))";
                $params[':term'] = "%$termino%";
            }
            
            if ($idProveedorExcluir > 0) {
                $sql .= " AND (id_proveedor IS NULL OR id_proveedor <> :id_excluir)";
                $params[':id_excluir'] = $idProveedorExcluir;
            }
            
            $sql .= " ORDER BY nombre ASC LIMIT 50";
            $consulta = DBPDO::ejecutarConsulta($sql, $params);
            return $consulta->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en buscarParaVincular: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registra un log de cambio masivo global.
     */
    public static function registrarLogAjusteGlobal(array $datos): bool
    {
        try {
            $sql = "INSERT INTO log_ajustes_globales 
                    (fecha, id_usuario, tipo_operacion, valor, tipo_valor, categoria_nom, motivo, productos_afectados)
                    VALUES (:fecha, :id_usuario, :tipo_op, :valor, :tipo_v, :cat_nom, :motivo, :afectados)";
            
            $params = [
                ':fecha'      => date('Y-m-d H:i:s'),
                ':id_usuario' => $datos['id_usuario'],
                ':tipo_op'    => $datos['tipo_operacion'],
                ':valor'       => $datos['valor'],
                ':tipo_v'     => $datos['tipo_valor'],
                ':cat_nom'    => $datos['categoria_nom'] ?? 'Todas',
                ':motivo'     => $datos['motivo'],
                ':afectados'  => $datos['productos_afectados']
            ];

            return DBPDO::ejecutarConsulta($sql, $params) !== false;
        } catch (PDOException $e) {
            error_log("Error en registrarLogAjusteGlobal: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista el historial de cambios globales.
     */
    public static function listarLogAjustesGlobales(): array
    {
        try {
            $sql = "SELECT l.*, u.nombre as nombre_usuario 
                    FROM log_ajustes_globales l
                    JOIN usuarios u ON l.id_usuario = u.id
                    ORDER BY l.fecha DESC LIMIT 100";
            $consulta = DBPDO::ejecutarConsulta($sql);
            return $consulta->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en listarLogAjustesGlobales: " . $e->getMessage());
            return [];
        }
    }
}
