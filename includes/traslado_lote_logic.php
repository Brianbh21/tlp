<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db.php';

if (!isset($_SESSION['nombre'])) {
    header("Location: index.php");
    exit();
}

$rol            = $_SESSION['rol']        ?? 'empacador';
$id_usuario     = $_SESSION['id_usuario'] ?? null;
$nombre_usuario = $_SESSION['nombre']     ?? 'Usuario';
$cedula         = $_SESSION['cedula']     ?? 'Sin cédula';

if (!$id_usuario && $cedula !== 'Sin cédula') {
    $stmt_user = $conn->prepare("SELECT id_usuario FROM usuarios WHERE cedula = ?");
    $stmt_user->bind_param("s", $cedula);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user_data   = $result_user->fetch_assoc();
        $id_usuario  = $user_data['id_usuario'];
        $_SESSION['id_usuario'] = $id_usuario;
    }
    $stmt_user->close();
}

if (!$id_usuario) {
    exit("❌ Error: No se pudo obtener el ID del usuario.");
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_lote            = $_POST['id_lote']          ?? null;
    $nuevo_estado       = $_POST['nuevo_estado']     ?? null;
    $cantidad_trasladar = (int)($_POST['cantidad_trasladar'] ?? 0);

    if (!$id_lote || !$nuevo_estado || $cantidad_trasladar <= 0) {
        $mensaje = "❌ Datos incompletos o inválidos.";
    } else {

        if ($rol === 'admin') {
            $verifica = $conn->prepare("SELECT * FROM lotes WHERE id_lote = ?");
            $verifica->bind_param("i", $id_lote);
        } else {
            $estado_permitido = match ($rol) {
                'empacador' => 'empaque',
                'almacen'   => 'almacen',
                'cedi'      => 'CEDI',
                'inventario'=> 'inventario',
                default     => 'empaque'
            };
            $verifica = $conn->prepare("SELECT * FROM lotes WHERE id_lote = ? AND estado = ?");
            $verifica->bind_param("is", $id_lote, $estado_permitido);
        }

        $verifica->execute();
        $result = $verifica->get_result();

        if ($result->num_rows === 0) {
            $mensaje = "❌ No tienes permiso para trasladar este lote, o no está en tu bodega.";
        } else {
            $lote_actual     = $result->fetch_assoc();
            $cantidad_actual = (int)$lote_actual['cantidad_total'];
            $estado_origen   = $lote_actual['estado'];
            $tipo_producto   = $lote_actual['tipo_producto'];

            if ($cantidad_trasladar > $cantidad_actual) {
                $mensaje = "❌ No puedes trasladar más de $cantidad_actual unidades.";
            } else {
                // LÓGICA DIFERIDA: solo registramos el movimiento como pendiente
                $conn->begin_transaction();
                try {
                    $stmt_mov = $conn->prepare("
                        INSERT INTO movimientos
                        (id_lote, tipo_producto, origen, destino, cantidad, fecha_movimiento,
                         id_responsable, estado_origen, estado_destino, estado_aceptacion)
                        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, 'pendiente')
                    ");
                    // 8 variables -> 8 tipos
                    // i: id_lote
                    // s: tipo_producto
                    // s: origen
                    // s: destino
                    // i: cantidad
                    // i: id_responsable
                    // s: estado_origen
                    // s: estado_destino
                    $stmt_mov->bind_param(
                        "isssiiss",
                        $id_lote,
                        $tipo_producto,
                        $estado_origen,
                        $nuevo_estado,
                        $cantidad_trasladar,
                        $id_usuario,
                        $estado_origen,
                        $nuevo_estado
                    );
                    $stmt_mov->execute();

                    $conn->commit();
                    $mensaje = "✅ Traslado registrado como pendiente. Esperando aprobación.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $mensaje = "❌ Error en traslado: " . $e->getMessage();
                }
            }
        }
        $verifica->close();
    }
}

// Cargar lotes para el formulario
$estado_filtro = match ($rol) {
    'empacador' => 'empaque',
    'almacen'   => 'almacen',
    'cedi'      => 'CEDI',
    'inventario'=> 'inventario',
    'admin'     => '',
    default     => 'empaque'
};

if ($rol === 'admin') {
    $stmt = $conn->prepare("SELECT * FROM lotes WHERE cantidad_total > 0 ORDER BY tipo_producto, fecha_empaque DESC");
} else {
    $stmt = $conn->prepare("SELECT * FROM lotes WHERE estado = ? AND cantidad_total > 0 ORDER BY tipo_producto, fecha_empaque DESC");
    $stmt->bind_param("s", $estado_filtro);
}
$stmt->execute();
$result = $stmt->get_result();

$lotes_por_tipo = [];
while ($row = $result->fetch_assoc()) {
    $lotes_por_tipo[$row['tipo_producto']][] = $row;
}
$stmt->close();
?>
