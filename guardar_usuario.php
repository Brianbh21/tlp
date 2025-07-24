<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'db.php'; // tu archivo de conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = mysqli_real_escape_string($conn, $_POST['nombre_completo']);
    $cedula = mysqli_real_escape_string($conn, $_POST['cedula']);
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT); // ciframos la contraseña
    $rol = mysqli_real_escape_string($conn, $_POST['rol']);

    // Verificar si la cédula ya existe
    $check_sql = "SELECT * FROM usuarios WHERE cedula = '$cedula'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        echo "Error: Ya existe un usuario con esa cédula.";
        echo '<br><a href="crear_usuario.php">Volver al formulario</a>';
        exit();
    }

    // Insertar usuario nuevo
    $sql = "INSERT INTO usuarios (nombre_completo, cedula, contrasena, rol) VALUES ('$nombre_completo', '$cedula', '$contrasena', '$rol')";
    if (mysqli_query($conn, $sql)) {
        header("Location: superadmin.php?msg=usuario_creado");
        exit();
    } else {
        echo "Error al crear usuario: " . mysqli_error($conn);
        echo '<br><a href="crear_usuario.php">Volver al formulario</a>';
    }
} else {
    header("Location: crear_usuario.php");
    exit();
}
