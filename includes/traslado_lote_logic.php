<?php
// tlp/includes/traslado_lote_logic.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['rol'], $_SESSION['nombre'])) {
    header("Location: ../index.php");
    exit();
}

$rol            = $_SESSION['rol'];
$nombre_usuario = $_SESSION['nombre'];
$id_usuario     = $_SESSION['id_usuario'] ?? null;
$cedula         = $_SESSION['cedula']     ?? null;

// Asegurar id_usuario si hay cédula
if (!$id_usuario && $cedula) {
    $tmp = $conn->prepare("SELECT id_usuario FROM usuarios WHERE cedula = ?");
    $tmp->bind_param("s", $cedula);
    $tmp->execute();
    if ($u = $tmp->get_result()->fetch_assoc()) {
        $id_usuario = (int)$u['id_usuario'];
        $_SESSION['id_usuario'] = $id_usuario;
    }
    $tmp->close();
}

if (!$id_usuario) {
    $_SESSION['mensaje'] = "❌ No se pudo identificar el usuario.";
    header("Location: ../index.php");
    exit();
}

// Mapear bodega/origen por rol
$bodega_actual = match (strtolower($rol)) {
    'empacador'  => 'empaque',
    'almacen'    => 'almacen',
    'cedi'       => 'CEDI',
    'inventario' => 'inventario',
    'admin'      => '',        // admin ve todo
    default      => 'empaque'
};

// ======= POST: registrar traslado =======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_lote            = isset($_POST['id_lote']) ? (int)$_POST['id_lote'] : 0;
    $cantidad_trasladar = isset($_POST['cantidad_trasladar']) ? (int)$_POST['cantidad_trasladar'] : 0;
    $destino_tipo       = $_POST['destino_tipo']   ?? '';
    $placa_destino      = $_POST['placa_destino']  ?? '';

    if ($id_lote <= 0 || $cantidad_trasladar <= 0 || $destino_tipo === '') {
        $_SESSION['mensaje'] = "❌ Datos incompletos.";
        header("Location: ../traslado_lote.php");
        exit();
    }

    // Validar que el lote existe y está en mi bodega (excepto admin)
    if ($bodega_actual === '') {
        $v = $conn->prepare("SELECT id_lote, numero_lote, tipo_producto, cantidad_total, estado FROM lotes WHERE id_lote = ?");
        $v->bind_param("i", $id_lote);
    } else {
        $v = $conn->prepare("SELECT id_lote, numero_lote, tipo_producto, cantidad_total, estado FROM lotes WHERE id_lote = ? AND estado = ?");
        $v->bind_param("is", $id_lote, $bodega_actual);
    }
    $v->execute();
    $lote = $v->get_result()->fetch_assoc();
    $v->close();

    if (!$lote) {
        $_SESSION['mensaje'] = "❌ El lote no está en tu bodega.";
        header("Location: ../traslado_lote.php");
        exit();
    }
    if ($cantidad_trasladar > (int)$lote['cantidad_total']) {
        $_SESSION['mensaje'] = "❌ No puedes trasladar más de {$lote['cantidad_total']} unidades.";
        header("Location: ../traslado_lote.php");
        exit();
    }

    // Listado de placas (estados que NO son bodegas)
    $bodegas = ["empaque","almacen","CEDI","inventario"];
    $placas = [];
    $rs = $conn->query("SELECT DISTINCT estado FROM lotes WHERE estado NOT IN ('empaque','almacen','CEDI','inventario')");
    while ($r = $rs->fetch_assoc()) { $placas[] = $r['estado']; }

    // Resolver destino final
    if ($destino_tipo === 'conductor') {
        if ($placa_destino === '' || !in_array($placa_destino, $placas, true)) {
            $_SESSION['mensaje'] = "❌ Destino conductor inválido. Selecciona una placa válida.";
            header("Location: ../traslado_lote.php");
            exit();
        }
        $destino_final = $placa_destino; // => movimientos.estado_destino = <PLACA>
    } else {
        $permitidos = ['CEDI','almacen','empaque'];
        if (!in_array($destino_tipo, $permitidos, true)) {
            $_SESSION['mensaje'] = "❌ Destino inválido.";
            header("Location: ../traslado_lote.php");
            exit();
        }
        $destino_final = $destino_tipo;
    }

    // Crear movimiento pendiente
    $conn->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
    $conn->begin_transaction();

    try {
        $stmt_mov = $conn->prepare("
            INSERT INTO movimientos
            (id_lote, tipo_producto, origen, destino, cantidad, fecha_movimiento,
             id_responsable, estado_origen, estado_destino, estado_aceptacion)
            VALUES (?,?,?,?,?, NOW(), ?, ?, ?, 'pendiente')
        ");
        // Tipos: i s s s i  i s s
        $stmt_mov->bind_param(
            "isssiiss",
            $lote['id_lote'],           // i
            $lote['tipo_producto'],     // s
            $lote['estado'],            // s (columna 'origen')
            $destino_final,             // s (columna 'destino')
            $cantidad_trasladar,        // i
            $id_usuario,                // i
            $lote['estado'],            // s (estado_origen)
            $destino_final              // s (estado_destino)
        );
        $stmt_mov->execute();
        $stmt_mov->close();

        $conn->commit();
        $_SESSION['mensaje'] = "✅ Traslado registrado como pendiente.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensaje'] = "❌ Error al registrar traslado: " . $e->getMessage();
    }

    header("Location: ../traslado_lote.php");
    exit();
}

// ======= GET: cargar datos para la vista =======

// Lotes visibles para el rol
if ($bodega_actual === '') {
    $q = $conn->prepare("SELECT * FROM lotes WHERE cantidad_total > 0 ORDER BY tipo_producto, fecha_empaque DESC");
} else {
    $q = $conn->prepare("SELECT * FROM lotes WHERE estado = ? AND cantidad_total > 0 ORDER BY tipo_producto, fecha_empaque DESC");
    $q->bind_param("s", $bodega_actual);
}
$q->execute();
$res = $q->get_result();

// Agrupar por tipo
$lotes_por_tipo = [];
while ($row = $res->fetch_assoc()) {
    $lotes_por_tipo[$row['tipo_producto']][] = $row;
}
$q->close();

// Placas disponibles para el select
$placas = [];
$rs = $conn->query("SELECT DISTINCT estado FROM lotes WHERE estado NOT IN ('empaque','almacen','CEDI','inventario') ORDER BY estado");
while ($r = $rs->fetch_assoc()) { $placas[] = $r['estado']; }

// Mensaje
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

// Exponer a la vista:
$GLOBALS['lotes_por_tipo'] = $lotes_por_tipo;
$GLOBALS['placas']         = $placas;
$GLOBALS['mensaje']        = $mensaje;
$GLOBALS['rol']            = $rol;
$GLOBALS['bodega_actual']  = $bodega_actual;
$GLOBALS['nombre_usuario'] = $nombre_usuario;
$GLOBALS['id_usuario']     = $id_usuario;
