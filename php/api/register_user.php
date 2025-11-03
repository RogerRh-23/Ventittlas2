<?php
// php/api/register_user.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conect.php';

// Read input: try JSON body first, fall back to form-encoded ($_POST)
$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!is_array($data)) {
    if (strlen(trim($body)) > 0) {
        $jerr = json_last_error_msg();
        error_log("register_user: JSON decode error: $jerr | raw: " . substr($body,0,1000));
    }
    $data = $_POST;
}

// normalize field names
$get = function($en, $es) use ($data) {
    if (isset($data[$en]) && $data[$en] !== '') return $data[$en];
    if (isset($data[$es]) && $data[$es] !== '') return $data[$es];
    return null;
};

$nombre = trim((string)($get('nombre','full_name') ?? ''));
$correo = trim((string)($get('correo_electronico','email') ?? ''));
$contrasena = $get('contrasena','password');
$rol = trim((string)($get('rol','role') ?? 'cliente'));

// allowed roles based on table enum
$allowed_roles = ['cliente','vendedor','administrador'];
if (!in_array($rol, $allowed_roles, true)) {
    $rol = 'cliente';
}

if ($nombre === '' || $correo === '' || $contrasena === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Faltan campos obligatorios: nombre, correo_electronico, contrasena']);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Email inválido']);
    exit;
}

try {
    // existence check
    $stmt = $pdo->prepare('SELECT 1 FROM Usuarios WHERE correo_electronico = ? LIMIT 1');
    $stmt->execute([$correo]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'El correo ya está registrado']);
        exit;
    }

    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // insert into table: nombre, correo_electronico, contrasena_hash, rol
    $sql = "INSERT INTO Usuarios (nombre, correo_electronico, contrasena_hash, rol) VALUES (:nombre, :correo, :hash, :rol)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':hash' => $hash,
        ':rol' => $rol
    ]);

    echo json_encode(['ok' => true, 'message' => 'Usuario registrado']);
} catch (PDOException $e) {
    error_log('register_user error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error interno al registrar usuario']);
}

?>