<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Usuarios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/global.css?v=<?php echo time(); ?>">
</head>
<body>
 <div class="contenedor-panel">
        <!-- Título centrado arriba -->
        <h2 class="titulo-gestion">Gestionar Usuarios</h2>
        
        <!-- Tabla centrada -->
        <div class="tabla-centrada">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['cedula']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['rol']); ?></td>
                        <td class="acciones">
                            <form action="gestionar_usuarios.php" method="get" style="display:inline-block;">
                                <input type="hidden" name="editar" value="<?php echo $usuario['id_usuario']; ?>">
                                <button type="submit" class="btn-editar-usuario">✏ Editar</button>
                            </form>
                            <form action="gestionar_usuarios.php" method="get" style="display:inline-block;">
                                <input type="hidden" name="eliminar" value="<?php echo $usuario['id_usuario']; ?>">
                                <button type="submit" class="btn-eliminar-usuario" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">🗑 Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
         <!-- Si hay un usuario a editar, muestra el formulario -->
        <?php if ($usuarioEditar): ?>
        <div class="contenedor-edicion">
            <h3 class="titulo-edicion">Editar Usuario</h3>
            <form method="POST" action="gestionar_usuarios.php">
                <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($usuarioEditar['id_usuario']); ?>">

                <label for="nombre_completo">Nombre completo:</label>
                <input type="text" name="nombre_completo" value="<?php echo htmlspecialchars($usuarioEditar['nombre_completo']); ?>" required>

                <label for="cedula">Cédula:</label>
                <input type="text" name="cedula" value="<?php echo htmlspecialchars($usuarioEditar['cedula']); ?>" required>

                <label for="rol">Rol:</label>
                <select name="rol" required>
                    <option value="superadmin" <?php if ($usuarioEditar['rol'] === 'superadmin') echo 'selected'; ?>>Superadmin</option>
                    <option value="administrador" <?php if ($usuarioEditar['rol'] === 'administrador') echo 'selected'; ?>>Administrador</option>
                    <option value="almacen" <?php if ($usuarioEditar['rol'] === 'almacen') echo 'selected'; ?>>Almacén</option>
                    <option value="cedi" <?php if ($usuarioEditar['rol'] === 'cedi') echo 'selected'; ?>>Cedi</option>
                    <option value="empacador" <?php if ($usuarioEditar['rol'] === 'empacador') echo 'selected'; ?>>Empacador</option>
                </select>

                <label for="contrasena">Nueva contraseña (opcional):</label>
                <input type="password" name="contrasena" placeholder="••••••••">

                <button type="submit" name="actualizar_usuario" class="btn-editar-usuario">💾 Guardar Cambios</button>
            </form>
        </div>
        <?php endif; ?>
        <!-- Botón regresar centrado abajo -->
        <div class="contenedor-regresar">
            <a href="superadmin.php" class="btn-gris">⬅ Regresar al Panel</a>
        </div>
    </div>
</body>
</html>