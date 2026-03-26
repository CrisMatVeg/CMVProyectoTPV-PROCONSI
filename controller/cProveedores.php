<?php
require_once 'model/ProveedorPDO.php';
// Verificar sesión
if (!isset($_SESSION['usuarioActualTPV'])) {
    $_SESSION['paginaEnCurso'] = 'Login';
    header('Location: index.php');
    exit;
}

$avProveedorSet = [];
$proveedores = ProveedorPDO::listarTodos(false);

foreach ($proveedores as $p) {
    $avProveedorSet[] = [
        'id' => $p->getId(),
        'cif_nif' => $p->getCifNif(),
        'nombre' => $p->getNombre(),
        'direccion' => $p->getDireccion(),
        'telefono' => $p->getTelefono(),
        'email' => $p->getEmail(),
        'aplica_re' => $p->getAplicaRe(),
        'notas' => $p->getNotas(),
        'activo' => $p->getActivo()
    ];
}

$avInicioPrivado = [
    'proveedores' => $avProveedorSet,
    'nombre_completo' => $_SESSION['usuarioActualTPV']->getNombre(),
    'esAdmin' => ($_SESSION['usuarioActualTPV']->getRol() === 'admin')
];

// Carga la vista layout principal
require_once $view["layout"];
