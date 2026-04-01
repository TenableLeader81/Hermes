<?php
// Router para el servidor built-in de PHP
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Servir archivos estáticos (CSS, JS, imágenes) directamente
$filePath = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// Si no tiene extensión o es directorio, buscar index.php
if (is_dir($filePath)) {
    $filePath .= '/index.php';
}

// Incluir el archivo PHP correspondiente
if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
    chdir(dirname($filePath));
    include $filePath;
    return true;
}

// 404
http_response_code(404);
echo "Página no encontrada";
