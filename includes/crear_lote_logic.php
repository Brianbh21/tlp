<?php
session_start();
require 'db.php';

// Verificar acceso
if (!isset($_SESSION['nombre']) || !isset($_SESSION['cedula']) || $_SESSION['rol'] !== 'empacador') {
    echo "<div style='background: red; color: white; padding: 20px; text-align: center;'>";
    echo "ERROR: Acceso denegado.";
    if (!isset($_SESSION['nombre'])) {
        echo "<br>- No hay sesión activa";
    }
    if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'empacador') {
        echo "<br>- Rol no autorizado: " . $_SESSION['rol'];
    }
    echo "<br><br><a href='index.php' style='color: yellow;'>Ir a Login</a>";
    echo "<br><a href='empacador.php' style='color: yellow;'>Volver</a>";
    echo "</div>";
    exit();
}

// Obtener ID del empacador
$cedula_logueada = $_SESSION['cedula'];
$id_empacador = null;

try {
    $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE cedula = ?");
    $stmt->bind_param("s", $cedula_logueada);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $id_empacador = $row['id_usuario'];
    }
} catch (Exception $e) {
    $id_empacador = null;
}

// Cargar productos
$productos = [];

try {
    $res = $conn->query("SELECT nombre_producto FROM productos");
    while ($prod = $res->fetch_assoc()) {
        $productos[] = $prod['nombre_producto'];
    }
} catch (Exception $e) {
    $productos[] = "Error al cargar productos";
}
// Exportar variable bodega_actual para la vista

$bodega_actual = match ($rol) {
    'admin' => 'Todas las bodegas (Administrador)',
    'empacador' => '📋 Empaque',
    'almacen' => '🏪 Almacén',
    'cedi' => '🏢 CEDI',
    'inventario' => '📦 Inventario',
    default => '📋 Empaque (por defecto)'
};
