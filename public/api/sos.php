<?php
/**
 * api/sos.php — Endpoint del botón de pánico (ESP32)
 *
 * El ESP32 llama: GET /HERMES/public/api/sos.php?lat=X&lon=Y
 * No requiere sesión (viene desde hardware).
 * Responde "OK" o "ERROR: motivo"
 */

require_once "../../config/database.php";
require_once "../../libs/Mailer.php";

header('Content-Type: text/plain; charset=UTF-8');

/* ── Validar coordenadas ── */
$lat = $_GET['lat'] ?? '';
$lon = $_GET['lon'] ?? '';

if (!is_numeric($lat) || !is_numeric($lon)) {
    echo "ERROR: coordenadas invalidas";
    exit;
}

$lat = (float) $lat;
$lon = (float) $lon;

$mapsUrl = "https://maps.google.com/?q={$lat},{$lon}";
$fecha   = date('d/m/Y H:i');

try {
    /* ── 1. Guardar reporte SOS ── */
    $stmt = $conn->prepare("
        INSERT INTO reportes
            (categoria, subcategoria, descripcion, latitud, longitud, visibilidad, estado, usuario_id)
        VALUES
            ('SOS', 'Botón de pánico', 'Persona en peligro — solicita ayuda comunitaria',
             :lat, :lon, 'publica', 'pendiente', NULL)
    ");
    $stmt->execute([':lat' => $lat, ':lon' => $lon]);
    $reporteId = $conn->lastInsertId();

    /* ── 2. Crear alerta (activa 4 horas) ── */
    $stmtA = $conn->prepare("
        INSERT INTO alertas (reporte_id, estado, fecha_expiracion)
        VALUES (:reporte_id, 'activa', DATE_ADD(NOW(), INTERVAL 2 HOUR))
    ");
    $stmtA->execute([':reporte_id' => $reporteId]);

    /* ── 3. Notificar a todos los usuarios registrados ── */
    $usuarios = $conn->query("SELECT nombre, correo, rol FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
    $asunto   = "🆘 EMERGENCIA EN CAMPUS UTEQ – Se necesita ayuda";
    $html     = Mailer::plantillaSOS($lat, $lon, $mapsUrl, $fecha);

    foreach ($usuarios as $u) {
        try {
            Mailer::enviar($u['correo'], $u['nombre'], $asunto, $html);
        } catch (Exception $e) {
            error_log("[SOS] Error al enviar a {$u['correo']} ({$u['rol']}): " . $e->getMessage());
        }
    }

    echo "OK";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
