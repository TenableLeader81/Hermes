<?php
session_start();
require_once "../config/database.php";

$token = trim($_GET['token'] ?? '');

if(empty($token)){
    header("Location: /login.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE desbloqueo_token = :token AND login_bloqueado_hasta > NOW()");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user){
    $conn->prepare("UPDATE usuarios SET login_intentos=0, login_bloqueado_hasta=NULL, desbloqueo_token=NULL WHERE id=:id")
         ->execute([':id' => $user['id']]);
    $mensaje = "✅ Tu cuenta ha sido desbloqueada. Ya puedes iniciar sesión.";
    $tipo = "exito";
} else {
    $mensaje = "❌ El enlace no es válido o ya expiró.";
    $tipo = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Desbloquear cuenta - HERMES</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="logo-container">
    <div class="logo-box"><span>📍</span></div>
    <h1>HERMES</h1>
    <p>Sistema de Seguridad — UTEQ</p>
</div>
<div class="card" style="text-align:center;">
    <p style="font-size:16px; margin-bottom:20px;">
        <?php echo htmlspecialchars($mensaje); ?>
    </p>
    <a href="/login.php" style="color:#4f46e5; font-weight:600;">Ir al inicio de sesión →</a>
</div>
</body>
</html>
