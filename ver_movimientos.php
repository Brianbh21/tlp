<?php
// ver_movimientos.php
session_start();
require_once 'db.php';

// Simulación de sesión (quítala cuando tengas login funcionando)
$_SESSION['id_usuario'] = 1;
$_SESSION['rol'] = 'empacador'; // Cambia a 'admin' para probar
$id_usuario = $_SESSION['id_usuario'];
$rol = $_SESSION['rol'];

// Consulta de movimientos
if ($rol === 'admin') {
    $sql = "SELECT m.*, l.numero_lote, l.tipo_producto 
            FROM movimientos m 
            JOIN lotes l ON m.id_lote = l.id_lote 
            ORDER BY m.fecha_movimiento DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT m.*, l.numero_lote, l.tipo_producto 
            FROM movimientos m 
            JOIN lotes l ON m.id_lote = l.id_lote 
            WHERE m.id_usuario_responsable = ? 
            ORDER BY m.fecha_movimiento DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Movimientos</title>
    <style>
        body { font-family: Arial; background: #f2f2f2; padding: 20px; }
        h2 { color: #007bff; text-align: center; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #007bff; color: white; }
        tr:hover { background-color: #f5f5f5; }
        .tipo-completo { background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .tipo-parcial { background-color: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .fecha { font-size: 14px; color: #666; }
        .cantidad { font-weight: bold; color: #007bff; }
        .estado { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .estado-empaque { background-color: #e2e3e5; color: #383d41; }
        .estado-cedi { background-color: #d1ecf1; color: #0c5460; }
        .estado-almacen { background-color: #f8d7da; color: #721c24; }
        .enlaces { margin-top: 20px; text-align: center; }
        .enlaces a { color: #007bff; text-decoration: none; margin: 0 15px; }
        .enlaces a:hover { text-decoration: underline; }
        .no-data { text-align: center; color: #666; padding: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📋 Historial de Movimientos de Lotes</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Mov.</th>
                        <th>Lote</th>
                        <th>Producto</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Cantidad</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id_movimiento'] ?></td>
                            <td><strong><?= htmlspecialchars($row['numero_lote']) ?></strong><br><small>(ID: <?= $row['id_lote'] ?>)</small></td>
                            <td><?= htmlspecialchars($row['tipo_producto']) ?></td>
                            <td><span class="estado estado-<?= strtolower($row['estado_origen']) ?>"><?= htmlspecialchars($row['estado_origen']) ?></span></td>
                            <td><span class="estado estado-<?= strtolower($row['estado_destino']) ?>"><?= htmlspecialchars($row['estado_destino']) ?></span></td>
                            <td><span class="cantidad"><?= number_format($row['cantidad_movida']) ?></span></td>
                            <td><span class="tipo-<?= $row['tipo_movimiento'] ?>"><?= ucfirst($row['tipo_movimiento']) ?></span></td>
                            <td><span class="fecha"><?= date('d/m/Y H:i', strtotime($row['fecha_movimiento'])) ?></span></td>
                            <td><?= htmlspecialchars($row['observaciones']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <p>No se encontraron movimientos registrados.</p>
            </div>
        <?php endif; ?>
        
        <div class="enlaces">
            <a href="traslado_empaque_parcial.php">← Volver a traslados</a>
            <a href="consultar_lotes.php">📦 Ver lotes</a>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>