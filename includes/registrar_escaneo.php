<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empacador') {
    echo "❌ Solo el rol 'empacador' puede usar esta función.";
    exit();
}

$qr = $_POST['qr_codigo'] ?? '';
$qr = trim($qr);

if ($qr === '') {
    echo "❌ Código QR no recibido.";
    exit();
}

// Buscar el lote asociado al QR
$stmt = $conn->prepare("SELECT id_lote, cantidad_total, estado FROM lotes WHERE qr_codigo = ?");
$stmt->bind_param("s", $qr);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "❌ Lote no encontrado con ese código QR.";
    exit();
}

$lote = $res->fetch_assoc();
$id_lote = $lote['id_lote'];
$cantidad = $lote['cantidad_total'];
$estado_actual = $lote['estado'];
$id_usuario = $_SESSION['id_usuario'] ?? null;

// Registrar el escaneo como traslado (1 unidad)
if ($cantidad < 1) {
    echo "❌ Este lote ya no tiene unidades disponibles.";
    exit();
}

// Reducir en 1 la cantidad y registrar el movimiento
$conn->begin_transaction();
try {
    $conn->query("UPDATE lotes SET cantidad_total = cantidad_total - 1 WHERE id_lote = $id_lote");

    $destino = 'almacen'; // destino fijo o podrías recibirlo como POST
    $stmt_mov = $conn->prepare("INSERT INTO movimientos (id_lote, origen, destino, cantidad, fecha_movimiento, id_responsable, estado_origen, estado_destino) VALUES (?, ?, ?, 1, NOW(), ?, ?, ?)");
    $stmt_mov->bind_param("ississ", $id_lote, $estado_actual, $destino, $id_usuario, $estado_actual, $destino);
    $stmt_mov->execute();

    $conn->commit();
    echo "✅ 1 unidad trasladada con éxito.";
} catch (Exception $e) {
    $conn->rollback();
    echo "❌ Error al procesar traslado: " . $e->getMessage();
}
?>
