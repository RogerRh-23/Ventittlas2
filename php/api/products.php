<?php
// php/api/products.php
// Devuelve lista de productos en JSON consumible por el frontend
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conect.php';

try {
    // Detectar columnas existentes en la tabla Productos para añadir lógica de descuento si aplica
    $colStmt = $pdo->query("SHOW COLUMNS FROM Productos");
    $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN);

    // Campos base que siempre queremos seleccionar
    $select = [
        'p.id_producto', 'p.nombre', 'p.descripcion', 'p.precio', 'p.stock',
        'p.id_categoria', 'p.id_vendedor', 'p.fecha_publicacion', 'p.estado'
    ];

    // Campos relacionados con descuentos que pueden o no existir en la tabla
    $possibleDiscountCols = ['precio_original', 'precio_antes', 'descuento_porcentaje', 'en_descuento', 'descuento'];
    foreach ($possibleDiscountCols as $c) {
        if (in_array($c, $cols)) {
            $select[] = "p." . $c;
        }
    }

    // Añadimos el nombre de categoría desde la tabla Categorias
    $select[] = 'c.nombre AS category_name';

    $sql = 'SELECT ' . implode(', ', $select) . ' FROM Productos p LEFT JOIN Categorias c ON p.id_categoria = c.id_categoria';

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = array_map(function($r) use ($cols) {
        // calcular estado de descuento y porcentaje si hay datos
        $onDiscount = false;
        $discountPercent = null;

        // caso directo: columna descuento_porcentaje
        if (isset($r['descuento_porcentaje']) && $r['descuento_porcentaje'] !== null && $r['descuento_porcentaje'] !== '') {
            $discountPercent = (float)$r['descuento_porcentaje'];
            $onDiscount = $discountPercent > 0;
        }

        // caso: existe precio_original/precio_antes y es mayor que precio actual -> calcular porcentaje
        if (($discountPercent === null || $discountPercent == 0) && (isset($r['precio_original']) || isset($r['precio_antes']))) {
            $orig = isset($r['precio_original']) ? (float)$r['precio_original'] : (isset($r['precio_antes']) ? (float)$r['precio_antes'] : null);
            $precio = isset($r['precio']) ? (float)$r['precio'] : null;
            if ($orig && $precio && $orig > $precio) {
                $discountPercent = round((($orig - $precio) / $orig) * 100, 2);
                $onDiscount = true;
            }
        }

        // caso: bandera en_descuento o descuento (booleano/enum)
        if (!$onDiscount && isset($r['en_descuento'])) {
            $val = $r['en_descuento'];
            if ($val === '1' || $val === 1 || strtolower($val) === 'si' || strtolower($val) === 'true') {
                $onDiscount = true;
                if (isset($r['descuento'])) {
                    $discountPercent = is_numeric($r['descuento']) ? (float)$r['descuento'] : $discountPercent;
                }
            }
        }

        $item = [
            'id_producto' => isset($r['id_producto']) ? (int)$r['id_producto'] : null,
            'nombre' => $r['nombre'] ?? '',
            'descripcion' => $r['descripcion'] ?? '',
            'precio' => isset($r['precio']) ? (float)$r['precio'] : null,
            'stock' => isset($r['stock']) ? (int)$r['stock'] : 0,
            'id_categoria' => isset($r['id_categoria']) ? (int)$r['id_categoria'] : null,
            'id_vendedor' => isset($r['id_vendedor']) ? (int)$r['id_vendedor'] : null,
            'fecha_publicacion' => $r['fecha_publicacion'] ?? null,
            'estado' => $r['estado'] ?? null,
            'category_name' => $r['category_name'] ?? '',
            'image' => '/assets/img/products/placeholder.png',
            'available' => (isset($r['stock']) && (int)$r['stock'] > 0),
            // descuento calculado / detectado
            'on_discount' => $onDiscount,
            'discount_percentage' => $discountPercent
        ];

        // incluir precio_original si existe para referencia
        if (isset($r['precio_original'])) {
            $item['precio_original'] = is_numeric($r['precio_original']) ? (float)$r['precio_original'] : $r['precio_original'];
        }

        return $item;
    }, $rows);

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Products API error: ' . $e->getMessage());
    echo json_encode([]);
}

?>
