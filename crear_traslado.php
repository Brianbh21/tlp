<?php
session_start();
require 'db.php';

if (!isset($_SESSION['rol']) || !isset($_SESSION['cedula'])) {
    die("Acceso denegado. Debes iniciar sesión.");
}

$cedula = $_SESSION['cedula'];
$rol = $_SESSION['rol'];

// Validar datos recibidos desde un formulario o enlace
$id_lote = isset($_POST['id_lote']) ? intval($_POST['id_lote']) : 0;
$nuevo_destino = isset($_POST['nuevo_destino']) ? trim($_POST['nuevo_destino']) : '';

if ($id_lote <= 0 || $nuevo_destino === '') {
    die("Error: Datos incompletos para realizar el traslado.");
}

// Verificar que el lote exista
$sql_verificar = "SELECT * FROM lotes WHERE id = ?";
$stmt = $conn->prepare($sql_verificar);
$stmt->bind_param("i", $id_lote);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("El lote con ID $id_lote no existe.");
}

$lote = $resultado->fetch_assoc();
$tipo_producto = $lote['tipo_producto'];
$cantidad = $lote['cantidad_total'];
$origen = $lote['destino']; // lugar actual del lote

// Insertar en la tabla movimientos como 'pendiente'
$sql_insertar = "INSERT INTO movimientos (id_lote, tipo_producto, cantidad, origen, destino, id_responsable, estado_aceptacion) 
                 VALUES (?, ?, ?, ?, ?, ?, 'pendiente')";

$stmt = $conn->prepare($sql_insertar);
$stmt->bind_param("isisss", $id_lote, $tipo_producto, $cantidad, $origen, $nuevo_destino, $cedula);

if ($stmt->execute()) {
    echo "<h2>Traslado pendiente registrado exitosamente.</h2>";
    echo "<p>Lote ID $id_lote será trasladado de <strong>$origen</strong> a <strong>$nuevo_destino</strong>, sujeto a aceptación.</p>";
    echo '<a href="empacador.php">Volver al panel</a>';
} else {
    echo "Error al registrar el traslado: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
