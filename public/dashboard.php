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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - HERMES</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        #mapa { height: 400px; border-radius: 12px; z-index: 0; }
        .alerts-section { overflow-y: auto; max-height: 460px; }
        .alert-card { padding: 12px 15px; border-radius: 10px; margin-bottom: 12px; border-left: 4px solid transparent; }
        .alert-card .cat  { font-weight: 700; font-size: 14px; }
        .alert-card .sub  { font-size: 13px; color: #374151; margin: 2px 0; }
        .alert-card .hora { font-size: 11px; color: #9ca3af; }
        .alert-card.robo      { background:#fee2e2; border-color:#ef4444; }
        .alert-card.accidente { background:#fef3c7; border-color:#f59e0b; }
        .alert-card.resuelta  { background:#d1fae5; border-color:#10b981; }
        .alert-card.sos       { background:#fef2f2; border-color:#dc2626; border-left-width:6px; }
        .badge-sos { display:inline-block; background:#dc2626; color:#fff; font-size:11px; font-weight:700; padding:2px 8px; border-radius:10px; margin-left:6px; letter-spacing:.5px; animation:pulse 0.8s infinite; }
        .badge-nuevo { display:inline-block; background:#ef4444; color:#fff; font-size:10px; padding:1px 6px; border-radius:10px; margin-left:6px; vertical-align:middle; animation:pulse 1s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
        #contadorAlertas { display:none; background:#ef4444; color:#fff; border-radius:50%; font-size:11px; width:18px; height:18px; line-height:18px; text-align:center; margin-left:6px; vertical-align:middle; }
        .sin-alertas { color:#9ca3af; font-size:13px; font-style:italic; text-align:center; padding:20px 0; }
        .toast { position:fixed; bottom:30px; right:30px; background:#1f2937; color:#fff; padding:14px 20px; border-radius:12px; font-size:14px; z-index:9999; max-width:320px; box-shadow:0 4px 20px rgba(0,0,0,.3); animation:slideIn .3s ease; }
        .toast.robo      { border-left:4px solid #ef4444; }
        .toast.accidente { border-left:4px solid #f59e0b; }
        .toast.resuelta  { border-left:4px solid #10b981; }
        @keyframes slideIn { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
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
            <a href="dashboard.php" class="active">🏠 Inicio</a>
            <a href="crear_reporte.php">➕ Crear Reporte</a>
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

    <!-- MAIN -->
    <div class="main">

        <div class="header">
            <h1>Bienvenido, <?php echo htmlspecialchars($user['nombre']); ?></h1>
            <span class="status">🟢 Sistema Activo</span>
        </div>

        <div class="content">

            <!-- MAPA -->
            <div class="map-section">
                <h3>Mapa del Campus</h3>
                <div id="mapa"></div>
            </div>

            <!-- ALERTAS -->
            <div class="alerts-section">
                <h3>
                    Alertas Recientes
                    <span id="contadorAlertas"></span>
                </h3>
                <div id="listaAlertas">
                    <p class="sin-alertas">Cargando alertas…</p>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* ═══════════════════════════════════════
   MAPA LEAFLET
═══════════════════════════════════════ */
const mapa = L.map('mapa', { minZoom: 14, maxZoom: 19 }).setView([20.65636, -100.40507], 16); // UTEQ

L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors © <a href="https://carto.com/">CARTO</a>',
    subdomains: 'abcd'
}).addTo(mapa);

// Iconos por categoría
const iconos = {
    'Robo':           L.divIcon({ className:'', html:'<div style="font-size:24px;filter:drop-shadow(0 0 4px #ef4444)">🚨</div>' }),
    'Accidente':      L.divIcon({ className:'', html:'<div style="font-size:24px;filter:drop-shadow(0 0 4px #f59e0b)">🚑</div>' }),
    'Falla electrica':L.divIcon({ className:'', html:'<div style="font-size:24px;filter:drop-shadow(0 0 4px #3b82f6)">⚡</div>' }),
    'SOS':            L.divIcon({ className:'', html:'<div style="font-size:32px;filter:drop-shadow(0 0 8px #dc2626);animation:pulse 0.8s infinite">🆘</div>' }),
};

// Radio y color por categoría (en metros)
const estiloRadio = {
    'Robo':           { color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.12, radius: 120 },
    'Accidente':      { color: '#f59e0b', fillColor: '#f59e0b', fillOpacity: 0.12, radius: 100 },
    'Falla electrica':{ color: '#3b82f6', fillColor: '#3b82f6', fillOpacity: 0.10, radius:  80 },
    'SOS':            { color: '#dc2626', fillColor: '#dc2626', fillOpacity: 0.25, radius: 200 },
};

let capas = {}; // guarda { marker, circulo } por reporte id

function cargarMarcadores() {
    fetch('api/reportes_mapa.php')
        .then(r => r.json())
        .then(data => {
            if(data.error) return;

            // Limpiar capas anteriores
            Object.values(capas).forEach(({ marker, circulo }) => {
                mapa.removeLayer(marker);
                mapa.removeLayer(circulo);
            });
            capas = {};

            data.reportes.forEach(r => {
                const latlng = [r.latitud, r.longitud];
                const estilo = estiloRadio[r.categoria] || estiloRadio['Robo'];
                const icono  = iconos[r.categoria]      || iconos['Robo'];

                // Círculo de radio
                const circulo = L.circle(latlng, {
                    ...estilo,
                    weight: 2,
                }).addTo(mapa);

                // Marcador con popup
                const marker = L.marker(latlng, { icon: icono })
                    .addTo(mapa)
                    .bindPopup(
                        `<strong>${r.categoria}</strong><br>` +
                        `${r.subcategoria}<br>` +
                        (r.descripcion ? `<em>${r.descripcion}</em><br>` : '') +
                        `<small>${formatHora(r.fecha_hora)}</small>`
                    );

                capas[r.id] = { marker, circulo };
            });
        });
}

/* ═══════════════════════════════════════
   ALERTAS + POLLING
═══════════════════════════════════════ */
let ultimoAlertaId = 0;
let totalNuevas = 0;

function cargarAlertas(inicial = false) {
    fetch('api/alertas.php?desde_id=' + ultimoAlertaId)
        .then(r => r.json())
        .then(data => {
            if(data.error) return;

            const alertas = data.alertas;
            if(alertas.length > 0){
                ultimoAlertaId = Math.max(...alertas.map(a => a.alerta_id));
            }
            const lista = document.getElementById('listaAlertas');

            if(inicial){
                // Primera carga: mostrar las últimas 20 alertas existentes
                lista.innerHTML = '';
                if(alertas.length === 0){
                    lista.innerHTML = '<p class="sin-alertas">Sin alertas recientes.</p>';
                    return;
                }
                // El API devuelve ORDER BY id DESC, invertir para mostrar más reciente arriba
                alertas.forEach(a => lista.appendChild(crearCard(a, false)));
            } else {
                // Polling: solo nuevas
                if(alertas.length === 0) return;

                alertas.forEach(a => {
                    // Insertar al inicio
                    const card = crearCard(a, true);
                    lista.prepend(card);
                    mostrarToast(a);
                });

                // Quitar "sin alertas" si existía
                const sinAlertas = lista.querySelector('.sin-alertas');
                if(sinAlertas) sinAlertas.remove();

                totalNuevas += alertas.length;
                const contador = document.getElementById('contadorAlertas');
                contador.style.display = 'inline-block';
                contador.textContent = totalNuevas;
            }
        });
}

function crearCard(a, esNueva) {
    const div = document.createElement('div');
    const clase = a.alerta_estado === 'resuelta' ? 'resuelta'
                : a.categoria === 'SOS'    ? 'sos'
                : a.categoria === 'Robo'   ? 'robo' : 'accidente';

    div.className = 'alert-card ' + clase;

    let titulo = a.categoria;
    if(a.categoria === 'SOS')           titulo = '🆘 EMERGENCIA — Botón de pánico';
    if(a.alerta_estado === 'resuelta')  titulo = '✅ Resuelto: ' + a.subcategoria;

    const badgeHtml = a.categoria === 'SOS'
        ? '<span class="badge-sos">EMERGENCIA</span>'
        : (esNueva ? '<span class="badge-nuevo">NUEVO</span>' : '');

    div.innerHTML =
        `<div class="cat">${titulo}${badgeHtml}</div>` +
        `<div class="sub">${a.subcategoria}</div>` +
        (a.descripcion ? `<div class="sub"><em>${a.descripcion}</em></div>` : '') +
        `<div class="hora">${formatHora(a.fecha_creacion)}</div>`;

    return div;
}

function mostrarToast(a) {
    const clase = a.alerta_estado === 'resuelta' ? 'resuelta'
                : a.categoria === 'SOS'  ? 'sos'
                : a.categoria === 'Robo' ? 'robo' : 'accidente';
    const icono = clase === 'resuelta' ? '✅'
                : clase === 'sos'      ? '🆘'
                : clase === 'robo'     ? '🚨' : '🚑';

    const toast = document.createElement('div');
    toast.className = 'toast ' + clase;
    const duracion = a.categoria === 'SOS' ? 12000 : 5000;
    toast.innerHTML = a.categoria === 'SOS'
        ? `<strong>🆘 EMERGENCIA EN CAMPUS</strong><br>Alguien necesita ayuda — revisa tu correo`
        : `<strong>${icono} ${a.categoria}</strong><br>${a.subcategoria}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), duracion);
}

function formatHora(fechaStr) {
    const fecha = new Date(fechaStr.replace(' ', 'T'));
    const ahora = new Date();
    const diff  = Math.floor((ahora - fecha) / 1000);
    if(diff < 60)   return 'Hace ' + diff + ' seg';
    if(diff < 3600) return 'Hace ' + Math.floor(diff/60) + ' min';
    if(diff < 86400)return 'Hace ' + Math.floor(diff/3600) + ' h';
    return fecha.toLocaleDateString('es-MX');
}

/* ── Inicio ── */
cargarAlertas(true);
cargarMarcadores();

// Polling cada 10 segundos
setInterval(() => {
    cargarAlertas(false);
    cargarMarcadores();
}, 10000);
</script>

</body>
</html>
