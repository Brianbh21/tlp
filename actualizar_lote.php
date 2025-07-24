<?php
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_lote = $_POST['id_lote'];
    $numero_lote = $_POST['numero_lote'];
    $tipo_producto = $_POST['tipo_producto'];
    $fecha_empaque = $_POST['fecha_empaque'];
    $fecha_vencimiento = $_POST['fecha_vencimiento'];
    $cantidad_total = $_POST['cantidad_total'];

    $stmt = $conn->prepare("UPDATE lotes SET numero_lote = ?, tipo_producto = ?, fecha_empaque = ?, fecha_vencimiento = ?, cantidad_total = ? WHERE id_lote = ?");
    $stmt->bind_param("ssssii", $numero_lote, $tipo_producto, $fecha_empaque, $fecha_vencimiento, $cantidad_total, $id_lote);

    if ($stmt->execute()) {
        header("Location: modificar_inventario.php?exito=1");
        exit();
    } else {
        echo "Error al actualizar el lote: " . $stmt->error;
    }
} else {
    echo "Acceso no permitido.";
}
?>
