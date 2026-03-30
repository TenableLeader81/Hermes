<?php
session_start();
require_once "../config/database.php";
require_once "../libs/GoogleAuthenticator.php";

// Determinar el user_id según el flujo
$user_id = $_SESSION['setup_2fa_user_id'] ?? $_SESSION['user_id'] ?? null;

if(!$user_id || !isset($_POST['codigo'])){
    header("Location: login.php");
    exit;
}

$codigo = trim($_POST['codigo']);

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    header("Location: login.php");
    exit;
}

$ga = new PHPGangsta_GoogleAuthenticator();
$checkResult = $ga->verifyCode($user['twofa_secret'], $codigo, 2);

if($checkResult){

    // Activar 2FA en la BD
    $stmt = $conn->prepare("UPDATE usuarios SET twofa_enabled = 1 WHERE id = :id");
    $stmt->execute([':id' => $user_id]);

    // Limpiar secret pendiente
    unset($_SESSION['pending_2fa_secret']);

    // Si venía del flujo de registro/configuración forzada, iniciar sesión completa
    if(isset($_SESSION['setup_2fa_user_id'])){
        unset($_SESSION['setup_2fa_user_id']);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
    }

    // Limpiar sesión temporal de login si existía
    unset($_SESSION['temp_user_2fa']);
    unset($_SESSION['temp_user_2fa_rol']);

    if($_SESSION['rol'] === 'admin'){
        header("Location: admin/dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;

} else {
    $_SESSION['error_2fa'] = "Código incorrecto. Intenta de nuevo.";
    header("Location: activar_2fa.php");
    exit;
}
