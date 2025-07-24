<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: index.php");
    exit();
}

$mensaje = "";

// Procesar bloqueo o desbloqueo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cedula'], $_POST['accion'])) {
    $cedula = $_POST['cedula'];
    $accion = $_POST['accion'];

    if (in_array($accion, ['bloquear', 'activar'])) {
        $nuevo_estado = ($accion === 'bloquear') ? 'bloqueado' : 'activo';
        $stmt = $conn->prepare("UPDATE usuarios SET estado_usuario = ? WHERE cedula = ? AND rol IN ('almacen','cedi','empacador','conductor')");
        $stmt->bind_param("ss", $nuevo_estado, $cedula);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $mensaje = "Usuario actualizado correctamente.";
        } else {
            $mensaje = "No se pudo actualizar (verifica el rol o cédula).";
        }
    }
}

// Consultar usuarios permitidos
$usuarios = [];
$result = $conn->query("SELECT cedula, nombre_completo, rol, estado_usuario FROM usuarios WHERE rol IN ('almacen','cedi','empacador','conductor')");

while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Usuarios</title>
    <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="contenedor-panel">
    <h2 class="titulo-panel">🔒 Bloqueo de Usuarios</h2>
    <?php if (!empty($mensaje)) echo "<p style='text-align:center; color:green;'>$mensaje</p>"; ?>

    <table class="tabla-usuarios">
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $user): ?>
                <tr>
                    <td><?php echo $user['cedula']; ?></td>
                    <td><?php echo $user['nombre_completo']; ?></td>
                    <td><?php echo $user['rol']; ?></td>
                    <td><?php echo $user['estado_usuario']; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="cedula" value="<?php echo $user['cedula']; ?>">
                            <input type="hidden" name="accion" value="<?php echo ($user['estado_usuario'] === 'activo') ? 'bloquear' : 'activar'; ?>">
                            <button class="btn-amarillo" type="submit">
                                <?php echo ($user['estado_usuario'] === 'activo') ? '🚫 Bloquear' : '✅ Activar'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="contenedor-regresar">
        <a href="administrador.php" class="btn-gris">⬅ Volver</a>
    </div>
</div>
</body>
</html>
