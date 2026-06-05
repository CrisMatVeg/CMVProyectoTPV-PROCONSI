<?php

/**
 * Clase: AeatQueueService
 * Gestiona la cola de envíos a la AEAT para asegurar un comportamiento asíncrono.
 */

require_once __DIR__ . '/DBPDO.php';
require_once __DIR__ . '/AeatApi.php';
require_once dirname(__DIR__) . '/config/config.php';

class AeatQueueService
{
    /**
     * Encola un nuevo envío para una venta.
     * Implementa 'Natural Batching' y 'Auto-Blocking' para preservar la cadena.
     */
    public function encolar(int $idVenta, string $xmlPath)
    {
        // 1. Verificar si hay algún registro REAL bloqueado o con error crítico previo.
        // Para VeriFactu (FIFO estricto), si hay CUALQUIER registro previo con error crítico 
        // o pendiente de subsanación, los nuevos deben entrar como 'bloqueado' para no romper la cadena.
        $sqlBloq = "SELECT COUNT(*) FROM cola_envios c
                    JOIN ventas v ON c.id_venta = v.id
                    WHERE c.estado IN ('error_critico', 'bloqueado') 
                      AND v.estado_envio_aeat IN ('error_critico', 'subsanacion_pendiente', 'bloqueado')";
        
        $hayBloqueo = ((int)DBPDO::ejecutarConsulta($sqlBloq)->fetchColumn() > 0);
        $estadoFinal = $hayBloqueo ? 'bloqueado' : 'pendiente';
        
        // Actualizar el estado en ventas para que la UI sepa qué esperar
        DBPDO::ejecutarConsulta("UPDATE ventas SET estado_envio_aeat = :st WHERE id = :id", [
            ':st' => $estadoFinal, 
            ':id' => $idVenta
        ]);

        // 2. Gestionar el tiempo de ráfaga (Natural Batching)
        $fechaProximo = null;
        if ($estadoFinal === 'pendiente') {
            // Buscamos si ya hay alguien esperando para heredar su hora de envío (el lote)
            $sqlTime = "SELECT c.fecha_proximo_intento FROM cola_envios c
                        JOIN ventas v ON c.id_venta = v.id
                        WHERE c.estado = 'pendiente' 
                          AND v.estado_envio_aeat NOT IN ('enviado', 'anulado')
                        ORDER BY c.id ASC LIMIT 1";
            $rowTime = DBPDO::ejecutarConsulta($sqlTime)->fetch(PDO::FETCH_ASSOC);
            
            // Si no hay nadie esperando, empezamos la cuenta atrás de 60s desde ahora
            $fechaProximo = ($rowTime && $rowTime['fecha_proximo_intento']) 
                            ? $rowTime['fecha_proximo_intento'] 
                            : date('Y-m-d H:i:s', time() + 60);
        }

        $sql = "INSERT INTO cola_envios (id_venta, xml_path, estado, fecha_proximo_intento)
                VALUES (:id_venta, :xml_path, :estado, :fecha)
                ON DUPLICATE KEY UPDATE 
                    xml_path = VALUES(xml_path), 
                    estado = :estado, 
                    intentos = 0, 
                    fecha_proximo_intento = :fecha";

        return DBPDO::ejecutarConsulta($sql, [
            ':id_venta' => $idVenta,
            ':xml_path' => $xmlPath,
            ':estado'   => $estadoFinal,
            ':fecha'    => $fechaProximo
        ]);
    }

    /**
     * Registra un error de generación de XML antes de encolar para reintento.
     */
    public function encolarError(int $idVenta, string $error)
    {
        $sqlVenta = "UPDATE ventas SET estado_envio_aeat = 'pendiente' WHERE id = :id";
        DBPDO::ejecutarConsulta($sqlVenta, [':id' => $idVenta]);

        // Igual que en encolar, mantenemos la coherencia del batch
        $stmtTime = DBPDO::ejecutarConsulta("SELECT fecha_proximo_intento FROM cola_envios WHERE estado = 'pendiente' ORDER BY id ASC LIMIT 1");
        $fechaProximo = $stmtTime->fetchColumn() ?: date('Y-m-d H:i:s', time() + 60);

        $sql = "INSERT INTO cola_envios (id_venta, xml_path, estado, ultimo_error, fecha_proximo_intento)
                VALUES (:id_venta, NULL, 'pendiente', :error, :fecha)
                ON DUPLICATE KEY UPDATE estado = 'pendiente', ultimo_error = VALUES(ultimo_error), intentos = 0, fecha_proximo_intento = :fecha";

        return DBPDO::ejecutarConsulta($sql, [
            ':id_venta' => $idVenta,
            ':error'    => $error,
            ':fecha'    => $fechaProximo
        ]);
    }

