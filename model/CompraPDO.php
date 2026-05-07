<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/Compra.php';
require_once __DIR__ . '/ProveedorPDO.php';
require_once __DIR__ . '/ProductoPDO.php';
require_once __DIR__ . '/EntradaStockPDO.php';

class CompraPDO
{

    public static function registrarAlbaran($proveedor_id, $numero_albaran, $fecha, $lineas)
    {
        try {
            $db = DBPDO::getPDO();

            $db->beginTransaction();

            $oProv = ProveedorPDO::buscarPorId($proveedor_id);
            if (!$oProv) throw new Exception("Proveedor no encontrado");

            $base_imponible = 0;
            $iva_total = 0;
            $re_total = 0;

            foreach ($lineas as $l) {
                $subtotal_neto = $l['cantidad'] * $l['precio_coste_neto'];
                $base_imponible += $subtotal_neto;

                $iva = $subtotal_neto * ($l['iva_pct'] / 100);
                $iva_total += $iva;

                $re = 0;
                if ($oProv->getAplicaRe()) {
                    $re = $subtotal_neto * ($l['re_pct'] / 100);
                    $re_total += $re;
                }
            }

            $total = $base_imponible + $iva_total + $re_total;

            $sqlAlbaran = "INSERT INTO albaranes_compra (proveedor_id, numero_albaran, fecha, base_imponible, iva_total, re_total, total, estado) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'recibido')";
            $stmt = $db->prepare($sqlAlbaran);
            $stmt->execute([$proveedor_id, $numero_albaran, $fecha, $base_imponible, $iva_total, $re_total, $total]);
            $albaran_id = $db->lastInsertId();

            foreach ($lineas as $l) {
                $sqlLinea = "INSERT INTO lineas_compra (albaran_id, producto_id, cantidad, precio_coste_neto, iva_pct, re_pct) 
                             VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sqlLinea);
                $stmt->execute([$albaran_id, $l['producto_id'], $l['cantidad'], $l['precio_coste_neto'], $l['iva_pct'], $l['re_pct']]);
            }

            $db->commit();
            return $albaran_id;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            error_log("Error en CompraPDO::registrarAlbaran: " . $e->getMessage());
            throw $e;
        }
    }

    public static function validarAlbaran($id)
    {
        try {
            $db = DBPDO::getPDO();
            $db->beginTransaction();

            // 1. Obtener datos del albarán y sus líneas
            $albaran = self::obtenerDetalleAlbaran($id);
            if (!$albaran) throw new Exception("Albarán no encontrado");
            if ($albaran['estado'] !== 'recibido') throw new Exception("El albarán ya ha sido validado o procesado");

            $oProv = ProveedorPDO::buscarPorId($albaran['proveedor_id']);
            if (!$oProv) throw new Exception("Proveedor no encontrado");

            // 2. Actualizar Stock y CMP para cada línea
            foreach ($albaran['lineas'] as $l) {
                // El IVA soportado NO forma parte del coste: es un impuesto repercutido al cliente.
                // Solo el recargo de equivalencia (RE) es un coste real no recuperable.
                $reUnitario = ($oProv->getAplicaRe()) ? $l['precio_coste_neto'] * ($l['re_pct'] / 100) : 0;
                $costeAdquisicionUnitario = $l['precio_coste_neto'] + $reUnitario;

                $notas = "Albarán validado: " . $albaran['numero_albaran'];
                $idUsuario = (isset($_SESSION['usuarioActualTPV']) && is_object($_SESSION['usuarioActualTPV']) && method_exists($_SESSION['usuarioActualTPV'], 'getId')) 
                             ? $_SESSION['usuarioActualTPV']->getId() : null;

                EntradaStockPDO::registrarEntrada(
                    $l['producto_id'],
                    $l['cantidad'],
                    $costeAdquisicionUnitario,
                    $idUsuario,
                    $notas,
                    $db
                );
            }

            // 3. Cambiar estado a 'validado'
            $stmt = $db->prepare("UPDATE albaranes_compra SET estado = 'validado' WHERE id = ?");
            $stmt->execute([$id]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            error_log("Error en CompraPDO::validarAlbaran: " . $e->getMessage());
            return ["error" => $e->getMessage()];
        }
    }

    public static function listarAlbaranes($soloPendientesFacturar = false, $proveedor_id = null)
    {
        try {
            $db = DBPDO::getPDO();

            $sql = "SELECT a.*, p.nombre as proveedor_nombre FROM albaranes_compra a 
                    JOIN proveedores p ON a.proveedor_id = p.id";
            $params = [];
            
            $where = [];
            if ($soloPendientesFacturar) {
                $where[] = "a.estado = 'validado'";
            }
            if ($proveedor_id) {
                $where[] = "a.proveedor_id = ?";
                $params[] = $proveedor_id;
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            $sql .= " ORDER BY a.fecha DESC, a.id DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en CompraPDO::listarAlbaranes: " . $e->getMessage());
            return [];
        }
    }

    public static function obtenerDetalleAlbaran($id)
    {
        try {
            $db = DBPDO::getPDO();

            $stmt = $db->prepare("SELECT a.*, p.nombre as proveedor_nombre FROM albaranes_compra a 
                                JOIN proveedores p ON a.proveedor_id = p.id 
                                WHERE a.id = ?");
            $stmt->execute([$id]);
            $albaran = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($albaran) {
                $stmtLines = $db->prepare("SELECT l.*, p.nombre as producto_nombre FROM lineas_compra l 
                                          JOIN productos p ON l.producto_id = p.id 
                                          WHERE l.albaran_id = ?");
                $stmtLines->execute([$id]);
                $albaran['lineas'] = $stmtLines->fetchAll(PDO::FETCH_ASSOC);
            }

            return $albaran;
        } catch (PDOException $e) {
            error_log("Error en CompraPDO::obtenerDetalleAlbaran: " . $e->getMessage());
            return null;
        }
    }

    public static function registrarFactura($proveedor_id, $numero_factura, $fecha, $albaranes_ids, $metodo_pago, $pagada = true)
    {
        try {
            $db = DBPDO::getPDO();

            $db->beginTransaction();

            $oProv = ProveedorPDO::buscarPorId($proveedor_id);
            if (!$oProv) throw new Exception("Proveedor no encontrado");

            // Calcular fecha de vencimiento
            $fecha_vencimiento = $fecha;
            if ($oProv->getVencimientoDias() > 0) {
                $date = new DateTime($fecha);
                $date->modify('+' . $oProv->getVencimientoDias() . ' days');
                $fecha_vencimiento = $date->format('Y-m-d');
            }

            // Calcular total sumando los albaranes
            $total = 0;
            $placeholders = implode(',', array_fill(0, count($albaranes_ids), '?'));
            $sqlSum = "SELECT SUM(total) as total FROM albaranes_compra WHERE id IN ($placeholders) AND proveedor_id = ?";
            $stmtSum = $db->prepare($sqlSum);
            $stmtSum->execute(array_merge($albaranes_ids, [$proveedor_id]));
            $total = $stmtSum->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;

            // 1. Crear Factura
            $sqlFactura = "INSERT INTO facturas_compra_prov (proveedor_id, numero_factura, fecha_factura, fecha_vencimiento, total, metodo_pago, pagado) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtFactura = $db->prepare($sqlFactura);
            $stmtFactura->execute([$proveedor_id, $numero_factura, $fecha, $fecha_vencimiento, $total, $metodo_pago, $pagada ? 1 : 0]);
            $factura_id = $db->lastInsertId();

            // 2. Vincular Albaranes
            $sqlUpdateAlb = "UPDATE albaranes_compra SET factura_id = ?, estado = 'facturado' WHERE id IN ($placeholders)";
            $stmtUpdateAlb = $db->prepare($sqlUpdateAlb);
            $stmtUpdateAlb->execute(array_merge([$factura_id], $albaranes_ids));

            // 3. Si se marca como pagada y el pago es por caja, registrar gasto
            if ($pagada && $metodo_pago === 'caja') {
                require_once __DIR__ . '/CajaTurnoPDO.php';
                $turno = CajaTurnoPDO::obtenerTurnoAbierto();
                if ($turno) {
                    $concepto = "Pago Factura Compra $numero_factura";
                    $db->prepare("INSERT INTO caja_movimientos (id_turno, tipo, importe, concepto) VALUES (?, 'retiro', ?, ?)")
                        ->execute([$turno['id'], $total, $concepto]);

                    $db->prepare("UPDATE caja_turnos SET total_retirado = total_retirado + ? WHERE id = ?")
                        ->execute([$total, $turno['id']]);
                }
            }

            $db->commit();
            return $factura_id;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            error_log("Error en CompraPDO::registrarFactura: " . $e->getMessage());
            return false;
        }
    }

    public static function pagarFactura($id, $metodo_pago)
    {
        try {
            $db = DBPDO::getPDO();
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT * FROM facturas_compra_prov WHERE id = ?");
            $stmt->execute([$id]);
            $factura = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$factura) throw new Exception("Factura no encontrada");
            if ($factura['pagado']) throw new Exception("La factura ya está pagada");

            // 1. Marcar como pagada
            $stmtUpdate = $db->prepare("UPDATE facturas_compra_prov SET pagado = 1, metodo_pago = ? WHERE id = ?");
            $stmtUpdate->execute([$metodo_pago, $id]);

            // 2. Si es por caja, registrar gasto
            if ($metodo_pago === 'caja') {
                require_once __DIR__ . '/CajaTurnoPDO.php';
                $turno = CajaTurnoPDO::obtenerTurnoAbierto();
                if ($turno) {
                    $concepto = "Pago Factura Compra " . $factura['numero_factura'];
                    $db->prepare("INSERT INTO caja_movimientos (id_turno, tipo, importe, concepto) VALUES (?, 'retiro', ?, ?)")
                        ->execute([$turno['id'], $factura['total'], $concepto]);

                    $db->prepare("UPDATE caja_turnos SET total_retirado = total_retirado + ? WHERE id = ?")
                        ->execute([$factura['total'], $turno['id']]);
                }
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            error_log("Error en CompraPDO::pagarFactura: " . $e->getMessage());
            return ["error" => $e->getMessage()];
        }
    }

    public static function listarFacturas($proveedor_id = null)
    {
        try {
            $db = DBPDO::getPDO();

            $sql = "SELECT f.*, p.nombre as proveedor_nombre FROM facturas_compra_prov f 
                    JOIN proveedores p ON f.proveedor_id = p.id";
            $params = [];
            if ($proveedor_id) {
                $sql .= " WHERE f.proveedor_id = ?";
                $params[] = $proveedor_id;
            }
            $sql .= " ORDER BY f.fecha_factura DESC, f.id DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en CompraPDO::listarFacturas: " . $e->getMessage());
            return [];
        }
    }

    public static function obtenerDetalleFactura($id)
    {
        try {
            $db = DBPDO::getPDO();

            $stmt = $db->prepare("SELECT f.*, p.nombre as proveedor_nombre FROM facturas_compra_prov f 
                                JOIN proveedores p ON f.proveedor_id = p.id 
                                WHERE f.id = ?");
            $stmt->execute([$id]);
            $factura = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($factura) {
                $stmtAlb = $db->prepare("SELECT * FROM albaranes_compra WHERE factura_id = ?");
                $stmtAlb->execute([$id]);
                $factura['albaranes'] = $stmtAlb->fetchAll(PDO::FETCH_ASSOC);
            }

            return $factura;
        } catch (PDOException $e) {
            error_log("Error en CompraPDO::obtenerDetalleFactura: " . $e->getMessage());
            return null;
        }
    }
}
