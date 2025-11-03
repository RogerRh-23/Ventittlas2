<?php
// php/api/register_client.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conect.php';

// Read JSON body
$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'JSON inválido']);
    exit;
}

// Required fields
$required = ['full_name','phone','email','password','address'];
foreach ($required as $f) {
    if (empty($data[$f])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => "Campo requerido: $f"]);
        exit;
    }
}

$full = trim($data['full_name']);
$phone = trim($data['phone']);
$email = trim($data['email']);
$password = $data['password'];
$address = trim($data['address']);

// basic email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Email inválido']);
    exit;
}

try {
    // check duplicate email
    $stmt = $pdo->prepare('SELECT id FROM Usuarios WHERE correo_electronico = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'El correo ya está registrado']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // insert client. columns: nombre, telefono, correo_electronico, contrasena_hash, direccion, rol
    $sql = "INSERT INTO Usuarios (nombre, telefono, correo_electronico, contrasena_hash, direccion, rol)
            VALUES (:nombre, :telefono, :correo, :hash, :direccion, 'cliente')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $full,
        ':telefono' => $phone,
        ':correo' => $email,
        ':hash' => $hash,
        ':direccion' => $address
    ]);

    echo json_encode(['ok' => true, 'message' => 'Cliente registrado']);
} catch (PDOException $e) {
    error_log('register_client error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error interno al registrar cliente']);
}

?>