    /**
     * Revisa registros bloqueados y los libera si ya no hay impedimentos previos.
     */
    public function desbloquearCola()
    {
        // Un registro 'bloqueado' puede pasar a 'pendiente' si no hay registros anteriores 
        // (por ID) que estén en 'error_critico' o ventas en 'subsanacion_pendiente'.
        
        // 1. Obtener todos los bloqueados
        $sql = "SELECT id, id_venta FROM cola_envios WHERE estado = 'bloqueado' ORDER BY id ASC";
        $bloqueados = DBPDO::ejecutarConsulta($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($bloqueados as $b) {
            $idBloq = (int)$b['id'];
            
            // Comprobar si hay algo ANTES de este ID que bloquee
            $sqlCheck = "SELECT COUNT(*) FROM cola_envios c
                         JOIN ventas v ON c.id_venta = v.id
                         WHERE c.id < :id 
                           AND (c.estado IN ('error_critico', 'bloqueado') 
                                OR v.estado_envio_aeat IN ('error_critico', 'subsanacion_pendiente', 'bloqueado'))";
            
            $hayBloqueo = ((int)DBPDO::ejecutarConsulta($sqlCheck, [':id' => $idBloq])->fetchColumn() > 0);
            
            if (!$hayBloqueo) {
                DBPDO::ejecutarConsulta("UPDATE cola_envios SET estado = 'pendiente' WHERE id = :id", [':id' => $idBloq]);
                // También actualizamos el estado en la venta si procede
                DBPDO::ejecutarConsulta("UPDATE ventas SET estado_envio_aeat = 'pendiente' WHERE id = :idv AND estado_envio_aeat = 'bloqueado'", [':idv' => $b['id_venta']]);
            }
        }
    }

    /**
     * Procesa los envíos pendientes en la cola respetando el orden FIFO,
     * el tiempo de espera de 60s y los límites de lote (1000 reg / 6MB).
     */
    public function procesarCola(bool $forzado = false, int $limit = 0): array
    {
        $resumen = ['exitos' => 0, 'fallos' => 0];

        // 0. Intentar desbloquear registros si las condiciones han cambiado
        $this->desbloquearCola();

        // 1. Obtener registros pendientes (máximo 2000 para permitir múltiples lotes de 1000)
        $sql = "SELECT * FROM cola_envios WHERE estado = 'pendiente' ORDER BY id ASC LIMIT 2000";
        $stmt = DBPDO::ejecutarConsulta($sql);
        $todosPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($todosPendientes)) {
            return $resumen;
        }

        // Detener la cola si hay registros con subsanación pendiente:
        // esos ítems están en error_critico pero los posteriores siguen en pendiente,
        // y no deben enviarse hasta que el usuario corrija y reencole el ítem subsanable.
        $sqlSubs = "SELECT COUNT(*) FROM ventas WHERE estado_envio_aeat = 'subsanacion_pendiente'";
        if ((int)DBPDO::ejecutarConsulta($sqlSubs)->fetchColumn() > 0) {
            return $resumen;
        }

        error_log("VeriFactu Heartbeat: Procesando " . count($todosPendientes) . " registros pendientes.");

        // 2. Filtrar solo los que están listos según el cronómetro (o si es forzado)
        $listos = [];
        $now = time();
        foreach ($todosPendientes as $p) {
            $proximo = $p['fecha_proximo_intento'] ? strtotime($p['fecha_proximo_intento']) : 0;
            if ($proximo > $now && !$forzado) {
                // El primer registro aún debe esperar -> bloquea toda la cola para preservar el orden
                break;
            }
            $listos[] = $p;
        }

        if (empty($listos)) {
            return $resumen;
        }

        // 3. Agrupar en lotes físicos (Max 1000 registros o 6MB de XMLs)
        $lotes = $this->agruparEnLotes($listos);

        foreach ($lotes as $lote) {
            $resultado = $this->enviarLoteUnificado($lote);
            $resumen['exitos'] += $resultado['exitos'];
            $resumen['fallos'] += $resultado['fallos'];
            
            // Si hubo un fallo que bloqueó la cola (subsanación o rechazo), no seguimos con más lotes
            $sqlCheck = "SELECT COUNT(*) FROM cola_envios WHERE estado IN ('bloqueado', 'error_critico')";
            if ((int)DBPDO::ejecutarConsulta($sqlCheck)->fetchColumn() > 0) {
                break;
            }

            // Si el lote falló por conexión, los registros vuelven a 'pendiente' pero con fecha_proximo_intento en el futuro.
            // Para preservar el orden FIFO y no saturar, si detectamos que el primer lote falló por conexión, paramos el resto.
            $sqlCheckCon = "SELECT COUNT(*) FROM cola_envios WHERE estado = 'pendiente' AND fecha_proximo_intento > CURRENT_TIMESTAMP";
            if ((int)DBPDO::ejecutarConsulta($sqlCheckCon)->fetchColumn() > 0) {
                break;
            }
        }

        return $resumen;
    }

