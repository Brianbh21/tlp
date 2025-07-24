<?php
require_once __DIR__ . '/vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Opciones del código QR
$options = new QROptions([
    'version'      => 5,
    'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel'     => QRCode::ECC_L, // Nivel de corrección de errores bajo
]);

// Generar el código QR
$qr = new QRCode($options);
header('Content-Type: image/png');
echo $qr->render('¡Hola, Brian! Este es tu primer QR.');
