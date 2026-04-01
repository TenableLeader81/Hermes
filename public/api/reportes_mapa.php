<?php
session_start();
require_once "../../config/database.php";

header('Content-Type: application/json; charset=UTF-8');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['error' => 'no_auth']);
    exit;
}

// Solo reportes con alerta activa en las últimas 2 horas (mismo criterio que alertas.php)
$stmt = $conn->prepare("
    SELECT DISTINCT
        r.id,
        r.categoria,
        r.subcategoria,
        r.descripcion,
        r.latitud,
        r.longitud,
        r.fecha_hora,
        r.estado
    FROM reportes r
    INNER JOIN alertas a ON a.reporte_id = r.id
    WHERE r.visibilidad = 'publica'
      AND a.estado IN ('activa', 'resuelta')
      AND a.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
    ORDER BY r.fecha_hora DESC
");
$stmt->execute();
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['reportes' => $reportes], JSON_UNESCAPED_UNICODE);
