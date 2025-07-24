<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'includes/db.php';

$estado = $_GET['estado'] ?? '';
$consulta = "SELECT * FROM lotes";

if ($estado !== '') {
    $consulta .= " WHERE estado = ? ORDER BY fecha_empaque DESC";
    $stmt = $conn->prepare($consulta);
    $stmt->bind_param("s", $estado);
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $consulta .= " ORDER BY fecha_empaque DESC";
    $resultado = $conn->query($consulta);
}

$lotes = $resultado->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Inventario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="contenedor-panel">
    <h2 class="titulo-gestion">Modificar Inventario</h2>
<?php if (isset($_GET['eliminado']) && $_GET['eliminado'] === 'ok'): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold;">
        ✅ Lote eliminado correctamente.
    </div>
<?php endif; ?>

    <!-- Filtro por estado -->
    <form method="GET" style="text-align: center; margin-bottom: 20px;">
        <label for="estado">Filtrar por estado:</label>
        <select name="estado" id="estado" style="padding: 6px 10px; border-radius: 6px;">
            <option value="">-- Todos --</option>
            <option value="cedi" <?php if ($estado === 'cedi') echo 'selected'; ?>>CEDI</option>
            <option value="almacen" <?php if ($estado === 'almacen') echo 'selected'; ?>>Almacén</option>
            <option value="empaque" <?php if ($estado === 'empaque') echo 'selected'; ?>>Empaque</option>
            <option value="conductor" <?php if ($estado === 'conductor') echo 'selected'; ?>>Conductor</option>
        </select>
        <button type="submit" class="btn-gris" style="margin-left: 10px;">Filtrar</button>
    </form>

    <!-- Tabla -->
    <div class="tabla-centrada">
        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>Nº Lote</th>
                    <th>Tipo Producto</th>
                    <th>Fecha Empaque</th>
                    <th>Fecha Vencimiento</th>
                    <th>Cantidad Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lotes)): ?>
                    <tr><td colspan="6" style="text-align: center;">No hay lotes registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($lotes as $lote): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lote['numero_lote']); ?></td>
                            <td><?php echo htmlspecialchars($lote['tipo_producto']); ?></td>
                            <td><?php echo htmlspecialchars($lote['fecha_empaque']); ?></td>
                            <td><?php echo htmlspecialchars($lote['fecha_vencimiento']); ?></td>
                            <td><?php echo htmlspecialchars($lote['cantidad_total']); ?></td>
                            <td class="acciones">
                                <a href="editar_lote.php?id=<?php echo $lote['id_lote']; ?>" class="btn-editar-usuario">✏ Editar</a>
                                <a href="eliminar_lote.php?id=<?php echo $lote['id_lote']; ?>" class="btn-eliminar-usuario" onclick="return confirm('¿Estás seguro de eliminar este lote?')">🗑 Eliminar</a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Botón regresar -->
    <div class="contenedor-regresar">
        <a href="superadmin.php" class="btn-gris">⬅ Regresar al Panel</a>
    </div>
</div>
</body>
</html>
