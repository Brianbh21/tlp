<?php
session_start();

// Verificación de sesión (igual que en mostrar_qr.php)
if (!isset($_SESSION['nombre']) || !isset($_SESSION['cedula'])) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Obtener y sanitizar parámetro (igual que en mostrar_qr.php)
$numero_lote = isset($_GET['lote']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['lote']) : '';
if (empty($numero_lote)) {
    http_response_code(400);
    exit('Número de lote requerido');
}

// Ruta estandarizada (igual que en mostrar_qr.php)
$ruta_archivo = "/var/www/html/tlp/qrcodes/qr_{$numero_lote}.png";

// Verificaciones (igual que en mostrar_qr.php)
if (!file_exists($ruta_archivo)) {
    http_response_code(404);
    exit('Archivo QR no encontrado');
}

$info_imagen = @getimagesize($ruta_archivo);
if ($info_imagen === false || $info_imagen['mime'] !== 'image/png') {
    http_response_code(400);
    exit('Archivo no válido');
}

// Configurar descarga (esto es lo único diferente)
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="QR_Lote_' . $numero_lote . '.png"');
header('Content-Length: ' . filesize($ruta_archivo));
header('Cache-Control: no-cache, must-revalidate');
readfile($ruta_archivo);
exit();
?>