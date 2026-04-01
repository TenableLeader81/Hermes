<?php
$host     = getenv('MYSQL_HOST')     ?: 'localhost';
$port     = getenv('MYSQL_PORT')     ?: '3306';
$dbname   = getenv('MYSQL_DATABASE') ?: 'dbhermes';
$user     = getenv('MYSQL_USER')     ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