    /**
     * Agrupa registros en sub-lotes que cumplan las restricciones técnicas de la AEAT.
     */
    private function agruparEnLotes(array $registros): array
    {
        $lotes = [];
        $loteActual = [];
        $tamanoActual = 0;
        $maxRegistros = 1000;
        $maxBytes = 6 * 1024 * 1024; // 6MB

        foreach ($registros as $reg) {
            $size = 0;
            if (!empty($reg['xml_path']) && file_exists($reg['xml_path'])) {
                $size = filesize($reg['xml_path']);
            }

            // Si añadir este registro supera algún límite, cerramos el lote actual
            if (count($loteActual) >= $maxRegistros || ($tamanoActual + $size) > $maxBytes) {
                if (!empty($loteActual)) {
                    $lotes[] = $loteActual;
                    $loteActual = [];
                    $tamanoActual = 0;
                }
            }

            $loteActual[] = $reg;
            $tamanoActual += $size;
        }

        if (!empty($loteActual)) {
            $lotes[] = $loteActual;
        }

        return $lotes;
    }

    /**
     * Procesa un lote físico específico (anteriormente parte de procesarCola).
     */
    private function enviarLoteUnificado(array $lote): array
    {
        $resumen = ['exitos' => 0, 'fallos' => 0];
        $loteId  = bin2hex(random_bytes(16));

        $loteIds  = [];
        $xmlPaths = [];
        $idVentas = [];

        foreach ($lote as $row) {
            if (empty($row['xml_path']) || !file_exists($row['xml_path'])) {
                require_once __DIR__ . '/VentaPDO.php';
                try {
                    VentaPDO::regenerarVentaAlta((int)$row['id_venta']);
                    // Se regenera y se encola de nuevo (el cron lo verá en la siguiente vuelta)
                } catch (\Exception $eReg) {
                    $this->marcarError($row['id'], "Error al regenerar XML: " . $eReg->getMessage(), 'error_critico');
                    $resumen['fallos']++;
                }
                continue;
            }
            $loteIds[]  = $row['id'];
            $xmlPaths[] = $row['xml_path'];
            $idVentas[] = $row['id_venta'];
        }

        if (empty($loteIds)) return $resumen;

        $respuesta = $this->enviarLote($xmlPaths, $lote);

        if ($respuesta['success']) {
            require_once __DIR__ . '/LogPDO.php';
            LogPDO::addLog('VF_ENVIO_EXITO', "Lote de " . count($loteIds) . " registros enviado correctamente.");
            foreach ($loteIds as $index => $idCola) {
                $idV = $idVentas[$index];
                $this->marcarEnviado($idCola, $idV, $respuesta['raw_response'] ?? '', $loteId);
                DBPDO::ejecutarConsulta(
                    "UPDATE verifactu_logs SET estado = 'enviado' WHERE id_venta = :idv AND estado = 'pendiente'",
                    [':idv' => $idV]
                );
                $resumen['exitos']++;
            }
        } else {
            // Lógica de gestión de errores (mantenida del original pero adaptada)
            require_once __DIR__ . '/VeriFactuErrorService.php';
            $raw = $respuesta['raw_response'] ?? '';

            if (!empty($raw) && strpos($raw, '<') !== false) {
                // Parseo de respuesta por línea para identificar qué falló exactamente
                $resLineas = $this->extraerLineasRespuesta($raw);

                if (!empty($resLineas)) {
                    $hayRechazoPrevio = false;
                    foreach ($loteIds as $idx => $idCola) {
                        $idV = (int)$idVentas[$idx];
                        
                        // Si hubo un rechazo previo en este mismo lote, bloqueamos el resto automáticamente
                        if ($hayRechazoPrevio) {
                            $this->marcarError($idCola, "Bloqueado por error en registro previo del mismo lote", self::CAT_BLOQUEO_CADENA, $raw);
                            DBPDO::ejecutarConsulta("UPDATE ventas SET estado_envio_aeat = 'bloqueado' WHERE id = :idv", [':idv' => $idV]);
                            $resumen['fallos']++;
                            continue;
                        }

                        try {
                            $resultadoLinea = $this->analizarResultadoLinea($idV, $resLineas);
                            
                            $stReg = $resultadoLinea['estado'];
                            $codErr = $resultadoLinea['codigo'];
                            $descErr = $resultadoLinea['mensaje'];

                            if ($stReg === 'Correcto' || $codErr === '3000') {
                                $this->marcarEnviado($idCola, $idV, $raw, $loteId);
                                DBPDO::ejecutarConsulta("UPDATE verifactu_logs SET estado = 'enviado' WHERE id_venta = :idv AND estado = 'pendiente'", [':idv' => $idV]);
                                $resumen['exitos']++;
                            } else {
                                $cat = ($stReg === 'AceptadoConErrores') ? VeriFactuErrorService::CAT_SUBSANAR : VeriFactuErrorService::clasificar($codErr);
                                
                                // Si es un rechazo crítico, activamos el flag para bloquear el resto del lote
                                if ($cat === VeriFactuErrorService::CAT_RECHAZO) {
                                    $hayRechazoPrevio = true;
                                }

                                $this->marcarError($idCola, "[$codErr] $descErr", $cat, $raw);
                                DBPDO::ejecutarConsulta("UPDATE verifactu_logs SET estado = :st WHERE id_venta = :idv AND estado = 'pendiente'", [':st' => $this->categoriaAEstadoVentas($cat), ':idv' => $idV]);
                                $resumen['fallos']++;
                            }
                        } catch (Exception $eInner) {
                            $this->marcarError($idCola, "Error interno: " . $eInner->getMessage(), VeriFactuErrorService::CAT_ERROR_TECNICO, $raw);
                            $resumen['fallos']++;
                        }
                    }
                    return $resumen;
                }
            }

            // Error global
            $categoria = VeriFactuErrorService::clasificar((string)$respuesta['code']);
            foreach ($loteIds as $idCola) {
                $this->marcarError($idCola, $respuesta['message'], $categoria, $raw);
                $resumen['fallos']++;
            }
        }

        return $resumen;
    }

