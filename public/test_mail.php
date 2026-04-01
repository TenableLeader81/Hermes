<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    die("Solo admins.");
}
require_once __DIR__ . "/../config/mail.php";

// Enviar correo de prueba via Brevo API
$payload = json_encode([
    'sender'      => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM],
    'to'          => [['email' => MAIL_FROM, 'name' => 'Admin Test']],
    'subject'     => 'Prueba HERMES Brevo',
    'htmlContent' => '<h1>Funciona!</h1><p>Brevo esta configurado correctamente.</p>',
]);

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'api-key: ' . BREVO_API_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 15,
]);

$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

echo "<pre>";
echo "HTTP Code: $code\n";
echo "cURL error: " . ($err ?: 'ninguno') . "\n";
echo "API Key usada: " . substr(BREVO_API_KEY, 0, 20) . "...\n";
echo "Respuesta:\n$resp\n";
echo "</pre>";
?>
