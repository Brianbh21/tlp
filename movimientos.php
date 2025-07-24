<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['cedula']) || !isset($_SESSION['rol'])) {
    die("Error: sesión no iniciada. Verifica login.");
}

require 'db.php';

$cedula = $_SESSION['cedula'];
$rol = $_SESSION['rol'];

$filtro = $_GET['filtro'] ?? '';

if ($rol === 'superadmin' || $rol === 'administrador') {
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

$movimientos = [];
while ($row = $resultado->fetch_assoc()) {
    $movimientos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Movimientos</title>
    <link rel="stylesheet" href="./css/global.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="contenedor-panel">
        <h2 class="titulo-gestion">Historial de Movimientos</h2>

        <form method="GET" style="text-align:center; margin-bottom: 20px;">
            <input type="text" name="filtro" placeholder="Buscar por lote, origen, destino..." value="<?php echo htmlspecialchars($_GET['filtro'] ?? ''); ?>" style="padding: 8px; width: 300px; border-radius: 5px;">
            <button type="submit" class="btn-amarillo">🔍 Buscar</button>
            <a href="exportar_excel.php?filtro=<?php echo urlencode($_GET['filtro'] ?? ''); ?>" class="btn-amarillo">📁 Descargar Excel</a>
        </form>

        <div class="tabla-centrada">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Lote</th>
                        <th>Cantidad</th>
                        <th>Fecha</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Responsable</th>
                        <th>Estado Origen</th>
                        <th>Estado Destino</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientos)): ?>
                        <tr><td colspan="9" style="text-align:center;">No hay movimientos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movimientos as $mov): ?>
                            <tr>
                                <td><?php echo $mov['id_movimiento']; ?></td>
                                <td><?php echo $mov['id_lote']; ?></td>
                                <td><?php echo $mov['cantidad']; ?></td>
                                <td><?php echo $mov['fecha_movimiento']; ?></td>
                                <td><?php echo $mov['origen']; ?></td>
                                <td><?php echo $mov['destino']; ?></td>
                                <td><?php echo $mov['nombre_completo']; ?></td>
                                <td><?php echo $mov['estado_origen']; ?></td>
                                <td><?php echo $mov['estado_destino']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="contenedor-regresar">
            <a href="<?php 
                switch ($rol) {
                    case 'superadmin': echo 'superadmin.php'; break;
                    case 'administrador': echo 'administrador.php'; break;
                    case 'empacador': echo 'empacador.php'; break;
                    case 'almacen': echo 'almacen.php'; break;
                    case 'cedi': echo 'cedi.php'; break;
                    case 'conductor': echo 'conductor.php'; break;
                    default: echo 'index.php'; break;
                }
            ?>" class="btn-gris">⬅ Regresar</a>
        </div>
    </div>
</body>
</html>
