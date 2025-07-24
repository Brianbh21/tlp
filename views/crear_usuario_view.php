<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Usuario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/global.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="contenedor-panel">
        <div class="panel formulario">
            <h2 class="titulo-panel">Registrar Nuevo Usuario</h2>
            <form action="includes/guardar_usuario_logic.php" method="POST" class="formulario-registro">
                <label for="nombre_completo">Nombre completo:</label>
                <input type="text" name="nombre_completo" id="nombre_completo" required>

                <label for="cedula">Cédula:</label>
                <input type="text" name="cedula" id="cedula" required>

                <label for="contrasena">Contraseña:</label>
                <input type="password" name="contrasena" id="contrasena" required>

                <label for="rol">Rol:</label>
              <select name="rol" required>
                 <option value="">Seleccione un rol</option>
                 <option value="superadmin">Superadmin</option>
                 <option value="administrador">Administrador</option>
                 <option value="empacador">Empacador</option>
                 <option value="almacen">Almacén</option>
                 <option value="cedi">Cedi</option>
                 <option value="conductor">Conductor</option>
              </select>


                <button type="submit" class="btn-rojo">Guardar Usuario</button>
            </form>

            <div class="text-center">
                <a href="superadmin.php" class="btn-gris">⬅ Regresar al Panel</a>
            </div>
        </div>
    </div>
</body>
</html>
