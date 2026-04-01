<?php
session_start();
require_once "../../config/database.php";

header('Content-Type: application/json; charset=UTF-8');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['error' => 'no_auth']);
    exit;
}

$desdeId = isset($_GET['desde_id']) ? (int)$_GET['desde_id'] : 0;

$stmt = $conn->prepare("
    SELECT
        a.id            AS alerta_id,
        a.estado        AS alerta_estado,
        a.fecha_creacion,
        r.categoria,
        r.subcategoria,
        r.descripcion,
        r.latitud,
        r.longitud
    FROM alertas a
    JOIN reportes r ON r.id = a.reporte_id
    WHERE r.visibilidad = 'publica'
      AND a.estado IN ('activa', 'resuelta')
      AND a.id > :desde_id
      AND a.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
    ORDER BY a.id DESC
    LIMIT 20
");

$stmt->execute([':desde_id' => $desdeId]);
$alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['alertas' => $alertas], JSON_UNESCAPED_UNICODE);
