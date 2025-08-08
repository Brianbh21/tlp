<?php
// tlp/includes/aceptar_rechazar_logic.php

// Mostrar errores SQL y de PHP (útil mientras depuras)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php'; // db.php está en /tlp/includes/

// -- Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /tlp/index.php");
  exit();
}

// -- Validar sesión/rol
if (!isset($_SESSION['rol'])) {
  header("Location: /tlp/index.php");
  exit();
}
$rol = $_SESSION['rol'];

// -- Mapear destino según rol (conductor usa placa)
switch ($rol) {
  case 'conductor':
    if (empty($_SESSION['placa'])) {
      header("Location: /tlp/conductor.php");
      exit();
    }
    $destinoDelUsuario = $_SESSION['placa']; // EJ: "TTY535"
    break;
  case 'almacen':
    $destinoDelUsuario = 'almacen';
    break;
  case 'cedi':
    $destinoDelUsuario = 'CEDI';
    break;
  case 'inventario':
    $destinoDelUsuario = 'inventario';
    break;
  case 'empaque':
    $destinoDelUsuario = 'empaque';
    break;
  default:
    header("Location: /tlp/index.php");
    exit();
}

// -- Normalizar parámetros del formulario (admite ambos nombres)
$id_raw = $_POST['id_movimiento'] ?? $_POST['id_traslado'] ?? null;
$accion = $_POST['accion'] ?? null;

if (!$id_raw || !$accion) {
  die("Error: Datos incompletos.");
}
$id_mov = (int)$id_raw;
$estado_acept = ($accion === 'aceptar') ? 'aceptado' : 'rechazado';

// ====== TRANSACCIÓN ======
$conn->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
$conn->autocommit(false);
$conn->begin_transaction();

try {
  // 1) Bloquear y leer el movimiento pendiente
  $stmt = $conn->prepare("
    SELECT *
      FROM movimientos
     WHERE id_movimiento     = ?
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

  // Validar que el usuario correcto recibe (comparar con estado_destino para soportar placas)
  $destinoMovimiento = (string)$mov['estado_destino']; // puede ser 'CEDI', 'almacen', 'inventario' o una PLACA
  if (strtolower($destinoMovimiento) !== strtolower($destinoDelUsuario)) {
    throw new Exception("Acceso denegado. Este traslado pertenece a '{$mov['estado_destino']}'.");
  }

  // Marcar como aceptado/rechazado
  $up = $conn->prepare("
    UPDATE movimientos
       SET estado_aceptacion = ?
     WHERE id_movimiento = ?
  ");
  $up->bind_param("si", $estado_acept, $id_mov);
  $up->execute();
  $up->close();

  // Si es rechazado, no tocamos inventarios
  if ($estado_acept === 'rechazado') {
    $_SESSION['mensaje'] = "❌ Traslado rechazado.";
    $conn->commit();
    $conn->autocommit(true);
    // Redirección al final
  } else {
    // ====== ACEPTADO: mover inventarios ======
    $lote_id   = (int)$mov['id_lote'];
    $cant_mov  = (int)$mov['cantidad'];
    $origen    = (string)$mov['estado_origen'];  // estado origen real
    $destino   = (string)$mov['estado_destino']; // destino final (incluye placas)

    // 2) Bloquear el lote origen
    $s = $conn->prepare("
      SELECT id_lote, numero_lote, tipo_producto, cantidad_total,
             qr_codigo, planta_origen, id_empacador,
             fecha_empaque, fecha_vencimiento, codigo_vitad, embalaje
        FROM lotes
       WHERE id_lote = ?
       FOR UPDATE
    ");
    $s->bind_param("i", $lote_id);
    $s->execute();
    $lote = $s->get_result()->fetch_assoc();
    $s->close();

    if (!$lote) {
      throw new Exception("Lote origen no encontrado.");
    }

    $cant_act = (int)$lote['cantidad_total'];
    if ($cant_mov > $cant_act) {
      throw new Exception("Inventario insuficiente en origen. Disponible: {$cant_act}.");
    }

    // 3) Restar/eliminar origen
    $rest = $cant_act - $cant_mov;
    if ($rest > 0) {
      $q = $conn->prepare("UPDATE lotes SET cantidad_total = ? WHERE id_lote = ?");
      $q->bind_param("ii", $rest, $lote_id);
      $q->execute();
      $q->close();
    } else {
      $q = $conn->prepare("DELETE FROM lotes WHERE id_lote = ?");
      $q->bind_param("i", $lote_id);
      $q->execute();
      $q->close();
    }

    // 4) Sumar/crear en destino
    $q2 = $conn->prepare("
      SELECT id_lote, cantidad_total
        FROM lotes
       WHERE numero_lote = ?
         AND estado      = ?
       FOR UPDATE
    ");
    $q2->bind_param("ss", $lote['numero_lote'], $destino);
    $q2->execute();
    $ex = $q2->get_result()->fetch_assoc();
    $q2->close();

    if ($ex) {
      $nuevo = (int)$ex['cantidad_total'] + $cant_mov;
      $q3 = $conn->prepare("UPDATE lotes SET cantidad_total = ? WHERE id_lote = ?");
      $q3->bind_param("ii", $nuevo, $ex['id_lote']);
      $q3->execute();
      $q3->close();
    } else {
      $q4 = $conn->prepare("
        INSERT INTO lotes
          (numero_lote, tipo_producto, fecha_empaque, fecha_vencimiento,
           cantidad_total, qr_codigo, planta_origen, id_empacador,
           estado, codigo_vitad, embalaje, cantidad_origen)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
      ");
      $q4->bind_param(
        "ssssisisssii",
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
      $q4->execute();
      $q4->close();
    }

    $_SESSION['mensaje'] = "✅ Traslado aceptado.";
    $conn->commit();
    $conn->autocommit(true);
  }

} catch (Exception $e) {
  $conn->rollback();
  $conn->autocommit(true);
  $_SESSION['mensaje'] = "❌ ERROR: " . $e->getMessage();
}

// -- Redirigir según rol
switch ($rol) {
  case 'conductor':
    header("Location: /tlp/conductor.php?view=traslados");
    break;
  case 'almacen':
    header("Location: /tlp/almacen.php");
    break;
  case 'cedi':
    header("Location: /tlp/cedi.php");
    break;
  case 'inventario':
    header("Location: /tlp/inventario.php");
    break;
  case 'empaque':
    header("Location: /tlp/empacador.php");
    break;
  default:
    header("Location: /tlp/index.php");
    break;
}
exit;
