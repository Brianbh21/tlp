<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'includes/db.php';

if (isset($_GET['id'])) {
    $id_lote = $_GET['id'];

    // Preparamos la eliminación
    $stmt = $conn->prepare("DELETE FROM lotes WHERE id_lote = ?");
    $stmt->bind_param("i", $id_lote);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Eliminación exitosa
        header("Location: modificar_inventario.php?mensaje=eliminado");
    } else {
        // El ID no existe o no se pudo eliminar
        header("Location: modificar_inventario.php?error=no_se_encontro");
    }

    exit();
} else {
    // ID no proporcionado
    header("Location: modificar_inventario.php?error=falta_id");
    exit();
}
