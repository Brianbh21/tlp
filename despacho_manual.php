<?php
// tlp/despacho_manual.php

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/includes/db.php';

// 1) Validar sesión y rol
if (!isset($_SESSION['cedula'], $_SESSION['nombre'], $_SESSION['placa'])
    || $_SESSION['rol'] !== 'conductor') {
    die("Sesión no válida. <a href='conductor.php'>Volver</a>");
}

$nombre = $_SESSION['nombre'];
$placa  = $_SESSION['placa'];

// 2) Si vinimos por POST, procesar el despacho
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $factura = trim($_POST['factura'] ?? '');
    $sel     = $_POST['seleccionados'] ?? [];
    $cant    = $_POST['cantidad']     ?? [];

    if ($factura === '') {
        die("Debes ingresar número de factura. <a href='despacho_manual.php'>Volver</a>");
    }
    if (empty($sel)) {
        die("No seleccionaste ningún lote. <a href='despacho_manual.php'>Volver</a>");
    }

    foreach ($sel as $numero_lote) {
        $qty = intval($cant[$numero_lote] ?? 0);
        if ($qty <= 0) continue;

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
        if (!$lote || $qty > $lote['cantidad_total']) continue;

        $id_lote       = $lote['id_lote'];
        $tipo_producto = $lote['tipo_producto'];
        $rest          = $lote['cantidad_total'] - $qty;

        if ($rest > 0) {
            $u = $conn->prepare("UPDATE lotes SET cantidad_total = ? WHERE id_lote = ?");
            $u->bind_param("ii", $rest, $id_lote);
            $u->execute();
            $u->close();
        } else {
            $d = $conn->prepare("DELETE FROM lotes WHERE id_lote = ?");
            $d->bind_param("i", $id_lote);
            $d->execute();
            $d->close();
        }

        $i = $conn->prepare("
            INSERT INTO despachos
              (numero_lote, tipo_producto, cantidad, numero_factura,
               fecha_despacho, nombre_conductor, estado)
            VALUES
              (?, ?, ?, ?, NOW(), ?, ?)
        ");
        $i->bind_param('ssisss',
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

    // Redirect con flag de éxito
    header("Location: despacho_manual.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Despacho Manual</title>
  <link rel="stylesheet" href="css/global.css">
  <style>
    .container { max-width:800px; margin:40px auto; }
    table { width:100%; border-collapse:collapse; margin-top:16px; }
    th { background:#D62828; color:#fff; padding:8px; }
    td { padding:8px; border:1px solid #ccc; text-align:center; }
    input[type=number] { width:60px; }
    .btn { display:inline-block; padding:10px 16px; background:#D62828; color:#fff; border:none; border-radius:6px; cursor:pointer; margin:10px 0; text-decoration:none; }
    .btn:hover { background:#B81F1F; }
  </style>

  <?php if (isset($_GET['success'])): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      alert('🚛 ¡Despacho realizado con éxito!');
    });
  </script>
  <?php endif; ?>
</head>
<body>
  <div class="container">
    <h2>Despacho Manual – Lotes asignados a <?= htmlspecialchars($placa) ?></h2>

    <form action="despacho_manual.php" method="POST">
      <label for="factura">Número de Factura:</label>
      <input type="text" name="factura" id="factura" required class="input">
      <br><br>

      <table>
        <thead>
          <tr>
            <th>Seleccionar</th>
            <th>Lote</th>
            <th>Producto</th>
            <th>Empaque</th>
            <th>Vencimiento</th>
            <th>Disponible</th>
            <th>Despachar</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $stmt = $conn->prepare("
            SELECT numero_lote, tipo_producto, fecha_empaque, fecha_vencimiento, cantidad_total
              FROM lotes
             WHERE estado = ?
               AND cantidad_total >= 0
          ");
          $stmt->bind_param("s", $placa);
          $stmt->execute();
          $res = $stmt->get_result();
          while ($r = $res->fetch_assoc()):
            $nl = htmlspecialchars($r['numero_lote']);
            $tp = htmlspecialchars($r['tipo_producto']);
            $ce = $r['fecha_empaque'];
            $fv = $r['fecha_vencimiento'];
            $ct = (int)$r['cantidad_total'];
        ?>
          <tr>
            <td><input type="checkbox" name="seleccionados[]" value="<?= $nl ?>"></td>
            <td><?= $nl ?></td>
            <td><?= $tp ?></td>
            <td><?= $ce ?></td>
            <td><?= $fv ?></td>
            <td><?= $ct ?></td>
            <td><input type="number" name="cantidad[<?= $nl ?>]" min="1" max="<?= $ct ?>" required></td>
          </tr>
        <?php endwhile; $stmt->close(); ?>
        </tbody>
      </table>

      <button type="submit" class="btn">🚛 Despachar lote(s)</button>
    </form>

    <a href="conductor.php" class="btn">↩️ Volver</a>
  </div>
</body>
</html>
