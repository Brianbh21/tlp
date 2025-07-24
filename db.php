<?php
$host = "localhost";
$usuario = "root";
$contrasena = "nueva_contraseña"; 
$base_datos = "trazabilidad"; 

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
?>
