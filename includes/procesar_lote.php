<?php
// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../phpqrcode/qrlib.php';
require_once __DIR__ . '/db.php';

if ($conn->connect_error) {
    die("Error de conexión a BD: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Acceso inválido');
}

// Recoger datos del formulario
$fecha_empaque  = $_POST['fecha_empaque']  ?? '';
$fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
// Validar que la fecha de empaque no sea posterior a la de vencimiento
if (strtotime($fecha_empaque) > strtotime($fecha_vencimiento)) {
    echo "<script>alert('⚠️ Error: La fecha de empaque no puede ser posterior a la fecha de vencimiento.'); window.history.back();</script>";
    exit();
}
$numero_lote    = $_POST['numero_lote']    ?? '';
$cantidad_total = (int) ($_POST['cantidad_total'] ?? 0);
$planta_origen  = $_POST['planta_origen']  ?? '';
$tipo_producto  = $_POST['tipo_producto']  ?? '';
$id_empacador   = (int) ($_POST['id_empacador'] ?? 0);

// Obtener codigo_vitad y embalaje de la base de datos basado en el tipo_producto
$codigo_vitad = '';
$embalaje = '';

$sql_producto = "SELECT codigo_vitad, embalaje FROM productos WHERE nombre_producto = ?";
$stmt_producto = $conn->prepare($sql_producto);
$stmt_producto->bind_param('s', $tipo_producto);
$stmt_producto->execute();
$result_producto = $stmt_producto->get_result();

if ($row_producto = $result_producto->fetch_assoc()) {
    $codigo_vitad = $row_producto['codigo_vitad'];
    $embalaje = $row_producto['embalaje'];
} else {
    die("No se encontró información del producto en la base de datos");
}
$stmt_producto->close();

// Validación básica (eliminada la duplicada)
if (empty($fecha_empaque) || empty($fecha_vencimiento) || empty($numero_lote)
    || empty($cantidad_total) || empty($planta_origen) || empty($tipo_producto)
    || empty($id_empacador)) {
    die("Faltan datos requeridos");
}

// Generar código QR temporal único
$qr_codigo = 'TEMP_' . uniqid();

// Insertar lote con qr_codigo temporal
$sql = "INSERT INTO lotes 
    (fecha_empaque, fecha_vencimiento, numero_lote, cantidad_total, planta_origen, tipo_producto, id_empacador, qr_codigo, codigo_vitad, embalaje) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param('sssississs', 
    $fecha_empaque, 
    $fecha_vencimiento, 
    $numero_lote, 
    $cantidad_total, 
    $planta_origen, 
    $tipo_producto, 
    $id_empacador, 
    $qr_codigo,
    $codigo_vitad,
    $embalaje
);

if (!$stmt->execute()) {
    if ($conn->errno === 1062) {
        die("❗ Este código QR ya fue generado anteriormente.");
    } else {
        die("Error execute(): " . $stmt->error);
    }
}
$id_lote = $conn->insert_id;
$stmt->close();

// Preparar carpeta qrcodes
$dir = dirname(__DIR__) . '/qrcodes/';
if (!is_dir($dir)) mkdir($dir, 0777, true);
if (!is_writable($dir)) die('No hay permisos de escritura en la carpeta qrcodes/');

// Generar datos QR
$qr_data = sprintf(
    "Lote: %s\nProducto: %s\nFecha de Empaque: %s\nFecha de Vencimiento: %s\nCantidad: %d\nPlanta: %s\nEmpacador: %d\nCódigo VITAD: %s\nEmbalaje: %s",
    $numero_lote, $tipo_producto, $fecha_empaque, $fecha_vencimiento,
    $cantidad_total, $planta_origen, $id_empacador, $codigo_vitad, $embalaje
);

// Generar nombre final de archivo QR
$qr_filename = "qr_{$id_lote}.png";
$temp = $dir . "temp_{$id_lote}.png";
$final = $dir . $qr_filename;

// Generar QR básico
QRcode::png($qr_data, $temp, QR_ECLEVEL_H, 10, 2);

// Elegir logo dinámicamente
$producto_normalizado = strtolower($tipo_producto);
$logo_file = null;
$usar_logo = false;

if (strpos($producto_normalizado, 'yoko') !== false) {
    $logo_file = __DIR__ . '/yoko.jpeg';
} elseif (strpos($producto_normalizado, 'krumer') !== false) {
    $logo_file = __DIR__ . '/krumerlogo.png';
}

// Verificar si existe el logo
if ($logo_file && file_exists($logo_file)) {
    $usar_logo = true;
}

// Cargar imagen QR
$qr_img = imagecreatefrompng($temp);

if ($usar_logo) {
    // Cargar logo
    $logo_img = imagecreatefromstring(file_get_contents($logo_file));
    
    // Dimensiones
    $qr_w = imagesx($qr_img);
    $qr_h = imagesy($qr_img);
    $logo_w = imagesx($logo_img);
    $logo_h = imagesy($logo_img);
    
    // Redimensionar logo al 20%
    $target_w = (int)($qr_w * 0.2);
    $scale = $logo_w / $target_w;
    $target_h = (int)($logo_h / $scale);
    $dst_x = (int)(($qr_w - $target_w) / 2);
    $dst_y = (int)(($qr_h - $target_h) / 2);
    
    // Insertar logo en el centro del QR
    imagecopyresampled(
        $qr_img, $logo_img,
        $dst_x, $dst_y,
        0, 0,
        $target_w, $target_h,
        $logo_w, $logo_h
    );
    
    // Liberar memoria del logo
    imagedestroy($logo_img);
}

// Guardar QR final y limpiar
imagepng($qr_img, $final);
imagedestroy($qr_img);
unlink($temp);

// Actualizar lote con el nombre final del QR
$upd = $conn->prepare("UPDATE lotes SET qr_codigo = ? WHERE id_lote = ?");
$upd->bind_param('si', $qr_filename, $id_lote);
if (!$upd->execute()) {
    die("Error al actualizar código QR: " . $upd->error);
}
$upd->close();
$conn->close();

// Redirigir
header("Location: resultado_lote.php?id=" . urlencode($id_lote));
exit;
?>