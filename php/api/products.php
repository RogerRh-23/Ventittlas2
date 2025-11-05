<?php
// php/api/products.php
// Devuelve lista de productos en JSON consumible por el frontend
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conect.php';

try {
    // Consulta alineada con la estructura de la tabla Productos proporcionada
    $sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock,
                   p.id_categoria, p.id_vendedor, p.fecha_publicacion, p.estado,
                   p.descuento_activo, c.nombre AS category_name
            FROM Productos p
            LEFT JOIN Categorias c ON p.id_categoria = c.id_categoria";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = array_map(function($r) {
        return [
            'id_producto' => isset($r['id_producto']) ? (int)$r['id_producto'] : null,
            'nombre' => $r['nombre'] ?? '',
            'descripcion' => $r['descripcion'] ?? '',
            'precio' => isset($r['precio']) ? (float)$r['precio'] : null,
            'stock' => isset($r['stock']) ? (int)$r['stock'] : 0,
            'id_categoria' => isset($r['id_categoria']) ? (int)$r['id_categoria'] : null,
            'id_vendedor' => isset($r['id_vendedor']) ? (int)$r['id_vendedor'] : null,
            'fecha_publicacion' => $r['fecha_publicacion'] ?? null,
            'estado' => $r['estado'] ?? null,
            // columna específica de la BD
            'descuento_activo' => isset($r['descuento_activo']) ? (int)$r['descuento_activo'] : 0,
            // campos auxiliares/compatibilidad
            'category_name' => $r['category_name'] ?? '',
            'image' => '/assets/img/products/placeholder.png',
            'available' => (isset($r['stock']) && (int)$r['stock'] > 0),
            // mapear descuento a una llave más legible para el frontend
            'on_discount' => (isset($r['descuento_activo']) && ((int)$r['descuento_activo'] === 1)),
            'discount_percentage' => null
        ];
    }, $rows);

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Products API error: ' . $e->getMessage());
    echo json_encode([]);
}

?>