    /** Helpers para el análisis de respuestas (extraídos de la lógica original) */
    private function extraerLineasRespuesta(string $raw): array {
        $saneado = preg_replace('/&(?!(amp|lt|gt|quot|apos);)/', '&amp;', $raw);
        $cleanXml = preg_replace('/<(\/?)[\w-]+:([\w-]+)/', '<$1$2', $saneado);
        $cleanXml = preg_replace('/ xmlns:[^=]+="[^"]+"/', '', $cleanXml);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($cleanXml);
        if ($xml !== false) return $xml->xpath('//RespuestaLinea');
        
        $lines = [];
        if (preg_match_all('/<RespuestaLinea>(.*?)<\/RespuestaLinea>/is', $cleanXml, $matches)) {
            foreach ($matches[1] as $m) $lines[] = $m;
        }
        return $lines;
    }

    private function analizarResultadoLinea(int $idV, array $resLineas): array {
        require_once __DIR__ . '/VentaPDO.php';
        $vData = DBPDO::ejecutarConsulta("SELECT numero_ticket, fecha, es_factura, nif_cliente, tipo_documento FROM ventas WHERE id = ?", [$idV])->fetch(PDO::FETCH_ASSOC);
        $esFact = !empty($vData['es_factura']) && !empty($vData['nif_cliente']);
        $idBuscado  = VentaPDO::formatTicketNumber($vData['numero_ticket'], $vData['fecha'], $esFact, $vData['tipo_documento']);
        // Raw numero_ticket as string (e.g. "1020315") — used when AEAT echoes the plain number
        // instead of the full formatted series (common for F2 simplificadas sent via regenerarVentaAlta)
        $idRaw = (string)$vData['numero_ticket'];

        foreach ($resLineas as $linea) {
            $lStr = is_string($linea) ? $linea : $linea->asXML();
            if (preg_match('/<NumSerieFactura>(.*?)<\/NumSerieFactura>/i', $lStr, $m)) {
                $aeatVal = trim($m[1]);
                // Match strategies (most-specific first):
                // 1. Exact match with full formatted series  e.g. "T-652026-1020315"
                // 2. Exact match with raw ticket number      e.g. "1020315"
                // 3. AEAT value is a substring of formatted  e.g. "1020315" inside "T-652026-1020315"
                // 4. Formatted is a substring of AEAT value  (rare, future-proof)
                if ($aeatVal === $idBuscado || $aeatVal === $idRaw
                    || strpos($idBuscado, $aeatVal) !== false
                    || strpos($aeatVal, $idRaw) !== false) {
                    preg_match('/<EstadoRegistro[^>]*>(.*?)<\/EstadoRegistro>/is', $lStr, $m1);
                    preg_match('/<CodigoErrorRegistro[^>]*>(.*?)<\/CodigoErrorRegistro>/is', $lStr, $m2);
                    preg_match('/<DescripcionErrorRegistro[^>]*>(.*?)<\/DescripcionErrorRegistro>/is', $lStr, $m3);
                    return [
                        'estado'  => isset($m1[1]) ? trim($m1[1]) : 'Error',
                        'codigo'  => isset($m2[1]) ? trim($m2[1]) : '0',
                        'mensaje' => isset($m3[1]) ? mb_strcut(trim(strip_tags($m3[1])), 0, 500) : 'Sin descripción'
                    ];
                }
            }
        }
        return ['estado' => 'Error', 'codigo' => '500', 'mensaje' => "No se encontró respuesta para $idBuscado"];
    }

