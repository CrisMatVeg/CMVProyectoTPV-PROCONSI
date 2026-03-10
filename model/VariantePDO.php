<?php

/**
 * Clase: VariantePDO
 * Gestiona la persistencia de las variantes de productos.
 */
require_once __DIR__ . '/DBPDO.php';

class VariantePDO
{

    public static function listarPorProducto(int $idProducto): array
    {
        $sql = "SELECT * FROM producto_variantes WHERE id_producto = :id ORDER BY nombre ASC";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idProducto]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerPorId(int $id): ?array
    {
        $q = DBPDO::ejecutarConsulta("SELECT * FROM producto_variantes WHERE id = :id", [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function editar(int $id, array $d): void
    {
        $sql = "UPDATE producto_variantes SET 
                sku = :sku, 
                stock_actual = :stock, 
                precio_venta = :precio, 
                activo = :activo 
                WHERE id = :id";

        DBPDO::ejecutarConsulta($sql, [
            ':id'     => $id,
            ':sku'    => $d['sku'],
            ':stock'  => (int)$d['stock_actual'],
            ':precio' => ($d['precio_venta'] !== '' && $d['precio_venta'] !== null) ? (float)$d['precio_venta'] : null,
            ':activo' => !empty($d['activo']) ? 1 : 0
        ]);
    }

    /**
     * Genera automáticamente las combinaciones de variantes basadas en el JSON de definición.
     */
    public static function sincronizarVariantes(int $idProducto, string $referenciaBase, ?string $jsonDefinicion): void
    {
        if (!$jsonDefinicion) {
            // Si no hay definición, opcionalmente podríamos borrar variantes, 
            // pero mejor no hacerlo para evitar pérdida de stock accidental.
            return;
        }

        $def = json_decode($jsonDefinicion, true);
        if (!$def || !is_array($def)) return;

        // 1. Obtener todas las combinaciones posibles (Producto Cartesiano)
        $combinaciones = self::generarCombinaciones($def);

        // 2. Obtener variantes actuales para no duplicar ni borrar stock existente
        $actuales = self::listarPorProducto($idProducto);
        $mapActuales = [];
        foreach ($actuales as $v) {
            $mapActuales[$v['datos']] = $v;
        }

        foreach ($combinaciones as $comb) {
            $jsonComb = json_encode($comb, JSON_UNESCAPED_UNICODE);

            if (!isset($mapActuales[$jsonComb])) {
                // Crear nueva variante
                $nombres = [];
                $skuParts = [$referenciaBase];
                foreach ($comb as $attr => $val) {
                    $nombres[] = "$attr: $val";
                    $skuParts[] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $val));
                }

                $nombreFinal = "Var: " . implode(", ", $nombres);
                $skuFinal = implode("-", $skuParts);

                // Evitar colisión de SKU (añadir sufijo si existe)
                $skuFinal = self::generarSkuUnico($skuFinal);

                $sql = "INSERT INTO producto_variantes (id_producto, sku, nombre, datos, stock_actual, activo) 
                        VALUES (:idp, :sku, :nom, :dat, 0, 1)";
                DBPDO::ejecutarConsulta($sql, [
                    ':idp' => $idProducto,
                    ':sku' => $skuFinal,
                    ':nom' => $nombreFinal,
                    ':dat' => $jsonComb
                ]);
            }
        }
    }

    private static function generarCombinaciones(array $def): array
    {
        $resultado = [[]];
        foreach ($def as $atributo => $valores) {
            if (!is_array($valores)) $valores = [$valores];
            $temp = [];
            foreach ($resultado as $combinacion) {
                foreach ($valores as $valor) {
                    $nueva = $combinacion;
                    $nueva[$atributo] = $valor;
                    $temp[] = $nueva;
                }
            }
            $resultado = $temp;
        }
        return $resultado;
    }

    private static function generarSkuUnico(string $sku): string
    {
        $original = $sku;
        $cont = 1;
        while (true) {
            $q = DBPDO::ejecutarConsulta("SELECT id FROM producto_variantes WHERE sku = :sku", [':sku' => $sku]);
            if (!$q->fetch()) return $sku;
            $sku = $original . "-" . $cont++;
        }
    }

    public static function reducirStock(int $id, int $cantidad): void
    {
        DBPDO::ejecutarConsulta("UPDATE producto_variantes SET stock_actual = stock_actual - :qty WHERE id = :id", [
            ':qty' => $cantidad,
            ':id'  => $id
        ]);
    }

    public static function aumentarStock(int $id, int $cantidad): void
    {
        DBPDO::ejecutarConsulta("UPDATE producto_variantes SET stock_actual = stock_actual + :qty WHERE id = :id", [
            ':qty' => $cantidad,
            ':id'  => $id
        ]);
    }
}
