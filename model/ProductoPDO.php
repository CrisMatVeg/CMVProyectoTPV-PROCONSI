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

    /**
     * Obtiene los productos de la base de datos.
     * @param bool $soloActivos Si es true, solo devuelve productos con activo=1. Default true.
     * @return Producto[]
     */
    public static function listarProductos(bool $soloActivos = true): array
    {
        $sql = "SELECT * FROM productos" . ($soloActivos ? " WHERE activo = 1" : "");
        $consulta = DBPDO::ejecutarConsulta($sql);

        $productos = [];
        while ($registro = $consulta->fetch(PDO::FETCH_ASSOC)) {
            $esPack = $registro['es_pack'] ?? 0;
            $stock = (int)$registro['stock_actual'];

            // Si es un pack, el stock es dinámico basado en componentes
            if ($esPack) {
                $stock = self::calcularStockPack((int)$registro['id']);
            }

            $productos[] = new Producto(
                $registro['id'],
                $registro['referencia'],
                $registro['nombre'],
                $registro['descripcion'],
                $registro['precio_coste'],
                $registro['precio_venta'],
                $registro['iva'],
                $stock,
                $registro['stock_minimo'],
                $registro['meses_garantia'],
                $registro['icono'],
                $registro['categoria'],
                $registro['variantes'],
                $registro['atributos'],
                $registro['activo'],
                $registro['codigo_iva'] ?? 'GENERAL',
                $esPack,
                $registro['precio_proveedor'] ?? 0,
                0, // RE now handled by provider
                $registro['id_proveedor'] ?? null
            );
        }

        return $productos;
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

    /**
     * Calcula el precio de coste basado en el precio de proveedor, IVA y Recargo de Equivalencia.
     */
    public static function calcularPrecioCoste(float $precioProveedor, string $codigoIva, int $aplicaRE): float
    {
        require_once __DIR__ . '/TipoIVAPDO.php';
        $tipoIva = TipoIVAPDO::obtenerVigentePorCodigo($codigoIva, date('Y-m-d'));

        $porcentajeIva = (float)($tipoIva['porcentaje'] ?? 21.00);
        $porcentajeRE = $aplicaRE ? (float)($tipoIva['recargo_equivalencia'] ?? 0.00) : 0.00;

        // Formula: PVP_Base + IVA + RE
        // Coste = Proveedor * (1 + (IVA/100) + (RE/100))
        $totalSurcharge = 1 + ($porcentajeIva / 100) + ($porcentajeRE / 100);
        return round($precioProveedor * $totalSurcharge, 2);
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

        // Calcular precio_coste automáticamente
        $precioCoste = self::calcularPrecioCoste($precioProveedor, $codigoIva, $aplicaRE);

        $sql = "INSERT INTO productos (referencia, nombre, descripcion, precio_coste, precio_venta, iva, stock_actual, stock_minimo, meses_garantia, icono, categoria, variantes, atributos, activo, codigo_iva, es_pack, precio_proveedor, id_proveedor)
                VALUES (:referencia, :nombre, :descripcion, :precio_coste, :precio_venta, :iva, :stock_actual, :stock_minimo, :meses_garantia, :icono, :categoria, :variantes, :atributos, 1, :codigo_iva, :es_pack, :precio_proveedor, :id_proveedor)";

        DBPDO::ejecutarConsulta($sql, [
            ':referencia'     => $ref,
            ':nombre'         => mb_substr(trim($datos['nombre']), 0, 100),
            ':descripcion'    => $datos['descripcion'] ?? '',
            ':precio_coste'   => $precioCoste,
            ':precio_venta'   => round((float)($datos['precio_venta'] ?? 0), 2),
            ':iva'            => round((float)($datos['iva'] ?? 21), 2),
            ':stock_actual'   => (int)($datos['stock_actual'] ?? 0),
            ':stock_minimo'   => (int)($datos['stock_minimo'] ?? 0),
            ':meses_garantia' => (int)($datos['meses_garantia'] ?? 24),
            ':icono'          => $iconoDato,
            ':categoria'      => mb_substr(trim($datos['categoria']), 0, 50),
            ':variantes'      => self::normalizarVariantes($datos['variantes'] ?? null),
            ':atributos'      => self::normalizarAtributos($datos['atributos'] ?? null),
            ':codigo_iva'     => $codigoIva,
            ':es_pack'        => !empty($datos['es_pack']) ? 1 : 0,
            ':precio_proveedor' => $precioProveedor,
            ':id_proveedor'   => $idProveedor
        ]);


        $q = DBPDO::ejecutarConsulta("SELECT * FROM productos ORDER BY id DESC LIMIT 1");
        require_once __DIR__ . '/VariantePDO.php';
        $p = $q->fetch(PDO::FETCH_ASSOC);
        VariantePDO::sincronizarVariantes($p['id'], $p['referencia'], $p['variantes']);

        // Guardar componentes si es pack
        if (!empty($datos['es_pack']) && isset($datos['componentes_pack']) && is_array($datos['componentes_pack'])) {
            self::sincronizarComponentesPack($p['id'], $datos['componentes_pack']);
        }

        // Registrar stock inicial si es > 0
        if (!empty($datos['stock_actual']) && (int)$datos['stock_actual'] > 0) {
            $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;
            MovimientoStockPDO::registrarMovimiento((int)$p['id'], 'inicial', (int)$datos['stock_actual'], $idUsuario, "Stock inicial");
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

        $precioCoste = self::calcularPrecioCoste($precioProveedor, $codigoIva, $aplicaRE);

        $prodAntiguo = self::obtenerProductoPorId($id);
        $stockActual = (int)($datos['stock_actual'] ?? ($prodAntiguo['stock_actual'] ?? 0));

        $sql = "UPDATE productos
                SET referencia = :referencia, nombre = :nombre, descripcion = :descripcion, 
                    precio_coste = :precio_coste, precio_venta = :precio_venta, iva = :iva, 
                    stock_actual = :stock_actual, stock_minimo = :stock_minimo, meses_garantia = :meses_garantia, icono = :icono, 
                    categoria = :categoria, variantes = :variantes, atributos = :atributos,
                    codigo_iva = :codigo_iva, precio_proveedor = :precio_proveedor,
                    id_proveedor = :id_proveedor, es_pack = :es_pack
                WHERE id = :id";

        $vars = self::normalizarVariantes($datos['variantes'] ?? null);

        DBPDO::ejecutarConsulta($sql, [
            ':referencia'     => $ref,
            ':nombre'         => mb_substr(trim($datos['nombre']), 0, 100),
            ':descripcion'    => $datos['descripcion'] ?? '',
            ':precio_coste'   => $precioCoste,
            ':precio_venta'   => round((float)($datos['precio_venta'] ?? 0), 2),
            ':iva'            => round((float)($datos['iva'] ?? 21), 2),
            ':stock_actual'   => $stockActual,
            ':stock_minimo'   => (int)($datos['stock_minimo'] ?? 0),
            ':meses_garantia' => (int)($datos['meses_garantia'] ?? 24),
            ':icono'          => $iconoDato,
            ':categoria'      => mb_substr(trim($datos['categoria']), 0, 50),
            ':variantes'      => $vars,
            ':atributos'      => self::normalizarAtributos($datos['atributos'] ?? null),
            ':codigo_iva'     => $codigoIva,
            ':precio_proveedor' => $precioProveedor,
            ':id_proveedor'   => $idProveedor,
            ':es_pack'        => !empty($datos['es_pack']) ? 1 : 0,
            ':id'             => $id,
        ]);

        // Registrar ajuste de stock si ha cambiado manualmente
        if ($prodAntiguo && (int)$prodAntiguo['stock_actual'] !== $stockActual) {
            $dif = $stockActual - (int)$prodAntiguo['stock_actual'];
            $idUsuario = isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getId() : null;
            MovimientoStockPDO::registrarMovimiento($id, 'ajuste', $dif, $idUsuario, "Ajuste manual en edición");
        }

        require_once __DIR__ . '/VariantePDO.php';
        VariantePDO::sincronizarVariantes($id, $datos['referencia'], $vars);

        // Guardar componentes si es pack
        if (!empty($datos['es_pack']) && isset($datos['componentes_pack']) && is_array($datos['componentes_pack'])) {
            self::sincronizarComponentesPack($id, $datos['componentes_pack']);
        }

        // Guardar cambios en variantes físicas si se proporcionan
        if (!empty($datos['variantes_fisicas']) && is_array($datos['variantes_fisicas'])) {
            foreach ($datos['variantes_fisicas'] as $v) {
                VariantePDO::editar((int)$v['id'], $v);
            }
        }
    }

    /**
     * Recalcula el precio de coste de TODOS los productos.
     * Útil cuando cambian los tipos de IVA o sus recargos.
     */
    public static function recalcularPreciosCosteGlobal(): void
    {
        $sql = "SELECT p.id, p.precio_proveedor, p.codigo_iva, pv.aplica_re 
                FROM productos p
                LEFT JOIN proveedores pv ON p.id_proveedor = pv.id
                WHERE p.es_pack = 0";
        $stmt = DBPDO::ejecutarConsulta($sql);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productos as $p) {
            $nuevoCoste = self::calcularPrecioCoste(
                (float)$p['precio_proveedor'],
                $p['codigo_iva'],
                (int)($p['aplica_re'] ?? 0)
            );

            DBPDO::ejecutarConsulta(
                "UPDATE productos SET precio_coste = :coste WHERE id = :id",
                [':coste' => $nuevoCoste, ':id' => $p['id']]
            );
        }
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

        if (is_string($atributos)) {
            $decoded = json_decode($atributos, true);
            if (json_last_error() !== JSON_ERROR_NONE || $decoded === null) return null;
            $atributos = $decoded;
        }

        if (empty($atributos)) return null;

        if (is_array($atributos)) {
            $clean = array_values(array_filter(array_map('trim', $atributos), function ($x) {
                return $x !== '';
            }));
            if (count($clean) > 0) {
                return json_encode($clean, JSON_UNESCAPED_UNICODE);
            }
        }
        return null;
    }

    /**
     * Normaliza el campo variantes antes de guardarlo en BD.
     * Acepta: null, string JSON (objeto o [{label,valor}]), array PHP.
     * Devuelve: null o JSON string con formato {"clave":"valor"}.
     */
    private static function normalizarVariantes($variantes): ?string
    {
        if ($variantes === null || $variantes === '' || $variantes === 'null' || $variantes === '[]' || $variantes === '{}') return null;

        // Si viene como string, parsearlo
        if (is_string($variantes)) {
            $decoded = json_decode($variantes, true);
            if (json_last_error() !== JSON_ERROR_NONE || $decoded === null) return null;
            $variantes = $decoded;
        }

        // Si después de parsear es nulo o vacío
        if (empty($variantes)) return null;

        // Caso A: Array de objetos [{label, valor}, ...] (vía Import / JSON export)
        if (is_array($variantes) && isset($variantes[0]) && (is_array($variantes[0]) || is_object($variantes[0]))) {
            $obj = [];
            foreach ($variantes as $item) {
                $label = $item['label'] ?? $item['Label'] ?? '';
                $valor = $item['valor'] ?? $item['Valor'] ?? $item['value'] ?? $item['Value'] ?? '';
                if ($label !== '') {
                    if (!isset($obj[$label])) {
                        $obj[$label] = $valor;
                    } else {
                        // Si ya existe, convertir a array si no lo es y añadir el nuevo valor
                        if (!is_array($obj[$label])) {
                            $obj[$label] = [$obj[$label]];
                        }
                        if (!in_array($valor, $obj[$label])) {
                            $obj[$label][] = $valor;
                        }
                    }
                }
            }
            return count($obj) > 0 ? json_encode($obj, JSON_UNESCAPED_UNICODE) : null;
        }

        // Caso B: Objeto asociativo {"Color":"Rojo"} (vía Formulario Gestión)
        if (is_array($variantes)) {
            return json_encode($variantes, JSON_UNESCAPED_UNICODE);
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
     * Formula: Nuevo Coste = (Stock * Coste Antiguo + Cantidad Comprada * Coste Adquisicion) / (Stock + Cantidad Comprada)
     * 
     * @param int $id ID del producto
     * @param int $cantidad Cantidad comprada
     * @param float $costeAdquisicion Coste total unitario de esta entrada (incluyendo IVA/RE si aplica)
     * @param PDO|null $db Opcional: conexión PDO con transacción activa
     */
    public static function actualizarStockYCoste(int $id, int $cantidad, float $costeAdquisicion, $db = null): void
    {
        $producto = self::obtenerProductoPorId($id);
        if (!$producto) return;

        $stockAnterior = (int)$producto['stock_actual'];
        $costeAnterior = (float)$producto['precio_coste'];

        // Si el stock actual es 0 o negativo, el nuevo coste es directamente el de adquisición
        if ($stockAnterior <= 0) {
            $nuevoCoste = $costeAdquisicion;
        } else {
            $nuevoCoste = (($stockAnterior * $costeAnterior) + ($cantidad * $costeAdquisicion)) / ($stockAnterior + $cantidad);
        }

        $sql = "UPDATE productos SET 
                stock_actual = stock_actual + :cantidad, 
                precio_coste = :nuevo_coste 
                WHERE id = :id";

        $params = [
            ':cantidad' => $cantidad,
            ':nuevo_coste' => round($nuevoCoste, 2),
            ':id' => $id
        ];

        if ($db) {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        } else {
            DBPDO::ejecutarConsulta($sql, $params);
        }
    }

    public static function obtenerProductoPorId(int $id): ?array
    {
        $q = DBPDO::ejecutarConsulta("SELECT * FROM productos WHERE id = :id", [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function obtenerProductoPorReferencia(string $referencia): ?array
    {
        $q = DBPDO::ejecutarConsulta("SELECT * FROM productos WHERE referencia = :referencia", [':referencia' => $referencia]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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
}
