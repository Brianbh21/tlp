<?php
// /var/www/html/tlp/includes/aceptar_rechazar_logic.php

// Mostrar errores SQL y de PHP
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Conexión a la base (db.php está un nivel arriba)
require_once __DIR__ . '/../db.php';

// Solo vía POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /tlp/index.php");
    exit();
}

$id_mov = $_POST['id_traslado'] ?? null;
$accion = $_POST['accion']      ?? null;

if (!$id_mov || !$accion) {
    die("Error: Datos incompletos.");
}

// Iniciar transacción
$conn->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
$conn->autocommit(false);
$conn->begin_transaction();

try {
    // 1) Bloquear y leer el movimiento pendiente
    $stmt = $conn->prepare("
      SELECT * 
        FROM movimientos 
       WHERE id_movimiento = ? 
         AND estado_aceptacion = 'pendiente'
       FOR UPDATE
    ");
    $stmt->bind_param("i", $id_mov);
    $stmt->execute();
    $mov = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$mov) {
        throw new Exception("Movimiento no encontrado o ya procesado.");
    }

    // 1.a) Validar que el usuario tenga permiso según su rol = destino
    $rol_usuario  = strtolower($_SESSION['rol'] ?? '');
    $destino_mov  = strtolower($mov['destino']);
    if ($rol_usuario !== $destino_mov) {
        throw new Exception("Acceso denegado. Debes iniciar sesión como " . strtoupper($mov['destino']) . ".");
    }

    $lote_id   = (int)$mov['id_lote'];
    $cant_mov  = (int)$mov['cantidad'];
    $destino   = $mov['destino'];

    // 2) Bloquear y leer el lote origen
    $stmt = $conn->prepare("SELECT * FROM lotes WHERE id_lote = ? FOR UPDATE");
    $stmt->bind_param("i", $lote_id);
    $stmt->execute();
    $lote = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$lote) {
        throw new Exception("Lote origen no encontrado.");
    }

    $cant_act = (int)$lote['cantidad_total'];
    $num_lote = $lote['numero_lote'];
    $tp       = $lote['tipo_producto'];

    if ($accion === 'aceptar') {
        // Validaciones
        if ($cant_mov > $cant_act) {
            throw new Exception("Inventario insuficiente. Disponible: $cant_act.");
        }
        if ($cant_act <= 0) {
            throw new Exception("Lote sin inventario.");
        }

        // 3) Marcar movimiento como aceptado
        $stmt = $conn->prepare("
          UPDATE movimientos
             SET estado_aceptacion = 'aceptado'
           WHERE id_movimiento = ?
        ");
        $stmt->bind_param("i", $id_mov);
        $stmt->execute();
        $stmt->close();

        // 4) Actualizar o crear lote destino
        $stmt = $conn->prepare("
          SELECT id_lote
            FROM lotes
           WHERE numero_lote = ?
             AND tipo_producto = ?
             AND estado = ?
           FOR UPDATE
        ");
        $stmt->bind_param("sss", $num_lote, $tp, $destino);
        $stmt->execute();
        $destRes = $stmt->get_result();
        $stmt->close();

        if ($destRes->num_rows > 0) {
            $id_dest = (int)$destRes->fetch_assoc()['id_lote'];
            $stmt = $conn->prepare("
              UPDATE lotes
                 SET cantidad_total = cantidad_total + ?
               WHERE id_lote = ?
            ");
            $stmt->bind_param("ii", $cant_mov, $id_dest);
            $stmt->execute();
            $stmt->close();
        } else {
            // Crear nuevo lote destino (uso correcto de 'codigo_vitad')
            $stmt = $conn->prepare("
              INSERT INTO lotes
              (numero_lote, tipo_producto, fecha_empaque, fecha_vencimiento,
               cantidad_total, qr_codigo, planta_origen, id_empacador,
               estado, codigo_vitad, embalaje, cantidad_origen)
              VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
              "ssssiissssii",
              $lote['numero_lote'],
              $lote['tipo_producto'],
              $lote['fecha_empaque'],
              $lote['fecha_vencimiento'],
              $cant_mov,
              $lote['qr_codigo'],
              $lote['planta_origen'],
              $lote['id_empacador'],
              $destino,
              $lote['codigo_vitad'],
              $lote['embalaje'],
              $cant_mov
            );
            $stmt->execute();
            $stmt->close();
        }

        // 5) Restar o eliminar lote origen
        $rest = $cant_act - $cant_mov;
        if ($rest > 0) {
            $stmt = $conn->prepare("
              UPDATE lotes
                 SET cantidad_total = ?
               WHERE id_lote = ?
            ");
            $stmt->bind_param("ii", $rest, $lote_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['mensaje'] = "✅ Traslado aceptado. Quedan $rest unidades.";
        } else {
            // Ver si quedan traslados pendientes
            $stmt = $conn->prepare("
              SELECT COUNT(*) AS p
                FROM movimientos
               WHERE id_lote = ?
                 AND estado_aceptacion = 'pendiente'
                 AND id_movimiento <> ?
            ");
            $stmt->bind_param("ii", $lote_id, $id_mov);
            $stmt->execute();
            $pend = (int)$stmt->get_result()->fetch_assoc()['p'];
            $stmt->close();

            if ($pend === 0) {
                $stmt = $conn->prepare("DELETE FROM lotes WHERE id_lote = ?");
                $stmt->bind_param("i", $lote_id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['mensaje'] = "✅ Traslado aceptado. Lote eliminado.";
            } else {
                $_SESSION['mensaje'] = "✅ Traslado aceptado. Lote agotado pero quedan $pend traslados pendientes.";
            }
        }

    } else {
        // Rechazar
        $stmt = $conn->prepare("
          UPDATE movimientos
             SET estado_aceptacion = 'rechazado'
           WHERE id_movimiento = ?
        ");
        $stmt->bind_param("i", $id_mov);
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

// 6) Redirigir según rol del usuario
switch (strtolower($_SESSION['rol'] ?? '')) {
    case 'cedi':
        header("Location: /tlp/cedi.php");
        break;
    case 'almacen':
        header("Location: /tlp/almacen.php");
        break;
    case 'inventario':
        header("Location: /tlp/inventario.php");
        break;
    case 'empaque':
        header("Location: /tlp/empacador.php");
        break;
    case 'conductor':  // cuando implementes conductores
        header("Location: /tlp/conductor.php");
        break;
    default:
        header("Location: /tlp/index.php");
        break;
}
exit();
