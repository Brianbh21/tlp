<?php
session_start();
include 'db.php';

// Verificar que el usuario sea superadmin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: ../index.php");
    exit();
}

// Validar que todos los campos estén presentes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre_completo'] ?? '';
    $cedula = $_POST['cedula'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $rol = $_POST['rol'] ?? '';

    if (!empty($nombre) && !empty($cedula) && !empty($contrasena) && !empty($rol)) {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, cedula, contrasena, rol) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $cedula, $hash, $rol);
        if ($stmt->execute()) {
            header("Location: ../superadmin.php");
            exit();
        } else {
            echo "Error al guardar el usuario.";
        }
    } else {
        echo "Todos los campos son obligatorios.";
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>
