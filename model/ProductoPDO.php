<?php

/**
 * Clase: ProductoPDO
 * Gestiona la persistencia de los productos mediante DBPDO.
 * @package Modelos
 * @author Cristian Mateos Vega
 */
require_once __DIR__ . '/DBPDO.php';
require_once __DIR__ . '/Producto.php';

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
            $productos[] = new Producto(
                $registro['id'],
                $registro['referencia'],
                $registro['nombre'],
                $registro['descripcion'],
                $registro['precio_coste'],
                $registro['precio_venta'],
                $registro['iva'],
                $registro['stock_actual'],
                $registro['stock_minimo'],
                $registro['meses_garantia'],
                $registro['icono'],
                $registro['categoria'],
                $registro['variantes'],
                $registro['atributos'],
                $registro['activo'],
                $registro['requiere_serial'] ?? 0,
                $registro['codigo_iva'] ?? 'GENERAL'
            );
        }
        return $productos;
    }

    /**
     * Añade un nuevo producto a la base de datos.
     * @param array $datos
     * @return array El nuevo producto como array asociativo
     */
    public static function añadirProducto(array $datos): array
    {
        $iconoDato = $datos['icono'] ?? '';
        if (strpos($iconoDato, 'data:image') === 0) {
            $parts = explode(',', $iconoDato);
            $iconoDato = base64_decode($parts[1]);
        }

        $sql = "INSERT INTO productos (referencia, nombre, descripcion, precio_coste, precio_venta, iva, stock_actual, stock_minimo, meses_garantia, icono, categoria, variantes, atributos, activo, requiere_serial, codigo_iva)
                VALUES (:referencia, :nombre, :descripcion, :precio_coste, :precio_venta, :iva, :stock_actual, :stock_minimo, :meses_garantia, :icono, :categoria, :variantes, :atributos, 1, :requiere_serial, :codigo_iva)";

        DBPDO::ejecutarConsulta($sql, [
            ':referencia'     => mb_substr(trim($datos['referencia']), 0, 50),
            ':nombre'         => mb_substr(trim($datos['nombre']), 0, 100),
            ':descripcion'    => $datos['descripcion'] ?? '',
            ':precio_coste'   => round((float)($datos['precio_coste'] ?? 0), 2),
            ':precio_venta'   => round((float)($datos['precio_venta'] ?? 0), 2),
            ':iva'            => round((float)($datos['iva'] ?? 21), 2),
            ':stock_actual'   => (int)($datos['stock_actual'] ?? 0),
            ':stock_minimo'   => (int)($datos['stock_minimo'] ?? 0),
            ':meses_garantia' => (int)($datos['meses_garantia'] ?? 24),
            ':icono'          => $iconoDato,
            ':categoria'      => mb_substr(trim($datos['categoria']), 0, 50),
            ':variantes'      => self::normalizarVariantes($datos['variantes'] ?? null),
            ':atributos'      => self::normalizarAtributos($datos['atributos'] ?? null),
            ':requiere_serial' => (int)($datos['requiere_serial'] ?? 0),
            ':codigo_iva'     => $datos['codigo_iva'] ?? 'GENERAL',
        ]);

        $q = DBPDO::ejecutarConsulta("SELECT * FROM productos ORDER BY id DESC LIMIT 1");
        return $q->fetch(PDO::FETCH_ASSOC);
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

        $sql = "UPDATE productos
                SET referencia = :referencia, nombre = :nombre, descripcion = :descripcion, 
                    precio_coste = :precio_coste, precio_venta = :precio_venta, iva = :iva, 
                    stock_actual = :stock_actual, stock_minimo = :stock_minimo, 
                    meses_garantia = :meses_garantia, icono = :icono, categoria = :categoria,
                    variantes = :variantes, atributos = :atributos, requiere_serial = :requiere_serial, codigo_iva = :codigo_iva
                WHERE id = :id";

        DBPDO::ejecutarConsulta($sql, [
            ':referencia'     => mb_substr(trim($datos['referencia']), 0, 50),
            ':nombre'         => mb_substr(trim($datos['nombre']), 0, 100),
            ':descripcion'    => $datos['descripcion'] ?? '',
            ':precio_coste'   => round((float)($datos['precio_coste'] ?? 0), 2),
            ':precio_venta'   => round((float)($datos['precio_venta'] ?? 0), 2),
            ':iva'            => round((float)($datos['iva'] ?? 21), 2),
            ':stock_actual'   => (int)($datos['stock_actual'] ?? 0),
            ':stock_minimo'   => (int)($datos['stock_minimo'] ?? 0),
            ':meses_garantia' => (int)($datos['meses_garantia'] ?? 24),
            ':icono'          => $iconoDato,
            ':categoria'      => mb_substr(trim($datos['categoria']), 0, 50),
            ':variantes'      => self::normalizarVariantes($datos['variantes'] ?? null),
            ':atributos'      => self::normalizarAtributos($datos['atributos'] ?? null),
            ':requiere_serial' => (int)($datos['requiere_serial'] ?? 0),
            ':codigo_iva'     => $datos['codigo_iva'] ?? 'GENERAL',
            ':id'             => $id,
        ]);
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
}
