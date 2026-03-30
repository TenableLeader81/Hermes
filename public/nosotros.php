<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Sobre Nosotros | HERMES</title>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

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
background:white;
padding:80px 20px;
text-align:center;
}

.hero h1{
font-size:38px;
margin-bottom:15px;
}

.hero p{
max-width:800px;
margin:auto;
line-height:1.6;
color:#555;
}

/* SECCIONES */

.section{
width:100%;
padding:80px 20px;
}

.container{
max-width:1200px;
margin:auto;
display:flex;
align-items:center;
gap:60px;
}

.text{
flex:1;
}

.text h2{
font-size:30px;
margin-bottom:15px;
}

.text p{
line-height:1.7;
color:#555;
}

.icon{
flex:1;
display:flex;
justify-content:center;
align-items:center;
font-size:120px;
color:#3b6ef5;
}

/* ALTERNAR SECCIONES */

.reverse .container{
flex-direction:row-reverse;
background:#eef3ff;
padding:40px;
border-radius:10px;
}

/* VALORES */

.valores{
padding:80px 20px;
background:white;
}

.valores h2{
text-align:center;
font-size:32px;
margin-bottom:50px;
}

.valores-grid{
max-width:1200px;
margin:auto;
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:25px;
}

.valor-card{
background:#f7f9ff;
padding:30px;
border-radius:10px;
text-align:center;
box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

.valor-card i{
font-size:40px;
color:#3b6ef5;
margin-bottom:10px;
}

.valor-card h3{
margin-bottom:10px;
}

/* FOOTER */

footer{
text-align:center;
padding:30px;
color:#777;
}

/* RESPONSIVE */

@media(max-width:900px){

.container{
flex-direction:column;
text-align:center;
}

.reverse .container{
flex-direction:column;
}

.icon{
font-size:80px;
}

.hero h1{
font-size:30px;
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

<h1>Sobre HERMES</h1>

<p>
HERMES es una plataforma digital diseñada para mejorar la seguridad dentro de los campus universitarios mediante reportes estructurados, geolocalización y dispositivos IoT de emergencia.
</p>

</section>

<!-- SOBRE HERMES -->

<section class="section">

<div class="container">

<div class="text">

<h2>¿Qué es HERMES?</h2>

<p>
HERMES es un sistema de alertas y reporte de incidentes enfocado en mejorar la seguridad dentro de los campus universitarios. Permite a los estudiantes registrar incidentes mediante opciones predefinidas y visualizar alertas cercanas a través de un mapa interactivo. Además, integra dispositivos IoT que permiten enviar alertas de emergencia con solo presionar un botón.
</p>

</div>

<div class="icon">
<i class="bi bi-map"></i>
</div>

</div>

</section>

<!-- MISION -->

<section class="section reverse">

<div class="container">

<div class="text">

<h2>Misión</h2>

<p>
Desarrollar soluciones tecnológicas seguras que contribuyan a la protección de la información y a la prevención de riesgos, promoviendo entornos confiables y el uso responsable de las tecnologías.</p>

</div>

<div class="icon">
<i class="bi bi-shield-check"></i>
</div>

</div>

</section>

<!-- VISION -->

<section class="section">

<div class="container">

<div class="text">

<h2>Visión</h2>

<p>
Ser, para el año 2028, un proyecto reconocido por su contribución a la seguridad tecnológica en instituciones educativas, destacando por la innovación, confiabilidad y mejora continua de sus soluciones.
</p>

</div>

<div class="icon">
<i class="bi bi-eye"></i>
</div>

</div>

</section>

<!-- VALORES -->

<section class="valores">

<h2>Valores</h2>

<div class="valores-grid">

<div class="valor-card">

<i class="bi bi-shield-lock"></i>

<h3>Confianza</h3>

<p>Proteger la información de los usuarios mediante un manejo adecuado de los datos y la generación de alertas verificadas.
</p>

</div>

<div class="valor-card">

<i class="bi bi-lightbulb"></i>

<h3>Innovación</h3>

<p>Aplicar nuevas tecnologías para mejorar la seguridad.</p>

</div>

<div class="valor-card">

<i class="bi bi-person-check"></i>

<h3>Responsabilidad</h3>

<p>Garantizar el uso adecuado de la información.</p>

</div>

<div class="valor-card">

<i class="bi bi-people"></i>

<h3>Compromiso</h3>

<p>Desarrollar un sistema funcional, seguro y accesible, enfocado en satisfacer las necesidades de los usuarios.
</p>

</div>

</div>

</section>

<footer>

Sistema HERMES © 2026

</footer>

</body>

</html>