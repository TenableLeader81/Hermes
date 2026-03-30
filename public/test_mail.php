<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado. Inicia sesión primero.");
}

require_once "../libs/Mailer.php";
require_once "../config/database.php";

$resultado = Mailer::enviar(
    MAIL_USER,          // se manda a sí mismo como prueba
    'Prueba HERMES',
    '✅ Prueba de conexión SMTP – HERMES',
    Mailer::plantillaAlerta('Robo', 'Hurto', 'Este es un correo de prueba.', date('d/m/Y H:i'))
);

if ($resultado) {
    echo "<h2 style='color:green;font-family:sans-serif'>✅ Correo enviado correctamente a " . MAIL_USER . "</h2>";
    echo "<p style='font-family:sans-serif'>Revisa tu bandeja de entrada (o spam).</p>";
} else {
    echo "<h2 style='color:red;font-family:sans-serif'>❌ No se pudo conectar al servidor SMTP</h2>";
    echo "<ul style='font-family:sans-serif'>
        <li>Verifica que <strong>MAIL_PASS</strong> sea una <em>contraseña de aplicación</em> de Gmail (16 caracteres).</li>
        <li>Asegúrate de tener la <strong>verificación en 2 pasos</strong> activada en tu cuenta Google.</li>
        <li>Comprueba que XAMPP tenga acceso a internet (puerto 587).</li>
    </ul>";
}
?>
<p style='font-family:sans-serif'><a href='dashboard.php'>← Volver al dashboard</a></p>
