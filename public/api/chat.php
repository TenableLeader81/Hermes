<?php
/**
 * api/chat.php — Proxy hacia el servidor local del agente Gemini
 *
 * El agente corre localmente con: python server_gemini.py
 * que escucha en http://localhost:8001
 *
 * Recibe: POST con JSON { "mensaje": "texto del usuario" }
 * Responde: JSON { "respuesta": "texto del agente" } o { "error": "..." }
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'no_auth']);
    exit;
}

header('Content-Type: application/json');

$body    = json_decode(file_get_contents('php://input'), true);
$mensaje = trim($body['mensaje'] ?? '');

if ($mensaje === '') {
    echo json_encode(['error' => 'Mensaje vacío']);
    exit;
}

$payload = json_encode([
    'mensaje' => $mensaje,
    'user_id' => (string) $_SESSION['user_id'],
]);

$ch = curl_init('http://127.0.0.1:8001/chat');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
]);

$resultado = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
unset($ch);

if ($curlError) {
    echo json_encode(['error' => 'No se pudo conectar al agente. ¿Está corriendo server_gemini.py?']);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode(['error' => "Error del agente (HTTP $httpCode)"]);
    exit;
}

$data = json_decode($resultado, true);

if (isset($data['error'])) {
    echo json_encode(['error' => $data['error']]);
    exit;
}

echo json_encode(['respuesta' => $data['respuesta'] ?? '']);
