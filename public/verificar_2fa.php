<?php
session_start();
require_once "../config/database.php";
require_once "../libs/GoogleAuthenticator.php";

if(!isset($_SESSION['temp_user_2fa'])){
    header("Location: login.php");
    exit;
}

$error = '';

if(isset($_POST['codigo'])){
    $codigo = trim($_POST['codigo']);

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['temp_user_2fa']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $ga = new PHPGangsta_GoogleAuthenticator();
    $checkResult = $ga->verifyCode($user['twofa_secret'], $codigo, 2);

    if($checkResult){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nombre']  = $user['nombre'];
        $_SESSION['rol']     = $_SESSION['temp_user_2fa_rol'] ?? $user['rol'];

        unset($_SESSION['temp_user_2fa']);
        unset($_SESSION['temp_user_2fa_rol']);

        if($_SESSION['rol'] === 'admin'){
            header("Location: admin/dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    } else {
        $error = "Código incorrecto. Intenta de nuevo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA - HERMES</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="logo-container">
    <div class="logo-box">
        <span>📍</span>
    </div>
    <h1>HERMES</h1>
    <p>Sistema de Seguridad — UTEQ</p>
</div>

<div class="card">
    <h2>Verificación en Dos Pasos</h2>
    <p style="color:#6b7280;font-size:14px;margin-bottom:20px;">
        Ingresa el código de 6 dígitos de tu aplicación Google Authenticator.
    </p>

    <?php if($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label>Código de verificación</label>
            <input type="text" name="codigo" placeholder="000000" maxlength="6"
                   inputmode="numeric" autocomplete="one-time-code" required autofocus>
        </div>
        <button type="submit">Verificar</button>
    </form>

    <div class="links">
        <a href="login.php">← Volver al login</a>
    </div>
</div>

<div class="footer">
    © 2026 HERMES - Todos los derechos reservados
</div>

</body>
</html>
