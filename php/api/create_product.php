<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../conect.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

$nombre = isset($input['nombre']) ? trim($input['nombre']) : null;
$descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : null;
$precio = isset($input['precio']) ? $input['precio'] : null;
$stock = isset($input['stock']) ? (int)$input['stock'] : 1;
$categoriaName = isset($input['categoria']) ? trim($input['categoria']) : null;
$estado = isset($input['estado']) ? trim($input['estado']) : 'disponible';

$allowedEstados = ['disponible', 'sin stock'];

// Basic validation
if (!$nombre || !$descripcion || $precio === null || $precio === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: nombre, descripcion, precio']);
    exit;
}
if (!is_numeric($precio)) {
    http_response_code(400);
    echo json_encode(['error' => 'precio must be a number']);
    exit;
}
$precio = number_format((float)$precio, 2, '.', '');

if ($stock < 0) $stock = 0;

if (!in_array($estado, $allowedEstados, true)) {
    $estado = 'disponible';
}

try {
    $pdo->beginTransaction();

    $id_categoria = null;
    if ($categoriaName) {
        $stmt = $pdo->prepare('SELECT id_categoria FROM Categorias WHERE LOWER(nombre) = LOWER(?) LIMIT 1');
        $stmt->execute([$categoriaName]);
        $row = $stmt->fetch();
        if ($row && isset($row['id_categoria'])) {
            $id_categoria = (int)$row['id_categoria'];
        } else {
            $ins = $pdo->prepare('INSERT INTO Categorias (nombre, descripcion) VALUES (?, ?)');
            $ins->execute([$categoriaName, 'Creado automáticamente']);
            $id_categoria = (int)$pdo->lastInsertId();
        }
    }

    $insert = $pdo->prepare('INSERT INTO Productos (nombre, descripcion, precio, stock, id_categoria, id_vendedor, estado) VALUES (?, ?, ?, ?, ?, NULL, ?)');
    $insert->execute([$nombre, $descripcion, $precio, $stock, $id_categoria, $estado]);
    $newId = (int)$pdo->lastInsertId();

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'ok' => true,
        'id' => $newId,
        'product' => [
            'id' => $newId,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => (float)$precio,
            'stock' => $stock,
            'id_categoria' => $id_categoria,
            'estado' => $estado,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    error_log('Create product error: ' . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
}

?>
