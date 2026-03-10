<?php

/**
 * Clase: EntradaStockPDO
 * Gestiona las entradas de stock y el cálculo del coste medio ponderado (CMP).
 */
require_once __DIR__ . '/DBPDO.php';
require_once __DIR__ . '/MovimientoStockPDO.php';

class EntradaStockPDO
{
    /**
     * Registra una entrada de stock para un producto y recalcula su CMP.
     *
     * Fórmula CMP:
     *   nuevo_cmp = (stock_actual × cmp_actual + cantidad_nueva × precio_coste_nueva)
     *               / (stock_actual + cantidad_nueva)
     *
     * @param int    $idProducto       ID del producto
     * @param int    $cantidad         Unidades que entran
     * @param float  $precioCoste      Coste por unidad de esta entrada
     * @param int    $idUsuario        ID del usuario que registra la entrada
     * @param string $notas            Notas opcionales (nº albarán, proveedor, etc.)
     * @return array ['cmp_anterior', 'cmp_resultante', 'stock_anterior', 'stock_nuevo']
     */
    public static function registrarEntrada(
        int $idProducto,
        int $cantidad,
        float $precioCoste,
        ?int $idUsuario = null,
        string $notas = '',
        ?PDO $db = null
    ): array {
        // 1. Obtener stock y CMP actuales del producto
        $sqlProd = "SELECT stock_actual, precio_coste FROM productos WHERE id = :id";
        if ($db) {
            $stmt = $db->prepare($sqlProd);
            $stmt->execute([':id' => $idProducto]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $q = DBPDO::ejecutarConsulta($sqlProd, [':id' => $idProducto]);
            $prod = $q->fetch(PDO::FETCH_ASSOC);
        }
        if (!$prod) {
            throw new \RuntimeException("Producto ID {$idProducto} no encontrado.");
        }

        $stockActual  = (int)$prod['stock_actual'];
        $cmpActual    = (float)$prod['precio_coste'];
        $stockNuevo   = $stockActual + $cantidad;

        // 2. Calcular nuevo CMP
        if ($stockNuevo <= 0) {
            $cmpNuevo = $precioCoste;
        } else {
            $cmpNuevo = round(
                ($stockActual * $cmpActual + $cantidad * $precioCoste) / $stockNuevo,
                4
            );
        }

        // 3. Actualizar el producto: stock y precio_coste (CMP)
        $sqlUpdate = "UPDATE productos SET stock_actual = :stock, precio_coste = :cmp WHERE id = :id";
        $paramsUpdate = [':stock' => $stockNuevo, ':cmp' => $cmpNuevo, ':id' => $idProducto];

        // 4. Registrar el movimiento de entrada
        $sqlInsert = "INSERT INTO entradas_stock
                (id_producto, cantidad, precio_coste, cmp_anterior, cmp_resultante,
                 stock_anterior, stock_nuevo, id_usuario, notas)
             VALUES (:prod, :qty, :coste, :cmpAnt, :cmpRes, :stkAnt, :stkNuevo, :usr, :notas)";
        $paramsInsert = [
            ':prod'    => $idProducto,
            ':qty'     => $cantidad,
            ':coste'   => round($precioCoste, 2),
            ':cmpAnt'  => round($cmpActual, 2),
            ':cmpRes'  => round($cmpNuevo, 2),
            ':stkAnt'  => $stockActual,
            ':stkNuevo' => $stockNuevo,
            ':usr'     => $idUsuario,
            ':notas'   => $notas ?: null,
        ];

        if ($db) {
            $stmtUpd = $db->prepare($sqlUpdate);
            $stmtUpd->execute($paramsUpdate);
            $stmtIns = $db->prepare($sqlInsert);
            $stmtIns->execute($paramsInsert);
        } else {
            DBPDO::ejecutarConsulta($sqlUpdate, $paramsUpdate);
            DBPDO::ejecutarConsulta($sqlInsert, $paramsInsert);
        }

        // Registrar en MovimientosStock
        MovimientoStockPDO::registrarMovimiento(
            $idProducto,
            'compra',
            $cantidad,
            $idUsuario,
            $notas ?: "Entrada de stock (CMP)",
            $db
        );

        return [
            'cmp_anterior'   => $cmpActual,
            'cmp_resultante' => $cmpNuevo,
            'stock_anterior' => $stockActual,
            'stock_nuevo'    => $stockNuevo,
        ];
    }

    /**
     * Historial de entradas de stock de un producto.
     */
    public static function historialProducto(int $idProducto): array
    {
        $q = DBPDO::ejecutarConsulta(
            "SELECT e.*, u.nombre as nombre_usuario
             FROM entradas_stock e
             LEFT JOIN usuarios u ON e.id_usuario = u.id
             WHERE e.id_producto = :id
             ORDER BY e.fecha DESC",
            [':id' => $idProducto]
        );
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Historial global de entradas de stock (todos los productos).
     */
    public static function historialGlobal(int $limit = 100): array
    {
        $q = DBPDO::ejecutarConsulta(
            "SELECT e.*, p.nombre as nombre_producto, p.referencia as referencia_producto,
                    u.nombre as nombre_usuario
             FROM entradas_stock e
             JOIN productos p ON e.id_producto = p.id
             LEFT JOIN usuarios u ON e.id_usuario = u.id
             ORDER BY e.fecha DESC
             LIMIT {$limit}"
        );
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
