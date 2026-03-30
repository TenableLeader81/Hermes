<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if(isset($_GET['lat']) && isset($_GET['lon'])){

    $lat = $_GET['lat'];
    $lon = $_GET['lon'];

    // Guardar ubicación
    file_put_contents("ubicacion.txt", $lat . "," . $lon);

    $mail = new PHPMailer(true);

    try {

        // CONFIGURACIÓN SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '2023371016@uteq.edu.mx'; // <-- CAMBIA ESTO
        $mail->Password   = 'pqfiuvoebhewximy'; // <-- SIN ESPACIOS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // 🔥 SOLUCIÓN ERROR SSL EN XAMPP
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Remitente y destino
        $mail->setFrom('2023371016@uteq.edu.mx', 'HERMES ALERTA');
        $mail->addAddress('2023371016@uteq.edu.mx'); // puedes cambiar el destino

        // Contenido
        $mail->isHTML(false);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '🚨 ALERTA HERMES - Botón Presionado';

        $mail->Body  = "Se presionó el botón de emergencia.\n\n";
        $mail->Body .= "Ubicación actual:\n";
        $mail->Body .= "Latitud: $lat\n";
        $mail->Body .= "Longitud: $lon\n\n";
        $mail->Body .= "Ver en Google Maps:\n";
        $mail->Body .= "https://maps.google.com/?q=$lat,$lon\n";

        $mail->send();

        echo "OK";

    } catch (Exception $e) {
        echo "ERROR: " . $mail->ErrorInfo;
    }

} else {
    echo "ERROR";
}

?>

