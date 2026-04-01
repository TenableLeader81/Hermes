<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado');
}

$categoria = $_GET['categoria'] ?? '';

$params = [];
$where  = '';
if ($categoria !== '') {
    $where = 'WHERE r.categoria = :categoria';
    $params[':categoria'] = $categoria;
}

$stmt = $conn->prepare("
    SELECT
        r.id,
        r.categoria,
        r.subcategoria,
        r.descripcion,
        r.estado,
        r.visibilidad,
        r.latitud,
        r.longitud,
        r.fecha_hora,
        CONCAT(u.nombre, ' ', COALESCE(u.apellido_paterno,''), ' ', COALESCE(u.apellido_materno,'')) AS usuario,
        u.matricula,
        u.correo
    FROM reportes r
    LEFT JOIN usuarios u ON u.id = r.usuario_id
    $where
    ORDER BY r.fecha_hora DESC
");
$stmt->execute($params);
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nombre = 'hermes_reportes';
if ($categoria !== '') {
    $nombre .= '_' . strtolower(str_replace(' ', '_', $categoria));
}
$nombre .= '_' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombre . '"');
header('Pragma: no-cache');

// BOM para que Excel abra correctamente con acentos
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

fputcsv($out, ['ID', 'Categoria', 'Subcategoria', 'Descripcion', 'Estado', 'Visibilidad', 'Latitud', 'Longitud', 'Fecha y Hora', 'Usuario', 'Matricula', 'Correo']);

foreach ($reportes as $r) {
    fputcsv($out, [
        $r['id'],
        $r['categoria'],
        $r['subcategoria'],
        $r['descripcion'],
        $r['estado'],
        $r['visibilidad'],
        $r['latitud'],
        $r['longitud'],
        $r['fecha_hora'],
        trim($r['usuario']),
        $r['matricula'],
        $r['correo'],
    ]);
}

fclose($out);
