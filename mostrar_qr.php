<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['nombre']) || !isset($_SESSION['cedula'])) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Obtener y sanitizar parámetro
$numero_lote = isset($_GET['lote']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['lote']) : '';
if (empty($numero_lote)) {
    http_response_code(400);
    exit('Número de lote requerido');
}

// Ruta estandarizada
$ruta_archivo = "/var/www/html/tlp/qrcodes/qr_{$numero_lote}.png";

// Verificaciones
if (!file_exists($ruta_archivo)) {
    http_response_code(404);
    exit('Archivo QR no encontrado');
}

$info_imagen = @getimagesize($ruta_archivo);
if ($info_imagen === false || $info_imagen['mime'] !== 'image/png') {
    http_response_code(400);
    exit('Archivo no válido');
}

// Mostrar imagen
header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
readfile($ruta_archivo);
exit();
?>