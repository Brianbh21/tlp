<?php
session_start();
require 'db.php';

if (!isset($_SESSION['rol'])) {
    die("No autorizado");
}

$rol = $_SESSION['rol'];
$hoy = date("Y-m-d");
$max_fecha = date("Y-m-d", strtotime("+60 days"));

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=fechas_cercanas_" . date("Ymd_His") . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

$query = "SELECT numero_lote, tipo_producto, fecha_vencimiento, cantidad_total, estado 
          FROM lotes 
          WHERE estado = '$rol' 
          AND fecha_vencimiento <= '$max_fecha'
          ORDER BY fecha_vencimiento ASC";

$result = $conn->query($query);

echo "<table border='1'>";
echo "<tr><th>Número Lote</th><th>Tipo Producto</th><th>Fecha Vencimiento</th><th>Cantidad</th><th>Estado</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['numero_lote']}</td>";
    echo "<td>{$row['tipo_producto']}</td>";
    echo "<td>{$row['fecha_vencimiento']}</td>";
    echo "<td>{$row['cantidad_total']}</td>";
    echo "<td>{$row['estado']}</td>";
    echo "</tr>";
}
echo "</table>";
?>