    /**
     * Desbloquea los registros posteriores a una venta cuyo error ha sido subsanado.
     * Regenera sus XMLs (necesario por el cambio en la cadena de hashes) y los
     * re-encola con un retraso de 60 segundos.
     */
    public function desbloquearRegistrosSiguientes(int $idVenta): void
    {
        // Usar SIEMPRE ventas.fecha como referencia (no cola_envios.creado_en).
        // Motivo: subsanarVenta hace DELETE + re-INSERT en cola_envios, lo que genera
        // un creado_en = NOW() posterior a los ítems bloqueados (236, 237…).
        // ventas.fecha es la fecha de la venta original, siempre anterior a los bloqueados.
        $stmtFecha = DBPDO::ejecutarConsulta(
            "SELECT fecha FROM ventas WHERE id = :id",
            [':id' => $idVenta]
        );
        $rowFecha = $stmtFecha->fetch(PDO::FETCH_ASSOC);
        $fechaRef = $rowFecha ? $rowFecha['fecha'] : date('Y-m-d H:i:s');

        // Buscar registros bloqueados posteriores
        $stmtBloqueados = DBPDO::ejecutarConsulta(
            "SELECT c.id_venta FROM cola_envios c
             WHERE c.estado = 'bloqueado' AND c.creado_en > :fecha
             ORDER BY c.creado_en ASC",
            [':fecha' => $fechaRef]
        );
        $bloqueados = $stmtBloqueados->fetchAll(PDO::FETCH_COLUMN);

        if (empty($bloqueados)) {
            return;
        }

        require_once __DIR__ . '/VentaPDO.php';

        // Regenerar cada uno en orden para respetar la cadena de hashes
        foreach ($bloqueados as $idVentaBloq) {
            try {
                VentaPDO::regenerarVentaAlta((int)$idVentaBloq);
                // regenerarVentaAlta re-encola con estado 'pendiente'; ajustamos el delay
                DBPDO::ejecutarConsulta(
                    "UPDATE cola_envios SET fecha_proximo_intento = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 5 SECOND)
                     WHERE id_venta = :id AND estado = 'pendiente'",
                    [':id' => $idVentaBloq]
                );
            } catch (\Exception $e) {
                error_log("VeriFactu desbloqueo error en venta $idVentaBloq: " . $e->getMessage());
            }
        }
    }

