<?php
// Mostrar errores claramente
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

if (!isset($_SESSION['cedula']) || !isset($_SESSION['rol'])) {
    header("Location: index.php");
    exit();
}

$rol           = $_SESSION['rol'];
$id_movimiento = $_POST['id_traslado'] ?? null;
$accion        = $_POST['accion'] ?? null;

if (!$id_movimiento || !$accion) {
    die("Error: Datos incompletos.");
}

// Configurar transacción
$conn->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
$conn->autocommit(false);
$conn->begin_transaction();

try {
    /* 1) Bloquear movimiento pendiente */
    $stmt = $conn->prepare("SELECT * FROM movimientos WHERE id_movimiento = ? AND estado_aceptacion = 'pendiente' FOR UPDATE");
    $stmt->bind_param("i", $id_movimiento);
    $stmt->execute();
    $mov = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$mov) {
        throw new Exception("Movimiento no encontrado o ya procesado.");
    }

    $id_lote_origen = (int)$mov['id_lote'];
    $cant_mov       = (int)$mov['cantidad'];
    $destino        = $mov['destino'];

    /* 2) Bloquear lote origen */
    $stmt = $conn->prepare("SELECT * FROM lotes WHERE id_lote = ? FOR UPDATE");
    $stmt->bind_param("i", $id_lote_origen);
    $stmt->execute();
    $lote_o = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$lote_o) throw new Exception("Lote origen no encontrado.");

    $cant_actual   = (int)$lote_o['cantidad_total'];
    $numero_lote   = $lote_o['numero_lote'];
    $tipo_producto = $lote_o['tipo_producto'];

    if ($accion === 'aceptar') {
        /* 3) Validaciones */
        if ($cant_mov > $cant_actual) throw new Exception("Inventario insuficiente. Disp: $cant_actual, Solicitado: $cant_mov");
        if ($cant_actual <= 0)        throw new Exception("Lote sin inventario.");

        /* 4) Marcar movimiento aceptado */
        $stmt = $conn->prepare("UPDATE movimientos SET estado_aceptacion='aceptado', fecha_aceptacion = NOW() WHERE id_movimiento = ?");
        $stmt->bind_param("i", $id_movimiento);
        $stmt->execute();
        $stmt->close();

        /* 5) Sumar al destino o crear lote */
        $stmt = $conn->prepare("SELECT id_lote FROM lotes WHERE numero_lote = ? AND tipo_producto = ? AND estado = ? FOR UPDATE");
        $stmt->bind_param("sss", $numero_lote, $tipo_producto, $destino);
        $stmt->execute();
        $resDest = $stmt->get_result();
        $stmt->close();

        if ($resDest->num_rows > 0) {
            $id_dest = (int)$resDest->fetch_assoc()['id_lote'];
            $stmt = $conn->prepare("UPDATE lotes SET cantidad_total = cantidad_total + ? WHERE id_lote = ?");
            $stmt->bind_param("ii", $cant_mov, $id_dest);
            $stmt->execute();
            $stmt->close();
        } else {
            // OJO: tu columna es codigo_vitad
            $stmt = $conn->prepare("
                INSERT INTO lotes
                (numero_lote, tipo_producto, fecha_empaque, fecha_vencimiento, cantidad_total,
                 qr_codigo, planta_origen, id_empacador, estado, codigo_vitad, embalaje, cantidad_origen)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "ssssiissssii",
                $lote_o['numero_lote'],
                $lote_o['tipo_producto'],
                $lote_o['fecha_empaque'],
                $lote_o['fecha_vencimiento'],
                $cant_mov,
                $lote_o['qr_codigo'],
                $lote_o['planta_origen'],
                $lote_o['id_empacador'],
                $destino,
                $lote_o['codigo_vitad'],
                $lote_o['embalaje'],
                $cant_mov
            );
            $stmt->execute();
            $stmt->close();
        }

        /* 6) Actualizar/eliminar origen */
        $restante = $cant_actual - $cant_mov;

        if ($restante > 0) {
            $stmt = $conn->prepare("UPDATE lotes SET cantidad_total = ? WHERE id_lote = ? AND cantidad_total = ?");
            $stmt->bind_param("iii", $restante, $id_lote_origen, $cant_actual);
            $stmt->execute();
            $stmt->close();
            $_SESSION['mensaje'] = "✅ Traslado aceptado. Restan $restante unidades.";
        } else {
            // Verificar otros pendientes
            $stmt = $conn->prepare("SELECT COUNT(*) AS p FROM movimientos WHERE id_lote = ? AND estado_aceptacion='pendiente' AND id_movimiento <> ?");
            $stmt->bind_param("ii", $id_lote_origen, $id_movimiento);
            $stmt->execute();
            $pend = (int)$stmt->get_result()->fetch_assoc()['p'];
            $stmt->close();

            if ($pend == 0) {
                $stmt = $conn->prepare("DELETE FROM lotes WHERE id_lote = ? AND cantidad_total = ?");
                $stmt->bind_param("ii", $id_lote_origen, $cant_actual);
                $stmt->execute();
                $stmt->close();
                $_SESSION['mensaje'] = "✅ Traslado aceptado. Lote completamente trasladado.";
            } else {
                $stmt = $conn->prepare("UPDATE lotes SET cantidad_total = 0 WHERE id_lote = ? AND cantidad_total = ?");
                $stmt->bind_param("ii", $id_lote_origen, $cant_actual);
                $stmt->execute();
                $stmt->close();
                $_SESSION['mensaje'] = "✅ Traslado aceptado. Lote agotado pero hay $pend traslados pendientes.";
            }
        }

    } else { // RECHAZAR
        $stmt = $conn->prepare("UPDATE movimientos SET estado_aceptacion='rechazado', fecha_aceptacion = NOW() WHERE id_movimiento = ?");
        $stmt->bind_param("i", $id_movimiento);
        $stmt->execute();
        $stmt->close();
        $_SESSION['mensaje'] = "❌ Traslado rechazado.";
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['mensaje'] = "❌ ERROR: " . $e->getMessage();
} finally {
    $conn->autocommit(true);
}

/* Redirección por rol */
switch ($rol) {
    case 'cedi':        header("Location: cedi.php");       break;
    case 'almacen':     header("Location: almacen.php");    break;
    case 'inventario':  header("Location: inventario.php"); break;
    case 'empaque':     header("Location: empacador.php");  break;
    default:            header("Location: index.php");      break;
}
exit();
