<?php
// Solo arranca sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión
require __DIR__ . '/db.php';

// 1) Validar sesión y rol
if (
    !isset($_SESSION['cedula'], $_SESSION['nombre'], $_SESSION['placa'])
    || $_SESSION['rol'] !== 'conductor'
) {
    header('Location: ../conductor.php');
    exit;
}

$cedula  = $_SESSION['cedula'];
$nombre  = $_SESSION['nombre'];
$placa   = $_SESSION['placa'];
$mensaje = '';

// 2) Si vinimos por POST, procesar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $factura = trim($_POST['factura'] ?? '');
    $sel     = $_POST['seleccionados'] ?? [];
    $cant    = $_POST['cantidad']     ?? [];

    if ($factura === '' || empty($sel)) {
        $mensaje = '❌ Ingresa factura y selecciona al menos un lote.';
    } else {
        foreach ($sel as $numero_lote) {
            $qty = intval($cant[$numero_lote] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            // 2.1) Traer lote real (cantidad >= 0)
            $stmt = $conn->prepare("
                SELECT id_lote, tipo_producto, cantidad_total
                  FROM lotes
                 WHERE numero_lote   = ?
                   AND estado         = ?
                   AND cantidad_total >= 0
            ");
            $stmt->bind_param("ss", $numero_lote, $placa);
            $stmt->execute();
            $lote = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$lote || $qty > $lote['cantidad_total']) {
                continue;
            }

            $id_lote         = $lote['id_lote'];
            $tipo_producto   = $lote['tipo_producto'];
            $actual          = (int)$lote['cantidad_total'];
            $restante        = $actual - $qty;

            // 2.2) Actualizar o borrar
            if ($restante > 0) {
                $u = $conn->prepare(
                    "UPDATE lotes SET cantidad_total = ? WHERE id_lote = ?"
                );
                $u->bind_param("ii", $restante, $id_lote);
                $u->execute();
                $u->close();
            } else {
                $d = $conn->prepare(
                    "DELETE FROM lotes WHERE id_lote = ?"
                );
                $d->bind_param("i", $id_lote);
                $d->execute();
                $d->close();
            }

            // 2.3) Insertar en despachos
            $i = $conn->prepare("
                INSERT INTO despachos
                  (numero_lote, tipo_producto, cantidad, numero_factura,
                   fecha_despacho, nombre_conductor, estado)
                VALUES
                  (?, ?, ?, ?, NOW(), ?, ?)
            ");
            $i->bind_param(
                "ssisss",
                $numero_lote,
                $tipo_producto,
                $qty,
                $factura,
                $nombre,
                $placa
            );
            $i->execute();
            $i->close();
        }

        $mensaje = '✅ Despacho registrado correctamente.';
    }
}

// 3) Traer inventario para la vista
$lotes = [];
$stmt  = $conn->prepare("
    SELECT numero_lote, tipo_producto, fecha_empaque, fecha_vencimiento, cantidad_total
      FROM lotes
     WHERE estado = ?
       AND cantidad_total >= 0
");
$stmt->bind_param("s", $placa);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $lotes[] = $r;
}
$stmt->close();
