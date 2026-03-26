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
        // Auto-migración: asegurar que la tabla entradas_stock existe
        static $tablaCreada = false;
        if (!$tablaCreada) {
            try {
                // Crear tabla si no existe
                DBPDO::ejecutarConsulta("CREATE TABLE IF NOT EXISTS entradas_stock (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    id_producto INT NOT NULL,
                    cantidad INT NOT NULL,
                    precio_coste DECIMAL(10,4) NOT NULL DEFAULT 0,
                    cmp_anterior DECIMAL(10,4) NOT NULL DEFAULT 0,
                    cmp_resultante DECIMAL(10,4) NOT NULL DEFAULT 0,
                    stock_anterior INT NOT NULL DEFAULT 0,
                    stock_nuevo INT NOT NULL DEFAULT 0,
                    id_usuario INT DEFAULT NULL,
                    notas TEXT DEFAULT NULL,
                    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_producto (id_producto)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            } catch (\Throwable $e) {
                error_log("EntradaStockPDO: Error auto-migrando tabla entradas_stock: " . $e->getMessage());
            }
            $tablaCreada = true;
        }

        // 1. Obtener stock y CMP actuales del producto
        $sqlProd = "SELECT p.precio_coste, p.stock_actual, p.precio_venta, p.margen
                    FROM productos p WHERE p.id = :id";
        
        $stmt = ($db) ? $db->prepare($sqlProd) : null;
        if ($db) {
            $stmt->execute([':id' => $idProducto]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $q = DBPDO::ejecutarConsulta($sqlProd, [':id' => $idProducto]);
            $prod = $q->fetch(PDO::FETCH_ASSOC);
        }

        if (!$prod) {
            throw new \RuntimeException("Producto ID {$idProducto} no encontrado.");
        }

        $stockActual    = (int)$prod['stock_actual'];
        $cmpActual      = (float)$prod['precio_coste'];
        $pvpActual      = (float)$prod['precio_venta'];
        $margen         = (float)($prod['margen'] ?? 0);

        // IMPORTANTE: Para el cálculo del CMP, si el stock actual es negativo,
        // lo tratamos como 0 para no "corromper" la media ponderada con deudas de stock.
        $stockPonderado = max(0, $stockActual);
        $stockNuevo     = $stockActual + $cantidad;

        // 2. Calcular nuevo CMP
        if ($stockPonderado <= 0) {
            $cmpNuevo = $precioCoste;
        } else {
            $cmpNuevo = round(
                ($stockPonderado * $cmpActual + $cantidad * $precioCoste) / ($stockPonderado + $cantidad),
                4
            );
        }

        // 2b. AJUSTE AUTOMÁTICO DE PVP SI HAY MARGEN DEFINIDO
        $pvpNuevo = $pvpActual;
        if ($margen > 0) {
            $pvpNuevo = round($cmpNuevo * (1 + ($margen / 100)), 2);
        }

        // 3. Actualizar el producto: precio_coste (CMP), stock y PVP (si cambió)
        $sqlUpdate = "UPDATE productos SET stock_actual = :stock, precio_coste = :cmp, precio_venta = :pvp WHERE id = :id";
        $paramsUpdate = [
            ':stock' => $stockNuevo, 
            ':cmp'   => $cmpNuevo, 
            ':pvp'   => $pvpNuevo,
            ':id'    => $idProducto
        ];

        // 4. Registrar el movimiento de entrada
        $sqlInsert = "INSERT INTO entradas_stock
                (id_producto, cantidad, precio_coste, cmp_anterior, cmp_resultante,
                 stock_anterior, stock_nuevo, id_usuario, notas)
              VALUES (:prod, :qty, :coste, :cmpAnt, :cmpRes, :stkAnt, :stkNuevo, :usr, :notas)";

        $paramsInsert = [
            ':prod'    => $idProducto,
            ':qty'     => $cantidad,
            ':coste'   => round($precioCoste, 4),
            ':cmpAnt'  => round($cmpActual, 4),
            ':cmpRes'  => round($cmpNuevo, 4),
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

        // 5. REGISTRAR AUDITORÍA DE PRECIO SI EL PVP CAMBIÓ AUTOMÁTICAMENTE
        if ($pvpNuevo !== $pvpActual) {
            $sqlAudit = "INSERT INTO auditoria_precios_base (id_producto, precio_old, precio_new, motivo, id_usuario) 
                         VALUES (:p, :old, :new, :m, :u)";
            $paramsAudit = [
                ':p'   => $idProducto,
                ':old' => $pvpActual,
                ':new' => $pvpNuevo,
                ':m'   => "Ajuste automático por cambio de CMP (Margen: {$margen}%)",
                ':u'   => $idUsuario
            ];
            if ($db) {
                $stmtAudit = $db->prepare($sqlAudit);
                $stmtAudit->execute($paramsAudit);
            } else {
                DBPDO::ejecutarConsulta($sqlAudit, $paramsAudit);
            }
        }

        // Registrar en MovimientosStock
        require_once __DIR__ . '/MovimientoStockPDO.php';
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
