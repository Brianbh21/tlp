<?php
// tlp/almacen.php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'almacen') {
  header("Location: index.php");
  exit();
}

$nombre = $_SESSION['nombre'] ?? 'usuario';

// mensaje flash
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

// traslados pendientes para ALMACÉN
$sql = "
  SELECT 
    m.id_movimiento, m.id_lote, m.tipo_producto, m.cantidad, m.origen, m.fecha_movimiento,
    l.numero_lote
  FROM movimientos m
  LEFT JOIN lotes l ON l.id_lote = m.id_lote
  WHERE m.estado_destino = 'almacen'
    AND m.estado_aceptacion = 'pendiente'
  ORDER BY m.id_movimiento DESC
";
$pend = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Panel Almacén</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./css/global.css" />
  <style>
    .wrap { max-width: 980px; margin: 20px auto; padding: 0 12px; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 6px 16px rgba(0,0,0,.08); padding: 14px 16px; margin-bottom: 18px; }
    .title { text-align:center; color:#c0161a; margin:8px 0 14px; }
    .table { width:100%; border-collapse:collapse; }
    .table th, .table td { padding:10px; border-bottom:1px solid #eee; text-align:center; }
    .table th { background:#ffe08a; }
    .btn-acc { background:#28a745; color:#fff; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; }
    .btn-rej { background:#dc3545; color:#fff; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; }
    .btn-acc:hover { opacity:.9; } .btn-rej:hover { opacity:.9; }
    .grid-actions { display:grid; gap:12px; max-width:460px; margin: 10px auto 0; }
    @media (min-width:520px){ .grid-actions { grid-template-columns: 1fr; } }
    .cta { display:block; text-align:center; background:#d62828; color:#fff; padding:12px; border-radius:10px; text-decoration:none; }
    .cta:hover { background:#b81f1f; }
    .muted { color:#666; }
    .flash { padding:10px 12px; border-radius:8px; margin:12px 0; font-weight:600; }
    .flash.ok { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .flash.err{ background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
    .top-right { text-align:right; font-size:14px; color:#666; margin-bottom:6px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top-right">Bienvenido, <?= htmlspecialchars($nombre) ?> (ALMACÉN)</div>

    <?php if ($mensaje): ?>
      <div class="flash <?= (strpos($mensaje,'✅')!==false?'ok':'err') ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="card">
      <h2 class="title">Traslados pendientes</h2>

      <?php if (!$pend): ?>
        <p class="muted" style="text-align:center;">No hay traslados pendientes en Almacén.</p>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>ID Lote</th>
                <th>Producto</th>
                <th>Unid.</th>
                <th>Origen</th>
                <th>Fecha</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pend as $i => $t): ?>
                <tr>
                  <td><?= (int)$t['id_movimiento'] ?></td>
                  <td><?= (int)$t['id_lote'] ?></td>
                  <td><?= htmlspecialchars($t['tipo_producto'] ?? 'No definido') ?></td>
                  <td><?= (int)$t['cantidad'] ?></td>
                  <td><?= htmlspecialchars($t['origen'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($t['fecha_movimiento']) ?></td>
                  <td>
                    <form action="includes/aceptar_rechazar_logic.php" method="POST" style="display:inline;">
                      <input type="hidden" name="id_movimiento" value="<?= (int)$t['id_movimiento'] ?>">
                      <button type="submit" name="accion" value="aceptar" class="btn-acc">✔ Aceptar</button>
                    </form>
                    <form action="includes/aceptar_rechazar_logic.php" method="POST" style="display:inline;margin-left:6px;">
                      <input type="hidden" name="id_movimiento" value="<?= (int)$t['id_movimiento'] ?>">
                      <button type="submit" name="accion" value="rechazar" class="btn-rej">✖ Rechazar</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2 class="title">Panel de Almacén</h2>
      <div class="grid-actions">
        <a class="cta" href="traslado_lote.php">🚚 Traslado Manual</a>
        <a class="cta" href="traslado_camara.php">📷 Traslado por Cámara</a>
        <a class="cta" href="ver_lotes.php">🖨️ Imprimir Lotes</a>
        <a class="cta" href="movimientos.php">📦 Ver Movimientos</a>
        <a class="cta" href="fechas_cercanas.php">📅 Fechas Próximas</a>
        <a class="cta" href="cerrar_sesion.php" style="background:#99051a;">🔒 Cerrar Sesión</a>
      </div>
    </div>
  </div>
</body>
</html>
