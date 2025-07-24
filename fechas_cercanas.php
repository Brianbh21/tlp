<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['cedula']) || !isset($_SESSION['rol'])) {
    die("Error: sesión no iniciada.");
}

require 'db.php';

$rol = $_SESSION['rol'];
$hoy = date("Y-m-d");
$max_fecha = date("Y-m-d", strtotime("+60 days"));

$query = "SELECT numero_lote, tipo_producto, fecha_vencimiento, cantidad_total, estado 
          FROM lotes 
          WHERE estado = '$rol' 
          AND fecha_vencimiento <= '$max_fecha'
          ORDER BY fecha_vencimiento ASC";

$result = $conn->query($query);

$lotes = [];
while ($row = $result->fetch_assoc()) {
    $lotes[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fechas cercanas a vencer</title>
    <link rel="stylesheet" href="css/fechas_cercanas.css">
</head>
<body>
    <?php include 'views/fechas_cercanas.html'; ?>
    <script>
        const lotes = <?php echo json_encode($lotes); ?>;
    </script>
    <script src="js/fechas_cercanas.js"></script>
</body>
</html>
