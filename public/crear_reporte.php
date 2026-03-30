<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

if($_SESSION['rol'] === 'admin'){
    header("Location: admin/dashboard.php");
    exit;
}

require_once "../config/database.php";

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    session_destroy();
    header("Location: login.php");
    exit;
}

$success = $_SESSION['reporte_success'] ?? null;
$error   = $_SESSION['reporte_error']   ?? null;
unset($_SESSION['reporte_success'], $_SESSION['reporte_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Reporte - HERMES</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .incident-card.selected {
            background: #e0e7ff;
            border: 2px solid #4f46e5;
            transform: translateY(-5px);
        }
        .subcategoria-group { display: none; margin: 16px 0; }
        .subcategoria-group.visible { display: block; }
        .subcategoria-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #374151;
        }
        .subcategoria-group select,
        .subcategoria-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: #f9fafb;
        }
        .subcategoria-group textarea { resize: vertical; min-height: 80px; }
        .location-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .location-box.error {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #991b1b;
        }
        .location-box.loading {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #1e40af;
        }
        .submit-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .alert-success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #065f46;
        }
        .alert-error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #991b1b;
        }
        .falla-notice {
            display: none;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            color: #92400e;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>

<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">🛡</div>
            <div>
                <h2>HERMES</h2>
                <span>Seguridad Campus</span>
            </div>
        </div>

        <nav>
            <a href="dashboard.php">🏠 Inicio</a>
            <a href="crear_reporte.php" class="active">➕ Crear Reporte</a>
        </nav>

        <div class="user-profile">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['nombre'], 0, 2)); ?>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($user['nombre']); ?></div>
            <div class="user-matricula"><?php echo htmlspecialchars($user['matricula']); ?></div>

            <?php if($user['twofa_enabled'] == 1): ?>
                <button class="btn-2fa active-2fa">🔒 2FA Activado</button>
            <?php else: ?>
                <a href="activar_2fa.php"><button class="btn-2fa">🔓 Activar 2FA</button></a>
            <?php endif; ?>
        </div>

        <div class="logout">
            <a href="../controllers/AuthController.php?logout=true">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <!-- CONTENIDO -->
    <div class="main">

        <div class="header">
            <h1>Crear Nuevo Reporte</h1>
        </div>

        <div class="report-container">

            <?php if($success): ?>
                <div class="alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="../controllers/ReporteController.php" id="formReporte">

                <input type="hidden" name="categoria"    id="inputCategoria">
                <input type="hidden" name="subcategoria" id="inputSubcategoria">
                <input type="hidden" name="latitud"    id="inputLatitud">
                <input type="hidden" name="longitud"   id="inputLongitud">

                <h3>Tipo de Incidente</h3>

                <div class="incident-grid">

                    <div class="incident-card" id="card-Robo" onclick="selectType('Robo')">
                        🚨
                        <h4>Robo</h4>
                        <p>Hurto o asalto</p>
                    </div>

                    <div class="incident-card" id="card-Accidente" onclick="selectType('Accidente')">
                        🚑
                        <h4>Accidente</h4>
                        <p>Lesión o emergencia</p>
                    </div>

                    <div class="incident-card" id="card-Falla electrica" onclick="selectType('Falla electrica')">
                        ⚡
                        <h4>Falla de Servicio</h4>
                        <p>Agua, luz, internet…</p>
                    </div>

                </div>

                <!-- Subcategoría Robo -->
                <div class="subcategoria-group" id="sub-Robo">
                    <label>¿Qué ocurrió?</label>
                    <select id="sel-Robo" onchange="setSubcategoria(this.value)">
                        <option value="">-- Selecciona --</option>
                        <option value="Hurto">Hurto (sin violencia)</option>
                        <option value="Asalto">Asalto (con violencia)</option>
                        <option value="Robo de vehículo">Robo de vehículo</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <!-- Subcategoría Accidente -->
                <div class="subcategoria-group" id="sub-Accidente">
                    <label>¿Qué ocurrió?</label>
                    <select id="sel-Accidente" onchange="setSubcategoria(this.value)">
                        <option value="">-- Selecciona --</option>
                        <option value="Accidente vial">Accidente vial</option>
                        <option value="Lesión">Lesión / caída</option>
                        <option value="Emergencia médica">Emergencia médica</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <!-- Subcategoría Falla -->
                <div class="subcategoria-group" id="sub-Falla electrica">
                    <label>¿Qué servicio falla?</label>
                    <select id="sel-Falla electrica" onchange="setSubcategoria(this.value)">
                        <option value="">-- Selecciona --</option>
                        <option value="Sin agua">Sin agua</option>
                        <option value="Sin luz">Sin luz / electricidad</option>
                        <option value="Sin internet">Sin internet / Wi-Fi</option>
                        <option value="Sanitarios">Sanitarios dañados</option>
                        <option value="Otro">Otro</option>
                    </select>
                    <div class="falla-notice" id="fallaNotice" style="display:block; margin-top:10px;">
                        ⚠️ Las fallas de servicio solo se notifican a Dirección. Cuando sean resueltas, recibirás una notificación.
                    </div>
                </div>

                <!-- Descripción (siempre visible tras seleccionar) -->
                <div class="subcategoria-group" id="sub-descripcion">
                    <label>Descripción (opcional)</label>
                    <textarea name="descripcion" placeholder="Describe brevemente lo que ocurrió…"></textarea>
                </div>

                <!-- Ubicación -->
                <div class="location-box loading" id="locationBox">
                    🔄 Obteniendo ubicación GPS…
                </div>

                <button type="submit" class="submit-btn" id="btnEnviar" disabled>
                    Enviar Reporte
                </button>

            </form>

        </div>
    </div>
