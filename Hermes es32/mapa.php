<?php
$archivo = "ubicacion.txt";

if (file_exists($archivo)) {
    $data = file_get_contents($archivo);
    list($lat, $lon) = explode(",", $data);
} else {
    $lat = "20.5888";
    $lon = "-100.3899";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>HERMES - Ubicación</title>
</head>
<body style="text-align:center;font-family:Arial;background:#111;color:white">

<h1>🚨 HERMES TRACKING</h1>

<p>Lat: <?php echo $lat; ?> | Lon: <?php echo $lon; ?></p>

<iframe
    width="90%"
    height="500"
    style="border-radius:15px"
    src="https://maps.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lon; ?>&z=17&output=embed">
</iframe>

</body>
</html>