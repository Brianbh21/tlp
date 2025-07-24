<?php
session_start();
require 'includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['rol']) || !isset($_SESSION['nombre'])) {
    die("Sesión no iniciada.");
}

$rol = $_SESSION['rol'];
$nombre = $_SESSION['nombre'];
$buscar_producto = $_GET['buscar_producto'] ?? '';
$lotes_encontrados = [];
$productos_disponibles = [];
$error = null;

// Redirección por rol
function redireccionarSegunRol($rol) {
    return match ($rol) {
        'superadmin' => 'superadmin.php',
        'administrador' => 'administrador.php',
        'empacador' => 'empacador.php',
        'almacen' => 'almacen.php',
        'cedi' => 'cedi.php',
        default => 'index.php'
    };
}

// Verificar archivo QR
function verificarQRExiste($qr_codigo) {
    $ruta = __DIR__ . "/qrcodes/$qr_codigo";
    return file_exists($ruta) ? "qrcodes/$qr_codigo" : null;
}

// Consulta principal de lotes
try {
    if ($rol === 'superadmin' || $rol === 'administrador') {
        if ($buscar_producto !== '') {
            $stmt = $conn->prepare("SELECT * FROM lotes WHERE tipo_producto LIKE ? ORDER BY fecha_empaque DESC");
            $like = "%$buscar_producto%";
            $stmt->bind_param("s", $like);
        } else {
            $stmt = $conn->prepare("SELECT * FROM lotes ORDER BY fecha_empaque DESC");
        }
    } else {
        $estado = $rol === 'empacador' ? 'empaque' : ($rol === 'almacen' ? 'almacen' : ($rol === 'cedi' ? 'CEDI' : ''));
        if ($buscar_producto !== '') {
            $stmt = $conn->prepare("SELECT * FROM lotes WHERE tipo_producto LIKE ? AND estado = ? ORDER BY fecha_empaque DESC");
            $like = "%$buscar_producto%";
            $stmt->bind_param("ss", $like, $estado);
        } else {
            $stmt = $conn->prepare("SELECT * FROM lotes WHERE estado = ? ORDER BY fecha_empaque DESC");
            $stmt->bind_param("s", $estado);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['qr_url'] = verificarQRExiste($row['qr_codigo']);
        $row['qr_disponible'] = !empty($row['qr_url']);
        $lotes_encontrados[] = $row;
    }
} catch (Exception $e) {
    $error = "⚠️ Error al obtener lotes: " . $e->getMessage();
}

// Productos únicos para el select
try {
    $sql_productos = ($rol === 'superadmin' || $rol === 'administrador')
        ? "SELECT DISTINCT tipo_producto FROM lotes"
        : "SELECT DISTINCT tipo_producto FROM lotes WHERE estado = '" . ($estado ?? '') . "'";
    $res = $conn->query($sql_productos);
    while ($row = $res->fetch_assoc()) {
        $productos_disponibles[] = $row['tipo_producto'];
    }
} catch (Exception $e) {
    $error = "⚠️ Error al obtener productos: " . $e->getMessage();
}

include 'includes/ver_lotes_html.php';
?>
