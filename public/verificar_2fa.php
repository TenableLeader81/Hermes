<?php
session_start();
require_once "../config/database.php";
require_once "../libs/GoogleAuthenticator.php";

if(!isset($_SESSION['temp_user_2fa'])){
    header("Location: login.php");
    exit;
}

if(isset($_POST['codigo'])){

    $codigo = $_POST['codigo'];

    $stmt = $conn->prepare("
        SELECT * FROM usuarios WHERE id = :id
    ");
    $stmt->execute([':id' => $_SESSION['temp_user_2fa']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $ga = new PHPGangsta_GoogleAuthenticator();
    $checkResult = $ga->verifyCode(
        $user['twofa_secret'],
        $codigo,
        2
    );

    if($checkResult){

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $_SESSION['temp_user_2fa_rol'] ?? $user['rol'];

        unset($_SESSION['temp_user_2fa']);
        unset($_SESSION['temp_user_2fa_rol']);

        if($_SESSION['rol'] === 'admin'){
            header("Location: admin/dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;

    } else {
        $error = "Código incorrecto.";
    }
}
?>

<h2>Verificación en Dos Pasos</h2>

<?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>

<form method="POST">
    <input type="text" name="codigo" placeholder="Código de 6 dígitos" required>
    <button type="submit">Verificar</button>
</form>