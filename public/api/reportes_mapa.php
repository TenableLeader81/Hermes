<?php
session_start();
require_once "../../config/database.php";

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['error' => 'no_auth']);
    exit;
}

// Reportes públicos de las últimas 24 horas para mostrar en el mapa
$stmt = $conn->prepare("
    SELECT
        r.id,
        r.categoria,
        r.subcategoria,
        r.descripcion,
        r.latitud,
        r.longitud,
        r.fecha_hora,
        r.estado
    FROM reportes r
    WHERE r.visibilidad = 'publica'
      AND r.fecha_hora >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY r.fecha_hora DESC
");
$stmt->execute();
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['reportes' => $reportes]);
