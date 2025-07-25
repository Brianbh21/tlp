<?php
session_start();
if (!isset($_SESSION['nombre']) || $_SESSION['rol'] !== 'almacen') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Almacén</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/global.css">
</head>
<body>
    <div class="contenedor-panel">
        <div class="panel">
            <h2 class="titulo-panel">Panel de Almacén</h2>

            <div class="botones-verticales">
                <a href="traslado_lote.php" class="boton-institucional">🚚 Traslado Manual</a>
                <a href="traslado_camara.php" class="boton-institucional">📷 Traslado por Cámara</a>
                <a href="imprimir_lotes.php" class="boton-institucional">🖨️ Imprimir Lotes</a>
                <a href="movimientos.php" class="boton-institucional">📦 Ver Movimientos</a>
                <a href="fechas_cercanas.php" class="boton-institucional">📅 Fechas Próximas</a>
                <a href="logout.php" class="boton-institucional cerrar">🔒 Cerrar Sesión</a>
            </div>

            <div class="usuario-info">
                Bienvenido, <?php echo $_SESSION['nombre']; ?> (Almacén)
            </div>
        </div>
    </div>
</body>
</html>
