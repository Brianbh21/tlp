<?php /* tlp/views/cedi_view.php */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de CEDI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./css/global.css">
  <style>
    .wrap { max-width: 980px; margin: 28px auto; padding: 0 12px; }
    .card { background: #fff; border-radius: 14px; box-shadow: 0 6px 18px rgba(0,0,0,.08); padding: 22px; }
    .btn { display:block; width:100%; margin:10px 0; padding:14px 18px; border-radius:10px;
           background:#d62828; color:#fff; text-align:center; text-decoration:none; font-weight:600; }
    .btn:hover{ background:#b81f1f; }
    .btn-outline { background:#f3f3f3; color:#333; }
    .grid-btns{ max-width:520px; margin:0 auto; }
    .badge { display:inline-block; min-width:22px; padding:2px 7px; border-radius:999px; background:#ffc107; color:#111; font-weight:700; margin-left:6px; }
    table { width:100%; border-collapse: collapse; margin-top:10px;}
    th,td{ padding:10px 8px; border-bottom:1px solid #eee; text-align:center; }
    th{ background:#fbe9e9; color:#b11818; }
    .actions{ display:flex; gap:6px; justify-content:center; flex-wrap:wrap; }
    .ok{ background:#28a745; } .ok:hover{ background:#218838; }
    .no{ background:#dc3545; } .no:hover{ background:#b22d3a; }
    @media (max-width:680px){
      th:nth-child(2), td:nth-child(2) { display:none; } /* oculta id_lote en móvil para compactar */
    }
  </style>
</head>
<body>
  <div class="wrap">

    <?php if ($mensaje !== ''): ?>
      <div class="card" style="margin-bottom:14px;background:#fff3cd;color:#7a5a00">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <!-- Bloque de Traslados pendientes -->
    <div class="card" style="margin-bottom:20px;">
      <h2 style="margin:0 0 10px;color:#c01818;text-align:center;">Traslados pendientes</h2>

      <?php if (!empty($traslados_pendientes)): ?>
        <div style="overflow-x:auto;">
          <table>
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
            <?php foreach ($traslados_pendientes as $t): ?>
              <tr>
                <td><?= (int)$t['id_movimiento'] ?></td>
                <td><?= (int)$t['id_lote'] ?></td>
                <td><?= htmlspecialchars($t['tipo_producto'] ?? 'No definido') ?></td>
                <td><?= (int)$t['cantidad'] ?></td>
                <td><?= htmlspecialchars($t['estado_origen'] ?: $t['origen']) ?></td>
                <td><?= htmlspecialchars($t['fecha_movimiento']) ?></td>
                <td class="actions">
                  <form action="./includes/aceptar_rechazar_logic.php" method="POST">
                    <input type="hidden" name="id_traslado" value="<?= (int)$t['id_movimiento'] ?>">
                    <button type="submit" name="accion" value="aceptar" class="btn ok" style="padding:8px 12px;">✔ Aceptar</button>
                  </form>
                  <form action="./includes/aceptar_rechazar_logic.php" method="POST">
                    <input type="hidden" name="id_traslado" value="<?= (int)$t['id_movimiento'] ?>">
                    <button type="submit" name="accion" value="rechazar" class="btn no" style="padding:8px 12px;">✖ Rechazar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div style="text-align:center;color:#666;">No hay traslados pendientes en CEDI.</div>
      <?php endif; ?>
    </div>

    <!-- Panel clásico -->
    <div class="card grid-btns">
      <h2 style="text-align:center;color:#c01818;margin-top:0;">Panel del CEDI</h2>
      <a class="btn" href="traslado_lote.php">🧾 Traslado Manual</a>
      <a class="btn" href="traslado_camara.php">📷 Traslado por Cámara</a>
      <a class="btn" href="ver_lotes.php">🖨️ Imprimir Lotes</a>
      <a class="btn" href="movimientos.php">📦 Ver Movimientos</a>
      <a class="btn" href="fechas_cercanas.php">📅 Fechas Próximas</a>
      <a class="btn btn-outline" href="cerrar_sesion.php">🔒 Cerrar Sesión</a>
    </div>

    <div style="text-align:right;color:#666;margin-top:10px;">
      Bienvenido, <?= htmlspecialchars($nombre) ?> (CEDI)
    </div>
  </div>
</body>
</html>
