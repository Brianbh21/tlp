<?php
include 'db.php';

// Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: gestionar_usuarios.php");
    exit();
}

// Actualizar usuario
if (!empty($_POST['actualizar_usuario'])) {
    $id = $_POST['id_usuario'];
    $nombre = $_POST['nombre_completo'];
    $cedula = $_POST['cedula'];
    $rol = $_POST['rol'];
    $nueva_contrasena = $_POST['contrasena'];

    if (!empty($nueva_contrasena)) {
        $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo=?, cedula=?, rol=?, contrasena=? WHERE id_usuario=?");
        $stmt->bind_param("ssssi", $nombre, $cedula, $rol, $hash, $id);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo=?, cedula=?, rol=? WHERE id_usuario=?");
        $stmt->bind_param("sssi", $nombre, $cedula, $rol, $id);
    }

    $stmt->execute();
    header("Location: gestionar_usuarios.php");
    exit();
}

// Obtener usuarios
$result = $conn->query("SELECT * FROM usuarios ORDER BY nombre_completo ASC");
$usuarios = $result->fetch_all(MYSQLI_ASSOC);

// Si se va a editar
$usuarioEditar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $usuarioEditar = $stmt->get_result()->fetch_assoc();
}
?>