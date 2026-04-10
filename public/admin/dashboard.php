<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

if($_SESSION['rol'] !== 'admin'){
    header("Location: ../dashboard.php");
    exit;
}

require_once "../../config/database.php";

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    session_destroy();
    header("Location: ../login.php");
    exit;
}

// Cargar todos los reportes al inicio (PHP, para el render inicial)
$stmtRep = $conn->prepare("
    SELECT
        r.id, r.categoria, r.subcategoria, r.descripcion,
        r.latitud, r.longitud, r.visibilidad, r.estado, r.fecha_hora,
        COALESCE(u.nombre, 'Dispositivo IoT') AS alumno_nombre,
        COALESCE(u.matricula, 'SOS')          AS alumno_matricula
    FROM reportes r
    LEFT JOIN usuarios u ON u.id = r.usuario_id
    ORDER BY r.id DESC
    LIMIT 100
");
$stmtRep->execute();
$reportesIniciales = $stmtRep->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Dirección - HERMES</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        .rol-badge { display:inline-block; background:#e74c3c; color:#fff; font-size:0.7rem; padding:2px 8px; border-radius:12px; margin-top:4px; text-transform:uppercase; letter-spacing:1px; }
        .tabs { display:flex; gap:8px; margin-bottom:16px; }
        .tab-btn { padding:8px 18px; border:none; border-radius:8px; background:#e5e7eb; color:#374151; cursor:pointer; font-size:13px; font-weight:600; }
        .tab-btn.active { background:#4f46e5; color:#fff; }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; }
        .reporte-card { background:#fff; border-radius:10px; padding:14px 16px; margin-bottom:12px; border-left:4px solid #d1d5db; display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
        .reporte-card.robo      { border-color:#ef4444; background:#fef9f9; }
        .reporte-card.accidente { border-color:#f59e0b; background:#fffdf0; }
        .reporte-card.falla     { border-color:#3b82f6; background:#f0f6ff; }
        .reporte-card.resuelto  { border-color:#10b981; background:#f0fdf4; opacity:.8; }
        .reporte-info .titulo { font-weight:700; font-size:14px; }
        .reporte-info .sub    { font-size:13px; color:#374151; margin:2px 0; }
        .reporte-info .meta   { font-size:11px; color:#9ca3af; }
        .reporte-info .desc   { font-size:12px; color:#6b7280; font-style:italic; margin-top:3px; }
        .btn-resolver { flex-shrink:0; padding:7px 14px; background:#10b981; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600; white-space:nowrap; }
        .btn-resolver:disabled { background:#d1d5db; color:#9ca3af; cursor:default; }
        .badge-nuevo { display:inline-block; background:#ef4444; color:#fff; font-size:10px; padding:1px 6px; border-radius:10px; margin-left:6px; animation:pulse 1s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
        .badge-tab { display:inline-block; background:#ef4444; color:#fff; border-radius:50%; font-size:10px; min-width:16px; height:16px; line-height:16px; text-align:center; padding:0 3px; margin-left:5px; vertical-align:middle; }
        .sin-reportes { color:#9ca3af; font-style:italic; font-size:13px; padding:20px 0; text-align:center; }
        #mapa-admin { height:520px; border-radius:12px; }
        .main { overflow-y:auto; }
        .toast { position:fixed; bottom:30px; right:30px; background:#1f2937; color:#fff; padding:14px 20px; border-radius:12px; font-size:14px; z-index:9999; max-width:320px; box-shadow:0 4px 20px rgba(0,0,0,.3); animation:slideIn .3s ease; }
        .toast.falla     { border-left:4px solid #3b82f6; }
        .toast.robo      { border-left:4px solid #ef4444; }
        .toast.accidente { border-left:4px solid #f59e0b; }
        @keyframes slideIn { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
        @media (max-width: 768px) {
            #sidebarToggle { display:inline-flex !important; }
            .sidebar { position:fixed;top:0;left:-260px;height:100vh;z-index:1000;transition:left .3s ease;box-shadow:4px 0 20px rgba(0,0,0,.15); }
            .sidebar.open { left:0; }
            .sidebar-overlay { display:block !important; }
        }
    </style>
</head>
<body>
<div class="layout">

    <!-- Overlay móvil -->
    <div class="sidebar-overlay" onclick="cerrarSidebar()"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:999;"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon">📍</div>
            <div><h2>HERMES</h2><span>Panel de Dirección</span></div>
        </div>
        <nav>
            <a href="dashboard.php" class="active">🏠 Panel</a>
        </nav>
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($user['nombre'], 0, 2)); ?></div>
            <div class="user-name"><?php echo htmlspecialchars($user['nombre']); ?></div>
            <span class="rol-badge">Dirección</span>
        </div>
        <div class="logout">
            <a href="../../controllers/AuthController.php?logout=true">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">

        <div class="header" style="display:flex;align-items:center;gap:12px;">
            <button id="sidebarToggle" onclick="abrirSidebar()"
                    style="display:none;background:#6366f1;color:#fff;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;font-size:18px;flex-shrink:0;">☰</button>
            <h1>Panel de Dirección</h1>
        </div>

        <!-- TABS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="cambiarTab('fallas')">
                🔧 Fallas de Servicio
                <span class="badge-tab" id="badgeFallas" style="display:none"></span>
            </button>
            <button class="tab-btn" onclick="cambiarTab('incidentes')">
                🚨 Incidentes (Robo/Accidente)
                <span class="badge-tab" id="badgeIncidentes" style="display:none"></span>
            </button>
            <button class="tab-btn" onclick="cambiarTab('mapa')">
                🗺 Mapa
            </button>
            <button class="tab-btn" onclick="cambiarTab('exportar')">
                📥 Exportar CSV
            </button>
        </div>

        <!-- TAB: FALLAS -->
        <div class="tab-panel active" id="tab-fallas">
            <div id="listaFallas">
                <p class="sin-reportes">Cargando…</p>
            </div>
        </div>

        <!-- TAB: INCIDENTES -->
        <div class="tab-panel" id="tab-incidentes">
            <div id="listaIncidentes">
                <p class="sin-reportes">Cargando…</p>
            </div>
        </div>

        <!-- TAB: MAPA -->
        <div class="tab-panel" id="tab-mapa">
            <div id="mapa-admin"></div>
        </div>

        <!-- TAB: EXPORTAR CSV -->
        <div class="tab-panel" id="tab-exportar">
            <div style="background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                <h3 style="margin:0 0 16px;font-size:15px;color:#1f2937;">📥 Exportar Reportes</h3>
                <p style="font-size:13px;color:#6b7280;margin-bottom:20px;">Descarga todos los reportes registrados en formato CSV para análisis externo.</p>

                <div style="display:flex;flex-wrap:wrap;gap:12px;">
                    <a href="../api/exportar_csv.php" download
                       style="display:inline-flex;align-items:center;gap:8px;background:#6366f1;color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">
                        ⬇ Descargar todos los reportes
                    </a>
                    <a href="../api/exportar_csv.php?categoria=Robo" download
                       style="display:inline-flex;align-items:center;gap:8px;background:#ef4444;color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">
                        🚨 Solo Robos
                    </a>
                    <a href="../api/exportar_csv.php?categoria=Accidente" download
                       style="display:inline-flex;align-items:center;gap:8px;background:#f59e0b;color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">
                        🚑 Solo Accidentes
                    </a>
                    <a href="../api/exportar_csv.php?categoria=Falla+electrica" download
                       style="display:inline-flex;align-items:center;gap:8px;background:#3b82f6;color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">
                        ⚡ Solo Fallas
                    </a>
                    <a href="../api/exportar_csv.php?categoria=SOS" download
                       style="display:inline-flex;align-items:center;gap:8px;background:#dc2626;color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">
                        🆘 Solo SOS
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* ═══════════════════
   TABS
═══════════════════ */
let mapaIniciado = false;

function cambiarTab(nombre) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelector(`[onclick="cambiarTab('${nombre}')"]`).classList.add('active');
    document.getElementById('tab-' + nombre).classList.add('active');

    if(nombre === 'mapa' && !mapaIniciado){
        iniciarMapa();
        mapaIniciado = true;
    }
}

/* ═══════════════════
   DATOS INICIALES (PHP → JS)
═══════════════════ */
const reportesIniciales = <?php echo json_encode($reportesIniciales); ?>;

let ultimoReporteId = reportesIniciales.length > 0
    ? Math.max(...reportesIniciales.map(r => r.id))
    : 0;

let nuevasFallas     = 0;
let nuevosIncidentes = 0;

function renderInicial() {
    const fallas     = reportesIniciales.filter(r => r.visibilidad === 'interna');
    const incidentes = reportesIniciales.filter(r => r.visibilidad === 'publica');

    const listaF = document.getElementById('listaFallas');
    const listaI = document.getElementById('listaIncidentes');

    listaF.innerHTML = fallas.length
        ? fallas.map(r => crearCard(r, false)).join('')
        : '<p class="sin-reportes">Sin fallas pendientes.</p>';

    listaI.innerHTML = incidentes.length
        ? incidentes.map(r => crearCard(r, false)).join('')
        : '<p class="sin-reportes">Sin incidentes recientes.</p>';
}

/* ═══════════════════
   TARJETA DE REPORTE
═══════════════════ */
function crearCard(r, esNuevo) {
    const claseColor = r.visibilidad === 'interna' ? 'falla'
                     : r.categoria === 'Robo'      ? 'robo' : 'accidente';
    const estaResuelto = r.estado === 'resuelto';
    const claseCard = estaResuelto ? 'resuelto' : claseColor;

    const icono = r.visibilidad === 'interna' ? '⚡'
                : r.categoria === 'Robo'      ? '🚨' : '🚑';

    const badgeNuevo = esNuevo ? '<span class="badge-nuevo">NUEVO</span>' : '';
    const estadoBadge = estaResuelto
        ? '<span style="color:#10b981;font-size:12px;font-weight:600">✅ Resuelto</span>'
        : '';

    const btnResolver = (r.visibilidad === 'interna' && !estaResuelto)
        ? `<button class="btn-resolver" onclick="resolverFalla(${r.id}, this)">✔ Marcar resuelto</button>`
        : '';

    return `
    <div class="reporte-card ${claseCard}" id="reporte-${r.id}">
        <div class="reporte-info">
            <div class="titulo">${icono} ${r.categoria} — ${r.subcategoria} ${badgeNuevo} ${estadoBadge}</div>
            ${r.descripcion ? `<div class="desc">"${r.descripcion}"</div>` : ''}
            <div class="meta">👤 ${r.alumno_nombre} (${r.alumno_matricula || 'N/A'}) · 🕐 ${formatHora(r.fecha_hora)}</div>
        </div>
        ${btnResolver}
    </div>`;
}

/* ═══════════════════
   RESOLVER FALLA
═══════════════════ */
function resolverFalla(reporteId, btn) {
    btn.disabled = true;
    btn.textContent = 'Guardando…';

    const form = new FormData();
    form.append('reporte_id', reporteId);

    fetch('../../controllers/admin/ResolverFallaController.php', {
        method: 'POST',
        body: form
    })
    .then(r => r.json())
    .then(data => {
        if(data.ok){
            const card = document.getElementById('reporte-' + reporteId);
            card.classList.remove('falla');
            card.classList.add('resuelto');
            btn.remove();

            const titulo = card.querySelector('.titulo');
            titulo.innerHTML += ' <span style="color:#10b981;font-size:12px;font-weight:600">✅ Resuelto</span>';

            mostrarToast('✅ Falla resuelta. Alumnos notificados.', 'falla');
        } else {
            btn.disabled = false;
            btn.textContent = '✔ Marcar resuelto';
            mostrarToast('❌ ' + data.msg, 'robo');
        }
    });
}

/* ═══════════════════
   POLLING (10s)
═══════════════════ */
function polling() {
    fetch('../api/admin_reportes.php?desde_id=' + ultimoReporteId)
        .then(r => r.json())
        .then(data => {
            if(data.error || data.reportes.length === 0) return;

            ultimoReporteId = Math.max(...data.reportes.map(r => r.id));

            data.reportes.forEach(r => {
                const esInterna = r.visibilidad === 'interna';
                const lista = document.getElementById(esInterna ? 'listaFallas' : 'listaIncidentes');

                // Quitar "sin reportes" si existía
                const sinRep = lista.querySelector('.sin-reportes');
                if(sinRep) sinRep.remove();

                lista.insertAdjacentHTML('afterbegin', crearCard(r, true));

                // Actualizar badge del tab
                if(esInterna){
                    nuevasFallas++;
                    actualizarBadge('badgeFallas', nuevasFallas);
                } else {
                    nuevosIncidentes++;
                    actualizarBadge('badgeIncidentes', nuevosIncidentes);
                }

                const tipoToast = r.categoria === 'SOS' ? 'robo'
                    : r.visibilidad === 'interna' ? 'falla'
                    : (r.categoria === 'Robo' ? 'robo' : 'accidente');

                const msgToast = r.categoria === 'SOS'
                    ? '🆘 EMERGENCIA SOS — Botón de pánico activado'
                    : (r.visibilidad === 'interna' ? '⚡ Nueva falla: ' : '🚨 Nuevo incidente: ') + r.subcategoria;

                mostrarToast(msgToast, tipoToast);

                // Agregar marcador al mapa si está iniciado
                if (mapaIniciado) {
                    agregarMarcadorMapa(r);
                }
            });
        });
}

function actualizarBadge(id, n) {
    const el = document.getElementById(id);
    el.style.display = 'inline-block';
    el.textContent = n;
}

// Limpiar badge al ver el tab
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.getAttribute('onclick').match(/'(\w+)'/)[1];
        if(tab === 'fallas'){
            nuevasFallas = 0;
            document.getElementById('badgeFallas').style.display = 'none';
        } else if(tab === 'incidentes'){
            nuevosIncidentes = 0;
            document.getElementById('badgeIncidentes').style.display = 'none';
        }
    });
});

/* ═══════════════════
   MAPA
═══════════════════ */
let mapa;

function iniciarMapa() {
    mapa = L.map('mapa-admin', { minZoom: 14, maxZoom: 19 }).setView([20.65636, -100.40507], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(mapa);

    const iconos = {
        'Robo':           L.divIcon({ className:'', html:'<div style="font-size:24px;filter:drop-shadow(0 0 4px #ef4444)">🚨</div>' }),
        'Accidente':      L.divIcon({ className:'', html:'<div style="font-size:24px;filter:drop-shadow(0 0 4px #f59e0b)">🚑</div>' }),
        'Falla electrica':L.divIcon({ className:'', html:'<div style="font-size:24px;filter:drop-shadow(0 0 4px #3b82f6)">⚡</div>' }),
        'SOS':            L.divIcon({ className:'', html:'<div style="font-size:32px;filter:drop-shadow(0 0 8px #dc2626)">🆘</div>' }),
    };

    // Solo reportes de las últimas 2 horas
    const hace2h = Date.now() - 2 * 60 * 60 * 1000;
    const recientes = reportesIniciales.filter(r => {
        return new Date(r.fecha_hora.replace(' ', 'T')).getTime() >= hace2h;
    });

    // Jitter para coordenadas duplicadas
    const coordCount = {};
    recientes.forEach(r => {
        const k = r.latitud + ',' + r.longitud;
        coordCount[k] = (coordCount[k] || 0) + 1;
    });
    const coordIdx = {};

    recientes.forEach(r => {
        const k = r.latitud + ',' + r.longitud;
        coordIdx[k] = (coordIdx[k] || 0) + 1;
        const offset = coordCount[k] > 1 ? (coordIdx[k] - 1) * 0.00003 : 0;

        const latlng = [parseFloat(r.latitud) + offset, parseFloat(r.longitud) + offset];
        const icono  = iconos[r.categoria] || iconos['Robo'];

        L.marker(latlng, { icon: icono })
            .addTo(mapa)
            .bindPopup(
                `<strong>${r.categoria}</strong><br>` +
                `${r.subcategoria}<br>` +
                (r.descripcion ? `<em>${r.descripcion}</em><br>` : '') +
                `👤 ${r.alumno_nombre}<br>` +
                `<small>${formatHora(r.fecha_hora)}</small>`
            );
    });
}

/* ═══════════════════
   TOAST
═══════════════════ */
function mostrarToast(msg, tipo) {
    const t = document.createElement('div');
    t.className = 'toast ' + tipo;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 5000);
}

function formatHora(fechaStr) {
    const fecha = new Date(fechaStr.replace(' ', 'T') + 'Z');
    const ahora = new Date();
    const diff  = Math.floor((ahora - fecha) / 1000);
    if(diff < 60)    return 'Hace ' + diff + ' seg';
    if(diff < 3600)  return 'Hace ' + Math.floor(diff/60) + ' min';
    if(diff < 86400) return 'Hace ' + Math.floor(diff/3600) + ' h';
    return fecha.toLocaleDateString('es-MX');
}

function abrirSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.querySelector('.sidebar-overlay').style.display = 'block';
}
function cerrarSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.querySelector('.sidebar-overlay').style.display = 'none';
}

/* ── Inicio ── */
renderInicial();
setInterval(polling, 10000);
</script>

</body>
</html>
