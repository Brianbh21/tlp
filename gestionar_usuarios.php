<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}
include 'includes/gestionar_usuarios_logic.php';
include 'views/gestionar_usuarios_view.php';
?>
