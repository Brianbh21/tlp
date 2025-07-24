<?php
session_start();

// Validar que el usuario tenga el rol correcto
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header('Location: index.php');
    exit();
}

// Recuperar nombre para mostrar
$nombre = $_SESSION['nombre'] ?? 'Usuario';
?>
