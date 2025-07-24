<?php
session_start();
include 'db.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cedula = $_POST['cedula'];
    $contrasena = $_POST['contrasena'];

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE cedula = ?");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();

        // 🚫 Verificar si el usuario está bloqueado
        if ($usuario['estado_usuario'] === 'bloqueado') {
            $error = "⚠ Usuario bloqueado. Contacte al administrador.";
        } else {
            $hash_db = $usuario['contrasena'];

            // ✅ Permitir hash o texto plano
            if ($contrasena === $hash_db || password_verify($contrasena, $hash_db)) {
                $_SESSION['nombre'] = $usuario['nombre_completo'];
                $_SESSION['rol'] = $usuario['rol'];
                $_SESSION['cedula'] = $usuario['cedula'];
                $_SESSION['estado'] = $usuario['estado'] ?? '';

                switch ($usuario['rol']) {
                    case 'superadmin': header("Location: superadmin.php"); break;
                    case 'administrador': header("Location: administrador.php"); break;
                    case 'empacador': header("Location: empacador.php"); break;
                    case 'almacen': header("Location: almacen.php"); break;
                    case 'cedi': header("Location: cedi.php"); break;
                    case 'conductor': header("Location: conductor.php"); break;
                    default: header("Location: dashboard.php"); break;
                }
                exit();
            } else {
                $error = "❌ Contraseña incorrecta.";
            }
        }
    } else {
        $error = "❌ Usuario no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TLP - Iniciar Sesión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
</head>
<body class="page-center">
    <form method="post" class="login-box">
        <img src="img/krumerlogo.png" alt="Logo">
        <h2>Iniciar Sesión en TLP</h2>

        <input type="text" name="cedula" placeholder="Cédula" required>
        <input type="password" name="contrasena" placeholder="Contraseña" required>
        <button type="submit" class="boton">Entrar</button>

        <?php if (!empty($error)) : ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>
    </form>
</body>
</html>
