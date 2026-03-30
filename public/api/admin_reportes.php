<?php
session_start();
require_once "../../config/database.php";

header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin'){
    echo json_encode(['error' => 'no_auth']);
    exit;
}

// ?desde_id=X → reportes con id > X
$desdeId = isset($_GET['desde_id']) ? (int)$_GET['desde_id'] : 0;

$stmt = $conn->prepare("
    SELECT
        r.id,
        r.categoria,
        r.subcategoria,
        r.descripcion,
        r.latitud,
        r.longitud,
        r.visibilidad,
        r.estado,
        r.fecha_hora,
        u.nombre    AS alumno_nombre,
        u.matricula AS alumno_matricula
    FROM reportes r
    JOIN usuarios u ON u.id = r.usuario_id
    WHERE r.id > :desde_id
    ORDER BY r.id DESC
    LIMIT 50
");
$stmt->execute([':desde_id' => $desdeId]);
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['reportes' => $reportes]);
