<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: index.php");
    exit();
}
$nombre = $_SESSION['nombre'] ?? 'Administrador';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Administrador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="contenedor-panel">
        <h2 class="titulo-panel">Panel del Administrador</h2>
        <p class="usuario-info">👤 Bienvenido, <?php echo htmlspecialchars($nombre); ?> (Administrador)</p>

        <div class="botones-verticales">
            <a href="ver_lotes.php" class="boton-institucional">📦 Ver Lotes</a>
            <a href="movimientos.php" class="boton-institucional">📋 Ver Movimientos</a>
            <a href="bloquear_usuarios.php" class="boton-institucional">🔒 Gestionar Usuarios</a>
        </div>

        <div class="contenedor-regresar">
            <a href="index.php" class="btn-gris">⏏ Cerrar sesión</a>
        </div>
    </div>
</body>
</html>
