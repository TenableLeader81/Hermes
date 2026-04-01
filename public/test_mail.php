<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    die("Solo admins.");
}
require_once __DIR__ . "/../config/mail.php";

echo "<pre>";

// Test 1: conexión TCP a smtp.gmail.com:587
echo "Probando conexión TCP a smtp.gmail.com:587...\n";
$sock = @stream_socket_client("tcp://smtp.gmail.com:587", $errno, $errstr, 10);
if (!$sock) {
    echo "FALLO puerto 587: $errno - $errstr\n";
    echo "\nProbando puerto 465 (SSL)...\n";
    $sock2 = @stream_socket_client("ssl://smtp.gmail.com:465", $errno2, $errstr2, 10);
    if (!$sock2) {
        echo "FALLO puerto 465: $errno2 - $errstr2\n";
        echo "\nRAILWAY BLOQUEA SMTP. Necesitamos otra solucion.\n";
    } else {
        echo "Puerto 465 funciona!\n";
        fclose($sock2);
    }
    die("</pre>");
}

echo "Conexion TCP exitosa!\n";
$resp = fgets($sock, 515);
echo "Servidor: $resp";

fwrite($sock, "EHLO localhost\r\n");
while ($line = fgets($sock, 515)) {
    echo $line;
    if (isset($line[3]) && $line[3] === ' ') break;
}

fwrite($sock, "STARTTLS\r\n");
echo fgets($sock, 515);

if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
    echo "FALLO TLS\n";
    fclose($sock);
    die("</pre>");
}
echo "TLS OK\n";

fwrite($sock, "EHLO localhost\r\n");
while ($line = fgets($sock, 515)) {
    echo $line;
    if (isset($line[3]) && $line[3] === ' ') break;
}

fwrite($sock, "AUTH LOGIN\r\n");
echo fgets($sock, 515);
fwrite($sock, base64_encode(MAIL_USER) . "\r\n");
echo fgets($sock, 515);
fwrite($sock, base64_encode(MAIL_PASS) . "\r\n");
$auth = fgets($sock, 515);
echo "Auth: $auth";

if (substr(trim($auth), 0, 3) === '235') {
    echo "AUTENTICACION EXITOSA. SMTP funciona correctamente.\n";
} else {
    echo "FALLO AUTENTICACION.\n";
}

fwrite($sock, "QUIT\r\n");
fclose($sock);
echo "</pre>";
?>
