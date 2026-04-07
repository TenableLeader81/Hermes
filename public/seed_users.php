<?php
// ARCHIVO TEMPORAL — ELIMINAR DESPUÉS DE EJECUTAR
require_once __DIR__ . "/../config/database.php";

$usuarios = [
    [
        'nombre'           => 'Alumno',
        'apellido_paterno' => 'Demo',
        'apellido_materno' => 'Test',
        'correo'           => 'alumno.demo@uteq.edu.mx',
        'matricula'        => 'alumno.demo',
        'password'         => 'Test1234',
        'rol'              => 'alumno',
    ],
    [
        'nombre'           => 'Admin',
        'apellido_paterno' => 'Demo',
        'apellido_materno' => 'Test',
        'correo'           => 'admin.demo@uteq.edu.mx',
        'matricula'        => 'admin.demo',
        'password'         => 'Test1234',
        'rol'              => 'admin',
    ],
];

// Borrar si ya existen para recrearlos limpios
$conn->exec("DELETE FROM usuarios WHERE correo IN ('alumno.demo@uteq.edu.mx','admin.demo@uteq.edu.mx')");

foreach ($usuarios as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    $stmt = $conn->prepare("
        INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, correo, matricula, password_hash, rol, twofa_enabled)
        VALUES (:nombre, :ap, :am, :correo, :matricula, :hash, :rol, 0)
    ");
    $stmt->execute([
        ':nombre'    => $u['nombre'],
        ':ap'        => $u['apellido_paterno'],
        ':am'        => $u['apellido_materno'],
        ':correo'    => $u['correo'],
        ':matricula' => $u['matricula'],
        ':hash'      => $hash,
        ':rol'       => $u['rol'],
    ]);
}

echo "<h2 style='font-family:sans-serif;color:green'>✅ Usuarios de prueba recreados</h2>";
echo "<table style='font-family:sans-serif;border-collapse:collapse'>";
echo "<tr><th style='padding:8px;border:1px solid #ccc'>Rol</th><th style='padding:8px;border:1px solid #ccc'>Correo</th><th style='padding:8px;border:1px solid #ccc'>Contraseña</th></tr>";
echo "<tr><td style='padding:8px;border:1px solid #ccc'>Alumno</td><td style='padding:8px;border:1px solid #ccc'>alumno.demo@uteq.edu.mx</td><td style='padding:8px;border:1px solid #ccc'>Test1234</td></tr>";
echo "<tr><td style='padding:8px;border:1px solid #ccc'>Admin</td><td style='padding:8px;border:1px solid #ccc'>admin.demo@uteq.edu.mx</td><td style='padding:8px;border:1px solid #ccc'>Test1234</td></tr>";
echo "</table>";
echo "<p style='font-family:sans-serif;color:#dc2626'><strong>Elimina este archivo después de usarlo.</strong></p>";
?>
