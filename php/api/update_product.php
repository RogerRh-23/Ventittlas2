<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../conect.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

$id = isset($input['id']) ? (int)$input['id'] : (isset($input['id_producto']) ? (int)$input['id_producto'] : 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid product id (id or id_producto)']);
    exit;
}

$allowedEstados = ['disponible', 'sin stock', 'reservado', 'vendido'];

$fields = [];
$params = [];

if (isset($input['nombre'])) {
    $fields[] = 'nombre = ?';
    $params[] = trim($input['nombre']);
}
if (isset($input['descripcion'])) {
    $fields[] = 'descripcion = ?';
    $params[] = trim($input['descripcion']);
}
if (isset($input['precio'])) {
    if (!is_numeric($input['precio'])) {
        http_response_code(400);
        echo json_encode(['error' => 'precio must be a number']);
        exit;
    }
    $precio = number_format((float)$input['precio'], 2, '.', '');
    $fields[] = 'precio = ?';
    $params[] = $precio;
}
if (isset($input['stock'])) {
    $stock = (int)$input['stock'];
    if ($stock < 0) $stock = 0;
    $fields[] = 'stock = ?';
    $params[] = $stock;
}
if (isset($input['estado'])) {
    $estado = trim($input['estado']);
    if (!in_array($estado, $allowedEstados, true)) {
        $estado = 'disponible';
    }
    $fields[] = 'estado = ?';
    $params[] = $estado;
}

$categoriaName = isset($input['categoria']) ? trim($input['categoria']) : null;

try {
    $pdo->beginTransaction();

    $id_categoria = null;
    if ($categoriaName !== null) {
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
        $fields[] = 'id_categoria = ?';
        $params[] = $id_categoria;
    }

    if (empty($fields)) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'No fields provided to update']);
        exit;
    }

    $set = implode(', ', $fields);
    $params[] = $id;

    $sql = "UPDATE Productos SET {$set} WHERE id_producto = ?";
    $upd = $pdo->prepare($sql);
    $upd->execute($params);

    $sel = $pdo->prepare('SELECT p.*, c.nombre AS categoria FROM Productos p LEFT JOIN Categorias c ON p.id_categoria = c.id_categoria WHERE p.id_producto = ? LIMIT 1');
    $sel->execute([$id]);
    $product = $sel->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    if (isset($product['precio'])) $product['precio'] = (float)$product['precio'];
    if (isset($product['stock'])) $product['stock'] = (int)$product['stock'];
    if (isset($product['id_categoria'])) $product['id_categoria'] = $product['id_categoria'] !== null ? (int)$product['id_categoria'] : null;
    if (isset($product['id_vendedor'])) $product['id_vendedor'] = $product['id_vendedor'] !== null ? (int)$product['id_vendedor'] : null;

    echo json_encode(['ok' => true, 'product' => $product], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    error_log('Update product error: ' . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
}

?>
