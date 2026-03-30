<?php
session_start();
require_once "../config/database.php";
require_once "../libs/Mailer.php";

/* ── Protección de acceso ── */
if(!isset($_SESSION['user_id'])){
    header("Location: ../public/login.php");
    exit;
}

if($_SESSION['rol'] === 'admin'){
    header("Location: ../public/admin/dashboard.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: ../public/crear_reporte.php");
    exit;
}

/* ── Validar datos ── */
$categoria   = trim($_POST['categoria']   ?? '');
$subcategoria = trim($_POST['subcategoria'] ?? '');
$descripcion  = trim($_POST['descripcion']  ?? '');
$latitud     = $_POST['latitud']  ?? '';
$longitud    = $_POST['longitud'] ?? '';

$categoriasValidas = ['Robo', 'Accidente', 'Falla electrica'];

if(!in_array($categoria, $categoriasValidas)){
    $_SESSION['reporte_error'] = "Selecciona un tipo de incidente válido.";
    header("Location: ../public/crear_reporte.php");
    exit;
}

if(empty($subcategoria)){
    $_SESSION['reporte_error'] = "Selecciona una subcategoría.";
    header("Location: ../public/crear_reporte.php");
    exit;
}

if(!is_numeric($latitud) || !is_numeric($longitud)){
    $_SESSION['reporte_error'] = "No se pudo obtener tu ubicación. Permite el GPS e intenta de nuevo.";
    header("Location: ../public/crear_reporte.php");
    exit;
}

/* ── Determinar visibilidad ──
   Robo y Accidente → publica (alerta inmediata a todos)
   Falla electrica  → interna (solo Dirección, hasta que la resuelvan)
*/
$visibilidad = ($categoria === 'Falla electrica') ? 'interna' : 'publica';

/* ── Insertar reporte ── */
try {
    $stmt = $conn->prepare("
        INSERT INTO reportes (categoria, subcategoria, descripcion, latitud, longitud, visibilidad, estado, usuario_id)
        VALUES (:categoria, :subcategoria, :descripcion, :latitud, :longitud, :visibilidad, 'pendiente', :usuario_id)
    ");
    $stmt->execute([
        ':categoria'    => $categoria,
        ':subcategoria' => $subcategoria,
        ':descripcion'  => $descripcion,
        ':latitud'      => $latitud,
        ':longitud'     => $longitud,
        ':visibilidad'  => $visibilidad,
        ':usuario_id'   => $_SESSION['user_id'],
    ]);

    $reporteId = $conn->lastInsertId();

    /* ── Crear alerta si es reporte público ── */
    if($visibilidad === 'publica'){
        $stmtAlerta = $conn->prepare("
            INSERT INTO alertas (reporte_id, estado, fecha_expiracion)
            VALUES (:reporte_id, 'activa', DATE_ADD(NOW(), INTERVAL 2 HOUR))
        ");
        $stmtAlerta->execute([':reporte_id' => $reporteId]);

        /* ── Enviar correo a todos los usuarios registrados ── */
        $usuarios = $conn->query("SELECT nombre, correo FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
        $fecha    = date('d/m/Y H:i');
        $asunto   = "⚠️ Alerta en campus UTEQ: {$categoria} – {$subcategoria}";
        $html     = Mailer::plantillaAlerta($categoria, $subcategoria, $descripcion, $fecha);

        foreach ($usuarios as $u) {
            Mailer::enviar($u['correo'], $u['nombre'], $asunto, $html);
        }

        $_SESSION['reporte_success'] = "Reporte enviado. Se ha generado una alerta para todos los alumnos.";
    } else {
        $_SESSION['reporte_success'] = "Falla de servicio reportada. Dirección ha sido notificada y trabajará en resolverlo.";
    }

} catch(PDOException $e) {
    $_SESSION['reporte_error'] = "Error al guardar el reporte. Intenta de nuevo.";
}

header("Location: ../public/crear_reporte.php");
exit;
