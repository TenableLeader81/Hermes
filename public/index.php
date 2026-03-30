<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>HERMES | Seguridad Campus</title>

<style>

/* RESET */

*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{
font-family:Arial, Helvetica, sans-serif;
background:#f5f7fb;
color:#333;
}

/* NAVBAR */

.navbar{
width:100%;
background:white;
border-bottom:1px solid #e6e6e6;
}

.nav-container{
width:100%;
max-width:1200px;
margin:auto;
display:flex;
justify-content:space-between;
align-items:center;
padding:18px 25px;
}

.logo{
font-size:22px;
font-weight:bold;
color:#3b6ef5;
}

.menu{
display:flex;
gap:25px;
align-items:center;
}

.menu a{
text-decoration:none;
color:#444;
font-weight:500;
}

.menu a:hover{
color:#3b6ef5;
}

.login-btn{
background:#3b6ef5;
color:white;
padding:8px 18px;
border-radius:6px;
}

.login-btn:hover{
background:#2f5bd1;
}

/* HERO */

.hero{
max-width:1200px;
margin:auto;
padding:60px 25px;
display:flex;
gap:40px;
align-items:center;
}

.hero-text{
flex:1;
}

.hero-text h1{
font-size:36px;
margin-bottom:15px;
}

.hero-text p{
line-height:1.6;
color:#555;
margin-bottom:15px;
}

.btn{
display:inline-block;
margin-top:10px;
background:#3b6ef5;
color:white;
padding:12px 22px;
border-radius:6px;
text-decoration:none;
}

/* MAPA */

.map-container{
flex:1;
}

.map-card{
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

.map{
width:100%;
aspect-ratio:1/1;
position:relative;
border-radius:8px;

background-image:
linear-gradient(#e5e9f2 1px, transparent 1px),
linear-gradient(90deg,#e5e9f2 1px, transparent 1px);

background-size:40px 40px;
}

/* EDIFICIOS */

.building{
position:absolute;
background:#e8efff;
padding:6px 10px;
border-radius:6px;
font-size:12px;
}

/* ALERTAS */

.alert{
width:16px;
height:16px;
border-radius:50%;
position:absolute;
animation:pulse 2s infinite;
}

.robo{
background:#ff4d4d;
}

.accidente{
background:#ffa500;
}

.falla{
background:#3b6ef5;
}

@keyframes pulse{

0%{
transform:scale(1);
opacity:1;
}

50%{
transform:scale(1.6);
opacity:.6;
}

100%{
transform:scale(1);
opacity:1;
}

}

/* FOOTER */

footer{
text-align:center;
padding:30px;
color:#777;
}

/* RESPONSIVE */

@media(max-width:900px){

.hero{
flex-direction:column;
}

.hero-text h1{
font-size:28px;
}

}

@media(max-width:600px){

.menu{
gap:15px;
}

.hero{
padding:40px 20px;
}

}

</style>

</head>

<body>

<!-- NAVBAR -->

<div class="navbar">

<div class="nav-container">

<div class="logo">
HERMES
</div>

<div class="menu">

<a href="index.php">Inicio</a>
<a href="nosotros.php">Sobre Nosotros</a>
<a href="login.php" class="login-btn">Iniciar sesión</a>

</div>

</div>

</div>

<!-- HERO -->

<section class="hero">

<div class="hero-text">

<h1>Sistema Inteligente de Seguridad Universitaria</h1>

<p>
HERMES es una plataforma digital diseñada para mejorar la seguridad dentro del campus universitario mediante reportes estructurados, visualización de alertas y dispositivos IoT de emergencia.
</p>

<p>
La plataforma permite a estudiantes y personal del campus reportar incidentes y recibir notificaciones en tiempo real para mejorar la prevención y el tiempo de respuesta ante situaciones de riesgo.
</p>

<a class="btn" href="login.php">
Acceder al sistema
</a>

</div>


<!-- MAPA -->

<div class="map-container">

<div class="map-card">

<h3>Mapa de Alertas del Campus</h3>

<br>

<div class="map">

<div class="building" style="top:20%;left:20%;">
Biblioteca
</div>

<div class="building" style="top:60%;left:25%;">
Cafetería
</div>

<div class="building" style="top:45%;left:55%;">
Edificio Central
</div>

<div class="building" style="top:25%;left:70%;">
Parking
</div>


<div class="alert robo" style="top:40%;left:35%;"></div>

<div class="alert accidente" style="top:70%;left:65%;"></div>

<div class="alert falla" style="top:50%;left:55%;"></div>

</div>

</div>

</div>

</section>

<footer>

Sistema HERMES © 2026

</footer>

</body>

</html>