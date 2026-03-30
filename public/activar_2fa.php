<?php
session_start();
require_once "../config/database.php";
require_once "../libs/GoogleAuthenticator.php";

// Aceptar usuario ya logueado (gestión de 2FA) o en flujo de configuración obligatoria
$user_id = $_SESSION['setup_2fa_user_id'] ?? $_SESSION['user_id'] ?? null;

if(!$user_id){
    header("Location: login.php");
    exit;
}

$ga = new PHPGangsta_GoogleAuthenticator();

// Generar secret solo si no hay uno pendiente en sesión
if(!isset($_SESSION['pending_2fa_secret'])){
    $secret = $ga->createSecret();
    $_SESSION['pending_2fa_secret'] = $secret;

    $stmt = $conn->prepare("UPDATE usuarios SET twofa_secret = :secret WHERE id = :id");
    $stmt->execute([':secret' => $secret, ':id' => $user_id]);
}

$secret = $_SESSION['pending_2fa_secret'];
$qrCodeUrl = $ga->getQRCodeGoogleUrl("HERMES", $secret);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Activar 2FA - HERMES</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="logo-container">
    <div class="logo-box"><span>🛡</span></div>
    <h1>HERMES</h1>
</div>

<div class="card">
    <h2>Autenticación en Dos Pasos</h2>
    <p>Para continuar, escanea el código QR con <strong>Google Authenticator</strong> e ingresa el código de 6 dígitos.</p>

    <div style="text-align:center; margin: 1rem 0;">
        <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR 2FA" style="width:180px;">
    </div>

    <?php if(isset($_SESSION['error_2fa'])): ?>
        <div class="error">
            <?php echo $_SESSION['error_2fa']; unset($_SESSION['error_2fa']); ?>
        </div>
    <?php endif; ?>

    <form action="confirmar_2fa.php" method="POST">
        <div class="input-group">
            <label>Código de verificación</label>
            <input type="text" name="codigo" placeholder="123456" maxlength="6" required autofocus>
        </div>
        <button type="submit">Confirmar y continuar</button>
    </form>
</div>

</body>
</html>
