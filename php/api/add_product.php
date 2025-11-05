<?php
// php/api/add_product.php
// Recibe POST multipart/form-data desde inventory.html y crea un nuevo producto.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Campos obligatorios y opcionales
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : null;
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
    $precio = isset($_POST['precio']) ? $_POST['precio'] : null;
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $id_categoria = isset($_POST['id_categoria']) && $_POST['id_categoria'] !== '' ? (int)$_POST['id_categoria'] : null;
    $id_vendedor = isset($_POST['id_vendedor']) && $_POST['id_vendedor'] !== '' ? (int)$_POST['id_vendedor'] : null;
    $fecha_publicacion = isset($_POST['fecha_publicacion']) && $_POST['fecha_publicacion'] !== '' ? $_POST['fecha_publicacion'] : date('Y-m-d H:i:s');
    $estado = isset($_POST['estado']) ? $_POST['estado'] : 'disponible';
    // En el formulario se llama en_descuento; en la BD la columna es descuento_activo
    $descuento_activo = isset($_POST['en_descuento']) && ($_POST['en_descuento'] === 'on' || $_POST['en_descuento'] == '1') ? 1 : 0;

    // Validaciones básicas
    if (!$nombre || $nombre === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }
    if ($precio === null || $precio === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El precio es obligatorio']);
        exit;
    }
    if (!is_numeric($precio)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Precio inválido']);
        exit;
    }

    // Manejo de imagen (opcional)
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['image'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir la imagen: ' . $f['error']);
        }

        // Validar tipo MIME mediante finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/jpg' => '.jpg'];
        if (!array_key_exists($mime, $allowed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Formato de imagen no permitido. Usa jpg o png']);
            exit;
        }

        // Tamaño máximo (2 MB)
        $maxSize = 2 * 1024 * 1024;
        if ($f['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Imagen demasiado grande (máx 2MB)']);
            exit;
        }

        // Directorio destino
        $destDir = __DIR__ . '/../../assets/img/products/';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $ext = $allowed[$mime];
        $safeName = uniqid('p_', true) . $ext;
        $destPath = $destDir . $safeName;

        if (!move_uploaded_file($f['tmp_name'], $destPath)) {
            throw new Exception('No se pudo mover el archivo subido');
        }

        // Ruta accesible desde web
        $imagePath = '/assets/img/products/' . $safeName;
    }

    // Insertar en la BD (id_producto es AUTO_INCREMENT/AI en la BD)
    $sql = "INSERT INTO Productos (nombre, descripcion, precio, stock, id_categoria, id_vendedor, fecha_publicacion, estado, descuento_activo)
            VALUES (:nombre, :descripcion, :precio, :stock, :id_categoria, :id_vendedor, :fecha_publicacion, :estado, :descuento_activo)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $nombre,
        ':descripcion' => $descripcion,
        ':precio' => $precio,
        ':stock' => $stock,
        ':id_categoria' => $id_categoria,
        ':id_vendedor' => $id_vendedor,
        ':fecha_publicacion' => $fecha_publicacion,
        ':estado' => $estado,
        ':descuento_activo' => $descuento_activo
    ]);

    $newId = $pdo->lastInsertId();

    // Respuesta
    echo json_encode(['success' => true, 'id_producto' => (int)$newId, 'image' => $imagePath]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    error_log('Add Product error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}

?>
