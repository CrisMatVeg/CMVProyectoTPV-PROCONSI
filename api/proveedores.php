<?php
session_start();
header('Content-Type: application/json');
require_once '../model/ProveedorPDO.php';
// Verificar sesión
if (!isset($_SESSION['usuarioActualTPV'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $proveedor = ProveedorPDO::buscarPorId($_GET['id']);
            echo json_encode($proveedor ? [
                'id' => $proveedor->getId(),
                'cif_nif' => $proveedor->getCifNif(),
                'nombre' => $proveedor->getNombre(),
                'direccion' => $proveedor->getDireccion(),
                'telefono' => $proveedor->getTelefono(),
                'email' => $proveedor->getEmail(),
                'aplica_re' => $proveedor->getAplicaRe(),
                'notas' => $proveedor->getNotas(),
                'activo' => $proveedor->getActivo()
            ] : null);
        } else {
            $proveedores = ProveedorPDO::listarTodos(isset($_GET['soloActivos']) ? ($_GET['soloActivos'] === 'true') : true);
            $res = [];
            foreach ($proveedores as $p) {
                $res[] = [
                    'id' => $p->getId(),
                    'cif_nif' => $p->getCifNif(),
                    'nombre' => $p->getNombre(),
                    'direccion' => $p->getDireccion(),
                    'telefono' => $p->getTelefono(),
                    'email' => $p->getEmail(),
                    'aplica_re' => $p->getAplicaRe(),
                    'activo' => $p->getActivo()
                ];
            }
            echo json_encode($res);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['nombre']) || !isset($data['cif_nif'])) {
            echo json_encode(['error' => 'Datos incompletos']);
            break;
        }

        if (isset($data['id'])) {
            // Editar
            $success = ProveedorPDO::editarProveedor(
                $data['id'],
                $data['cif_nif'],
                $data['nombre'],
                $data['direccion'] ?? '',
                $data['telefono'] ?? '',
                $data['email'] ?? '',
                $data['aplica_re'] ?? false,
                $data['notas'] ?? '',
                $data['activo'] ?? true
            );
            echo json_encode(['success' => $success]);
        } else {
            // Añadir
            $id = ProveedorPDO::añadirProveedor(
                $data['cif_nif'],
                $data['nombre'],
                $data['direccion'] ?? '',
                $data['telefono'] ?? '',
                $data['email'] ?? '',
                $data['aplica_re'] ?? false,
                $data['notas'] ?? ''
            );
            echo json_encode(['success' => (bool)$id, 'id' => $id]);
        }
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            $success = ProveedorPDO::borrarProveedor($_GET['id']);
            echo json_encode(['success' => $success]);
        }
        break;
}