    /**
     * Envía un conjunto de facturas en un único mensaje SOAP.
     */
    private function enviarLote(array $xmlPaths, array $loteRaw = [])
    {
        require_once __DIR__ . '/VeriFactuService.php';
        $vfService = new VeriFactuService();
        $api = new AeatApi();

        // Si algún registro del lote ya ha tenido intentos previos, marcamos el lote con incidencia 'S'
        $hayIncidencia = false;
        foreach ($loteRaw as $row) {
            if (($row['intentos'] ?? 0) > 0) {
                $hayIncidencia = true;
                break;
            }
        }

        try {
            $xmlLote = $vfService->generarXmlLote($xmlPaths, ['incidencia' => $hayIncidencia]);
            return $api->enviarFactura($xmlLote);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Error al generar/enviar lote: " . $e->getMessage(),
                'code'    => 500
            ];
        }
    }

    private function marcarEnviado(int $idCola, int $idVenta, string $respuestaAeat, string $loteId = '')
    {
        $sql = "UPDATE cola_envios
                SET estado = 'enviado',
                    intentos = intentos + 1,
                    respuesta_aeat = :resp,
                    fecha_envio = CURRENT_TIMESTAMP,
                    lote_id = :lote_id
                WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [':id' => $idCola, ':resp' => $respuestaAeat, ':lote_id' => $loteId ?: null]);

        // Si quedan otras entradas pendientes para esta venta (ej. anulación + alta correctiva),
        // no marcar como 'enviado' hasta que todas hayan sido procesadas.
        $pendingOthers = (int) DBPDO::ejecutarConsulta(
            "SELECT COUNT(*) FROM cola_envios WHERE id_venta = :idv AND id != :id AND estado = 'pendiente'",
            [':idv' => $idVenta, ':id' => $idCola]
        )->fetchColumn();

        if ($pendingOthers === 0) {
            DBPDO::ejecutarConsulta(
                "UPDATE ventas SET estado_envio_aeat = 'enviado' WHERE id = :id_venta",
                [':id_venta' => $idVenta]
            );
        }
    }

    /**
     * Marca un registro con error según su categoría:
     * - CAT_CONEXION       → 'pendiente', reintento en 60s. NO BLOQUEA.
     * - CAT_RECHAZO        → 'error_critico', BLOQUEA posteriores.
     * - CAT_SUBSANAR       → 'error_critico', NO BLOQUEA posteriores (Aceptado con Errores).
     * - CAT_EXITO          → marcado como enviado (duplicado AEAT).
     */
    private function marcarError(int $idCola, string $error, string $categoria, string $respuestaAeat = '')
    {
        require_once __DIR__ . '/VeriFactuErrorService.php';

        if ($categoria === VeriFactuErrorService::CAT_EXITO) {
            $stmtIdV = DBPDO::ejecutarConsulta("SELECT id_venta FROM cola_envios WHERE id = :id", [':id' => $idCola]);
            $idVenta = (int)$stmtIdV->fetchColumn();
            $this->marcarEnviado($idCola, $idVenta, $respuestaAeat);
            return;
        }

        if ($categoria === VeriFactuErrorService::CAT_CONEXION) {
            // Error de conexión → reintento automático en 60 segundos
            // Al reprogramarse en el futuro, bloquea la cola naturalmente por FIFO en la siguiente ejecución
            $sql = "UPDATE cola_envios
                    SET estado = 'pendiente',
                        intentos = intentos + 1,
                        ultimo_error = :error,
                        respuesta_aeat = :resp,
                        fecha_proximo_intento = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 60 SECOND)
                    WHERE id = :id";
            DBPDO::ejecutarConsulta($sql, [
                ':error' => mb_strcut($error, 0, 1000),
                ':resp'  => $respuestaAeat,
                ':id'    => $idCola
            ]);

            DBPDO::ejecutarConsulta(
                "UPDATE ventas SET estado_envio_aeat = 'pendiente' WHERE id = (SELECT id_venta FROM cola_envios WHERE id = :id)",
                [':id' => $idCola]
            );
            return;
        }

        // CAT_RECHAZO o CAT_SUBSANAR
        // CAT_RECHAZO, CAT_SUBSANAR o CAT_BLOQUEO_CADENA
        $estadoVentas = $this->categoriaAEstadoVentas($categoria);
        $estadoCola   = ($categoria === self::CAT_BLOQUEO_CADENA) ? 'bloqueado' : 'error_critico';

        $sql = "UPDATE cola_envios
                SET estado = :estadoC,
                    intentos = intentos + 1,
                    ultimo_error = :error,
                    respuesta_aeat = :resp,
                    fecha_proximo_intento = NULL
                WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':estadoC' => $estadoCola,
            ':error'   => mb_strcut($error, 0, 1000),
            ':resp'    => $respuestaAeat,
            ':id'      => $idCola
        ]);

        DBPDO::ejecutarConsulta(
            "UPDATE ventas SET estado_envio_aeat = :estado WHERE id = (SELECT id_venta FROM cola_envios WHERE id = :id)",
            [':estado' => $estadoVentas, ':id' => $idCola]
        );

        // SOLO BLOQUEAR EN CASCADA SI ES RECHAZO (Cat. rechazo)
        // Si es subsanable (aceptado con errores), el resto de la cadena puede seguir
        if ($categoria === VeriFactuErrorService::CAT_RECHAZO) {
            $stmtFecha = DBPDO::ejecutarConsulta(
                "SELECT creado_en FROM cola_envios WHERE id = :id", [':id' => $idCola]
            );
            $fechaActual = $stmtFecha->fetchColumn();
            if ($fechaActual) {
                DBPDO::ejecutarConsulta(
                    "UPDATE cola_envios SET estado = 'bloqueado' WHERE estado = 'pendiente' AND creado_en > :f",
                    [':f' => $fechaActual]
                );
                DBPDO::ejecutarConsulta(
                    "UPDATE ventas SET estado_envio_aeat = 'bloqueado'
                     WHERE id IN (SELECT id_venta FROM cola_envios WHERE estado = 'bloqueado')"
                );
            }
        }
    }

    const CAT_BLOQUEO_CADENA = 'bloqueo_cadena';

    /** Convierte categoría de error al estado correspondiente en tabla ventas. */
    private function categoriaAEstadoVentas(string $categoria): string
    {
        require_once __DIR__ . '/VeriFactuErrorService.php';
        if ($categoria === VeriFactuErrorService::CAT_SUBSANAR) return 'subsanacion_pendiente';
        if ($categoria === VeriFactuErrorService::CAT_RECHAZO)  return 'error_critico';
        if ($categoria === self::CAT_BLOQUEO_CADENA)           return 'bloqueado';
        return 'pendiente';
    }

    /**
     * Obtiene estadísticas de la cola para el panel de control.
     */
    public function obtenerEstadisticas()
    {
        $sql = "SELECT
                    SUM(CASE WHEN c.estado = 'pendiente'    THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN c.estado = 'enviado'      THEN 1 ELSE 0 END) as enviados,
                    SUM(CASE WHEN c.estado = 'error_critico' THEN 1 ELSE 0 END) as errores,
                    SUM(CASE WHEN c.estado = 'bloqueado'    THEN 1 ELSE 0 END) as bloqueados
                FROM cola_envios c
                JOIN ventas v ON c.id_venta = v.id
                WHERE v.estado_envio_aeat NOT IN ('enviado', 'anulado') OR c.estado = 'enviado'";
        $res = DBPDO::ejecutarConsulta($sql);
        return $res->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista los últimos movimientos de la cola.
     */
    public function listarUltimosMovimientos(int $limite = 20)
    {
        $limite = (int)$limite;
        $sql = "SELECT c.*, v.numero_ticket
                FROM cola_envios c
                JOIN ventas v ON c.id_venta = v.id
                ORDER BY c.creado_en DESC LIMIT $limite";
        $res = DBPDO::ejecutarConsulta($sql);
        $rows = $res->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['xml_content'] = file_exists($row['xml_path'] ?? '') ? file_get_contents($row['xml_path']) : 'No disponible';
        }
        return $rows;
    }

    /**
     * Lista solo los registros que están actualmente en la cola de reenvío automático.
     */
    public function listarColaPendiente()
    {
        $sql = "SELECT c.*, v.numero_ticket, v.estado_envio_aeat as venta_estado,
                       v.es_factura, v.nombre_cliente, v.nif_cliente
                FROM cola_envios c
                JOIN ventas v ON c.id_venta = v.id
                WHERE c.estado IN ('pendiente', 'error_critico', 'bloqueado')
                  AND v.estado_envio_aeat NOT IN ('enviado', 'anulado')
                ORDER BY c.creado_en ASC";
        $res = DBPDO::ejecutarConsulta($sql);
        $rows = $res->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['xml_content'] = file_exists($row['xml_path'] ?? '') ? file_get_contents($row['xml_path']) : 'No disponible';
        }
        return $rows;
    }

    /**
     * Resetea un envío para que sea procesado de nuevo inmediatamente.
     */
    public function reintentarEnvio(int $idCola)
    {
        $sql = "UPDATE cola_envios
                SET estado = 'pendiente',
                    fecha_proximo_intento = CURRENT_TIMESTAMP
                WHERE id = :id";
        return DBPDO::ejecutarConsulta($sql, [':id' => $idCola]);
    }

    public function enviarEspecifico(int $idVenta)
    {
        $api = new AeatApi();
        $sql = "SELECT id, id_venta, xml_path, intentos FROM cola_envios WHERE id_venta = :id_venta";
        $stmt = DBPDO::ejecutarConsulta($sql, [':id_venta' => $idVenta]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !file_exists($row['xml_path'] ?? '')) return false;

        $xmlContent = file_get_contents($row['xml_path']);
        $respuesta  = $api->enviarFactura($xmlContent);

        if ($respuesta['success']) {
            $this->marcarEnviado($row['id'], $row['id_venta'], $respuesta['raw_response'] ?? '');
            return true;
        } else {
            require_once __DIR__ . '/VeriFactuErrorService.php';
            $categoria = VeriFactuErrorService::clasificar((string)$respuesta['code']);
            $this->marcarError($row['id'], $respuesta['message'], $categoria, $respuesta['raw_response'] ?? '');
            return false;
        }
    }

    /**
     * Obtiene un resumen detallado para el semáforo y notificaciones del dashboard.
     */
    public function obtenerResumenEstado()
    {
        $resumen = [
            'pendientes_total' => 0,
            'pendientes_24h'   => 0,
            'criticos'         => 0,
            'subsanaciones'    => 0,
            'bloqueados'       => 0,
        ];

        try {
            // AUTO-HEALING: Si hay registros bloqueados pero ya no existen errores reales en la tabla ventas,
            // los liberamos automáticamente para que vuelvan a la cola de reenvío.
            $sqlCheckErrors = "SELECT COUNT(*) FROM ventas WHERE estado_envio_aeat IN ('error_critico', 'subsanacion_pendiente')";
            $hasActiveErrors = ((int)DBPDO::ejecutarConsulta($sqlCheckErrors)->fetchColumn() > 0);
            
            if (!$hasActiveErrors) {
                DBPDO::ejecutarConsulta("UPDATE cola_envios SET estado = 'pendiente' WHERE estado = 'bloqueado'");
            }

            // Incidencias Críticas y Subsanaciones: La verdad está en la tabla ventas (vista del usuario)
            $sqlVentas = "SELECT 
                            SUM(CASE WHEN estado_envio_aeat = 'error_critico' THEN 1 ELSE 0 END) as criticos,
                            SUM(CASE WHEN estado_envio_aeat = 'subsanacion_pendiente' THEN 1 ELSE 0 END) as subsanaciones
                         FROM ventas";
            $qVentas = DBPDO::ejecutarConsulta($sqlVentas)->fetch(PDO::FETCH_ASSOC);

            $resumen['criticos']      = (int)($qVentas['criticos'] ?? 0);
            $resumen['subsanaciones'] = (int)($qVentas['subsanaciones'] ?? 0);

            // Cola de Reenvío: La verdad está en cola_envios, pero filtrando solo lo que no esté 'enviado' en ventas
            $sqlQueue = "SELECT 
                            SUM(CASE WHEN c.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                            SUM(CASE WHEN c.estado = 'bloqueado'  THEN 1 ELSE 0 END) as bloqueados
                         FROM cola_envios c
                         JOIN ventas v ON c.id_venta = v.id
                         WHERE v.estado_envio_aeat NOT IN ('enviado', 'anulado')";
            $qQueue = DBPDO::ejecutarConsulta($sqlQueue)->fetch(PDO::FETCH_ASSOC);

            $resumen['pendientes_total'] = (int)($qQueue['pendientes'] ?? 0);
            $resumen['bloqueados']       = (int)($qQueue['bloqueados'] ?? 0);

            $sql24 = "SELECT COUNT(*) FROM cola_envios c
                      JOIN ventas v ON c.id_venta = v.id
                      WHERE c.estado = 'pendiente' 
                        AND v.estado_envio_aeat NOT IN ('enviado', 'anulado')
                        AND c.creado_en < (NOW() - INTERVAL 1 DAY)";
            $resumen['pendientes_24h'] = (int)DBPDO::ejecutarConsulta($sql24)->fetchColumn();

            // Próximo envío programado — formato ISO 8601 para que JS lo parsee bien
            $sqlNext = "SELECT DATE_FORMAT(COALESCE(c.fecha_proximo_intento, c.creado_en), '%Y-%m-%dT%H:%i:%s') FROM cola_envios c
                        JOIN ventas v ON c.id_venta = v.id
                        WHERE c.estado = 'pendiente' 
                          AND v.estado_envio_aeat NOT IN ('enviado', 'anulado')
                        ORDER BY c.fecha_proximo_intento ASC, c.id ASC LIMIT 1";
            $resumen['proximo_envio'] = DBPDO::ejecutarConsulta($sqlNext)->fetchColumn();

        } catch (\Exception $e) {
            error_log("Error resumen AEAT: " . $e->getMessage());
        }

        return $resumen;
    }
}
