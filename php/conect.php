<?php
$dbHost = '10.12.220.169';
$dbName = 'Tienda';
$dbUser = 'Kika';
$dbPass = 'Kika esunperro1214-';
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo 'Conectado correctamente a la base de datos';
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Error de conexión: ' . htmlspecialchars($e->getMessage());
}