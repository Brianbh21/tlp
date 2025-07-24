<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Super Admin - TLP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
</head>
<body>
  <!-- Bienvenida arriba a la derecha -->
  <div class="usuario-info">
    Bienvenido, <?php echo htmlspecialchars($nombre); ?> <span class="rol">(SUPER ADMIN)</span>
  </div>

  <!-- Contenedor general centrado -->
  <div class="contenedor-panel">
    <div class="panel">
      <h2 class="titulo-panel">Panel de Super Administrador</h2>

      <div class="botones-verticales">
        <a href="crear_usuario.php" class="boton-institucional">👤 Crear Usuarios</a>
        <a href="gestionar_usuarios.php" class="boton-institucional">🔧 Gestionar Usuarios</a>
        <a href="modificar_inventario.php" class="boton-institucional">🛠️ Modificar Inventario</a>
        <a href="movimientos.php" class="boton-institucional">🔎 Ver Trazabilidad</a>
        <a href="ver_lotes.php" class="boton-institucional">📦 Ver Lotes</a>
        <a href="salida_productos.php" class="boton-institucional">📤 Asignar Productos a Conductores</a>
        <a href="cerrar_sesion.php" class="boton-institucional cerrar">🔒 Cerrar Sesión</a>
      </div>
    </div>
  </div>
</body>
</html>
