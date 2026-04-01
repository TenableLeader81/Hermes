<?php
/**
 * api/chat.php — Agente Gemini directo desde PHP (sin servidor Python)
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'no_auth']);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

$body    = json_decode(file_get_contents('php://input'), true);
$mensaje = trim($body['mensaje'] ?? '');

if ($mensaje === '') {
    echo json_encode(['error' => 'Mensaje vacío']);
    exit;
}

$apiKey = getenv('GEMINI_API_KEY') ?: '';
if ($apiKey === '') {
    echo json_encode(['error' => 'API key de Gemini no configurada.']);
    exit;
}

// Leer reportes recientes de la DB para contexto
require_once __DIR__ . "/../../config/database.php";

$reportes = [];
try {
    $stmt = $conn->query("
        SELECT r.categoria, r.subcategoria, r.descripcion, r.estado,
               r.fecha_hora, u.nombre as usuario
        FROM reportes r
        LEFT JOIN usuarios u ON r.usuario_id = u.id
        ORDER BY r.fecha_hora DESC
        LIMIT 30
    ");
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$resumen = "";
foreach ($reportes as $r) {
    $desc = $r['descripcion'] ? " - {$r['descripcion']}" : "";
    $user = $r['usuario'] ?? 'Dispositivo IoT';
    $resumen .= "- [{$r['fecha_hora']}] {$r['categoria']} / {$r['subcategoria']}{$desc} (estado: {$r['estado']}, reportado por: {$user})\n";
}
if ($resumen === '') $resumen = "No hay reportes registrados aún.";

$systemInstruction = "Eres el asistente de seguridad HERMES del campus UTEQ (Universidad Tecnológica de Querétaro). " .
    "Tu función es ayudar a los alumnos y personal con información sobre incidentes en el campus, " .
    "consejos de seguridad y orientación sobre cómo reportar emergencias. " .
    "Responde siempre en español, de forma concisa y amable. " .
    "Aquí están los últimos reportes del campus:\n\n" . $resumen;

$payload = json_encode([
    'system_instruction' => ['parts' => [['text' => $systemInstruction]]],
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => $mensaje]]]
    ],
    'generationConfig' => [
        'temperature'     => 0.7,
        'maxOutputTokens' => 512,
    ],
], JSON_UNESCAPED_UNICODE);

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-04-17:generateContent?key={$apiKey}";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
]);

$resp    = curl_exec($ch);
$code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['error' => 'Error de conexión con Gemini.']);
    exit;
}

$data = json_decode($resp, true);

if ($code !== 200) {
    $msg = $data['error']['message'] ?? "HTTP $code";
    echo json_encode(['error' => "Error Gemini: $msg"]);
    exit;
}

$texto = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
echo json_encode(['respuesta' => $texto], JSON_UNESCAPED_UNICODE);
