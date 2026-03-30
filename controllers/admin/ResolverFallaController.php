<?php
session_start();
require_once "../../config/database.php";

header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin'){
    echo json_encode(['ok' => false, 'msg' => 'Sin autorización']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

$reporteId = (int)($_POST['reporte_id'] ?? 0);

if($reporteId <= 0){
    echo json_encode(['ok' => false, 'msg' => 'ID de reporte inválido']);
    exit;
}

try {
    // Verificar que el reporte existe, es una falla interna y está pendiente/en_proceso
    $stmt = $conn->prepare("
        SELECT id, estado, visibilidad FROM reportes
        WHERE id = :id AND visibilidad = 'interna'
    ");
    $stmt->execute([':id' => $reporteId]);
    $reporte = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$reporte){
        echo json_encode(['ok' => false, 'msg' => 'Reporte no encontrado o no es una falla de servicio']);
        exit;
    }

    if($reporte['estado'] === 'resuelto'){
        echo json_encode(['ok' => false, 'msg' => 'Este reporte ya fue marcado como resuelto']);
        exit;
    }

    // 1. Marcar el reporte como resuelto y hacerlo público
    $conn->prepare("
        UPDATE reportes SET estado = 'resuelto', visibilidad = 'publica'
        WHERE id = :id
    ")->execute([':id' => $reporteId]);

    // 2. Crear alerta pública con estado 'resuelta' para notificar a alumnos
    $conn->prepare("
        INSERT INTO alertas (reporte_id, estado, fecha_expiracion)
        VALUES (:reporte_id, 'resuelta', DATE_ADD(NOW(), INTERVAL 24 HOUR))
    ")->execute([':reporte_id' => $reporteId]);

    echo json_encode(['ok' => true, 'msg' => 'Falla marcada como resuelta. Los alumnos serán notificados.']);

} catch(PDOException $e){
    echo json_encode(['ok' => false, 'msg' => 'Error en base de datos']);
}
