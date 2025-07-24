<?php
session_start();

if (!isset($_SESSION['nombre']) || $_SESSION['rol'] !== 'empacador') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Empacador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Usuario arriba a la derecha -->
    <div class="usuario-info">
        Usuario: <?php echo htmlspecialchars($_SESSION['nombre']); ?>
    </div>

    <!-- Contenedor centrado -->
    <div class="contenedor-panel">
        <div class="panel">
            <!-- TÍTULO DENTRO DEL PANEL -->
            <h2 class="titulo-panel">Panel del Empacador</h2>

            <div class="botones-verticales">
                <a href="crear_lote.php" class="boton-institucional">📦 Registrar nuevo lote</a>
                <a href="ver_lotes.php" class="boton-institucional">📄 Ver lotes creados</a>
                <a href="traslado_lote.php" class="boton-institucional">🚚 Realizar traslado</a>
                <a href="cerrar_sesion.php" class="boton-institucional cerrar">🔒 Cerrar Sesión</a>
            </div>
        </div>
    </div>
</body>
</html>