</div>

<script>
let categoriaSeleccionada = null;
let gpsListo = false;

/* ── Selección de tipo ── */
function selectType(tipo) {
    document.querySelectorAll('.incident-card').forEach(c => c.classList.remove('selected'));
    document.querySelectorAll('.subcategoria-group').forEach(g => g.classList.remove('visible'));

    document.getElementById('card-' + tipo).classList.add('selected');
    document.getElementById('sub-' + tipo).classList.add('visible');
    document.getElementById('sub-descripcion').classList.add('visible');

    document.getElementById('inputCategoria').value = tipo;
    categoriaSeleccionada = tipo;

    // Resetear subcategoria al cambiar tipo
    document.getElementById('sel-' + tipo).value = '';
    document.getElementById('inputSubcategoria').value = '';

    actualizarBoton();
}

function setSubcategoria(valor) {
    document.getElementById('inputSubcategoria').value = valor;
    actualizarBoton();
}

/* ── Geolocalización ── */
function iniciarGeo() {
    if(!navigator.geolocation){
        setLocationError('Tu navegador no soporta geolocalización.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            document.getElementById('inputLatitud').value  = pos.coords.latitude;
            document.getElementById('inputLongitud').value = pos.coords.longitude;
            gpsListo = true;

            const box = document.getElementById('locationBox');
            box.className = 'location-box';
            box.textContent = '📍 Ubicación obtenida: ' +
                pos.coords.latitude.toFixed(6) + ', ' +
                pos.coords.longitude.toFixed(6);

            actualizarBoton();
        },
        function(err) {
            setLocationError('No se pudo obtener ubicación. Permite el acceso al GPS.');
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

function setLocationError(msg) {
    const box = document.getElementById('locationBox');
    box.className = 'location-box error';
    box.textContent = '⚠️ ' + msg;
}

function actualizarBoton() {
    const btn = document.getElementById('btnEnviar');
    const subcategoria = document.getElementById('inputSubcategoria').value;
    btn.disabled = !(categoriaSeleccionada && gpsListo && subcategoria);
}

iniciarGeo();
</script>

</body>
</html>
