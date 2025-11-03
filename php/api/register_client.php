<?php
// php/api/register_client.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conect.php';

// Read input: try JSON body first, fall back to form-encoded ($_POST)
$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!is_array($data)) {
    // JSON decode failed or no JSON provided — fall back to $_POST
    // include json_last_error_msg to help debugging when JSON was present but malformed
    if (strlen(trim($body)) > 0) {
        $jerr = json_last_error_msg();
        // return informative message but still allow form fallback
        // note: don't exit here to permit form-encoded clients
        error_log("register_client: JSON decode error: $jerr | raw: " . substr($body,0,1000));
    }
    $data = $_POST;
}

// Accept both English and Spanish field names. Normalize into variables.
$get = function($en, $es) use ($data) {
    if (isset($data[$en]) && $data[$en] !== '') return $data[$en];
    if (isset($data[$es]) && $data[$es] !== '') return $data[$es];
    return null;
};

$full = trim((string)($get('full_name','nombre') ?? ''));
$phone = trim((string)($get('phone','telefono') ?? ''));
$email = trim((string)($get('email','correo_electronico') ?? ''));
$password = $get('password','contrasena');
$address = trim((string)($get('address','direccion') ?? ''));

// Required fields (after normalization)
$required = ['nombre/full_name','telefono/phone','correo_electronico/email','contrasena/password','direccion/address'];
if ($full === '' || $phone === '' || $email === '' || $password === null || $address === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Faltan campos obligatorios (nombre, telefono, correo_electronico, contrasena, direccion)']);
    exit;
}

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
