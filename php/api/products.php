<?php
// php/api/products.php
// Devuelve lista de productos en JSON consumible por el frontend
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conect.php';

try {
    $sql = "SELECT p.id_producto AS id, p.nombre AS title, p.descripcion AS description,
                   p.precio AS price, p.stock, p.id_categoria, c.nombre AS category
            FROM Productos p
            LEFT JOIN Categorias c ON p.id_categoria = c.id_categoria
            WHERE p.estado = 'disponible'";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    $out = array_map(function($r) {
        return [
            'id' => (int)$r['id'],
            'title' => $r['title'],
            'description' => $r['description'],
            'price' => (float)$r['price'],
            // no hay campo imagen en la tabla por ahora
            'image' => '/assets/img/products/placeholder.png',
            'stock' => (int)$r['stock'],
            'department' => '',
            'category' => $r['category'] ?? '',
            'best_seller' => false,
            'available' => ((int)$r['stock']) > 0,
            'url' => "/pages/products.html?id=" . (int)$r['id']
        ];
    }, $rows);

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Products API error: ' . $e->getMessage());
    echo json_encode([]);
}

?>
