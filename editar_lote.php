<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] === '') {
    header("Location: index.php");
    exit();
}

include 'includes/db.php';

// Validar ID del lote
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID de lote no válido.";
    exit();
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM lotes WHERE id_lote = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$lote = $resultado->fetch_assoc();

if (!$lote) {
    echo "Lote no encontrado.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Lote</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/global.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="contenedor-panel">
    <h2 class="titulo-gestion">Editar Lote</h2>

    <form action="actualizar_lote.php" method="POST">
        <input type="hidden" name="id_lote" value="<?php echo $lote['id_lote']; ?>">

        <label for="numero_lote">Nº Lote:</label>
        <input type="text" name="numero_lote" required value="<?php echo htmlspecialchars($lote['numero_lote']); ?>">

        <label for="tipo_producto">Tipo de Producto:</label>
        <input type="text" name="tipo_producto" required value="<?php echo htmlspecialchars($lote['tipo_producto']); ?>">

        <label for="fecha_empaque">Fecha de Empaque:</label>
        <input type="date" name="fecha_empaque" required value="<?php echo $lote['fecha_empaque']; ?>">

        <label for="fecha_vencimiento">Fecha de Vencimiento:</label>
        <input type="date" name="fecha_vencimiento" required value="<?php echo $lote['fecha_vencimiento']; ?>">

        <label for="cantidad_total">Cantidad Total:</label>
        <input type="number" name="cantidad_total" min="1" required value="<?php echo $lote['cantidad_total']; ?>">

        <div class="contenedor-regresar">
            <button type="submit" class="btn-editar-usuario">💾 Guardar Cambios</button>
            <a href="modificar_inventario.php" class="btn-gris">⬅ Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>
