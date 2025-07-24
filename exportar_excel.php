<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['cedula']) || !isset($_SESSION['rol'])) {
    die("Sesión no válida");
}

require 'db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=movimientos.xls");

$cedula = $_SESSION['cedula'];
$rol = $_SESSION['rol'];
$filtro = $_GET['filtro'] ?? '';

if ($rol === 'superadmin') {
    $sql = "SELECT m.*, u.nombre_completo 
            FROM movimientos m 
            LEFT JOIN usuarios u ON m.id_responsable = u.cedula 
            WHERE 1";
    $parametros = [];
    $tipos = "";
} else {
    $sql = "SELECT m.*, u.nombre_completo 
            FROM movimientos m 
            LEFT JOIN usuarios u ON m.id_responsable = u.cedula 
            WHERE (m.id_responsable = ? OR m.estado_destino = ?)";
    $parametros = [$cedula, $rol];
    $tipos = "ss";
}

if (!empty($filtro)) {
    $sql .= " AND (m.id_lote LIKE ? OR m.origen LIKE ? OR m.destino LIKE ?)";
    $filtro = "%$filtro%";
    array_push($parametros, $filtro, $filtro, $filtro);
    $tipos .= "sss";
}

$sql .= " ORDER BY m.fecha_movimiento DESC";

$stmt = $conn->prepare($sql);
if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$resultado = $stmt->get_result();

// Encabezado de tabla
echo "<table border='1'>";
echo "<tr>
        <th>ID</th>
        <th>Lote</th>
        <th>Cantidad</th>
        <th>Fecha</th>
        <th>Origen</th>
        <th>Destino</th>
        <th>Responsable</th>
        <th>Estado Origen</th>
        <th>Estado Destino</th>
      </tr>";

// Datos
while ($row = $resultado->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id_movimiento']}</td>";
    echo "<td>{$row['id_lote']}</td>";
    echo "<td>{$row['cantidad']}</td>";
    echo "<td>{$row['fecha_movimiento']}</td>";
    echo "<td>{$row['origen']}</td>";
    echo "<td>{$row['destino']}</td>";
    echo "<td>{$row['nombre_completo']}</td>";
    echo "<td>{$row['estado_origen']}</td>";
    echo "<td>{$row['estado_destino']}</td>";
    echo "</tr>";
}
echo "</table>";
?>
