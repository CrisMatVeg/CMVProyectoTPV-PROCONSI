<?php

/**
 * Calcula paginación a partir de $_GET['p'] y un tamaño de página.
 * Devuelve ['pag', 'limit', 'offset'].
 */
function obtenerPaginacion(int $perPagina = 50): array {
    $pag    = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
    $offset = ($pag - 1) * $perPagina;
    return ['pag' => $pag, 'limit' => $perPagina, 'offset' => $offset];
}
