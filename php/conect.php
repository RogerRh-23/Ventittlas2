<?php
// php/conect.php
// Conexión PDO reutilizable. Carga php/.env si existe y usa variables de entorno.

// Simple loader para archivos .env (clave=valor). No usa dependencias externas.
function load_dotenv($path)
{
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        // split on first '='
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $key = trim($parts[0]);
        $val = trim($parts[1]);
        // remove optional surrounding quotes
        if ((strlen($val) >= 2) && ($val[0] === '"' && substr($val, -1) === '"' || $val[0] === "'" && substr($val, -1) === "'")) {
            $val = substr($val, 1, -1);
        }
        // set env and _ENV for current process
        putenv("$key=$val");
        $_ENV[$key] = $val;
    }
}

// cargar ./php/.env relativo al archivo
$envPath = __DIR__ . DIRECTORY_SEPARATOR . '.env';
load_dotenv($envPath);

// Valores por defecto si no están en las variables de entorno
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'tienda_db';
$dbUser = getenv('DB_USER') ?: 'tienda_user';
$dbPass = getenv('DB_PASS') ?: 'TU_PASSWORD_SEGURO';
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Si se ejecuta desde CLI, imprimir verificación mínima
    if (php_sapi_name() === 'cli') {
        echo "PDO OK\n";
    }
} catch (PDOException $e) {
    // Registrar el error siempre en logs
    error_log('DB connection error: ' . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        // En CLI mostramos el mensaje de error y salimos con código 1
        fwrite(STDERR, 'Error de conexión a la base de datos: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    } else {
        // En entorno web no exponemos detalles
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
        exit;
    }
}

?>