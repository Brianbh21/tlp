<?php
// tlp/views/conductor_view.php
$nombre    = $GLOBALS['nombre']    ?? 'Conductor';
$mensaje   = $GLOBALS['mensaje']   ?? '';
$placa     = $GLOBALS['placa']     ?? '';
$placas    = $GLOBALS['placas']    ?? []; // << listado completo
$traslados = $GLOBALS['traslados'] ?? [];
$cnt       = $GLOBALS['cnt']       ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel del Conductor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./css/global.css">
  <style>
    .wrap{max-width:980px;margin:20px auto;padding:0 12px}
    .card{background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:18px;margin-bottom:20px}
    .title{font-weight:700;text-align:center;color:#c1121f;margin:6px 0 14px}
    .toolbar{display:flex;flex-direction:column;gap:12px;max-width:420px;margin:10px auto}
    .btn{display:block;width:100%;text-align:center;border:none;border-radius:10px;padding:12px 14px;color:#fff;background:#d62828;cursor:pointer;text-decoration:none}
    .btn:hover{background:#b81f1f}
    .btn-gray{background:#6c757d;color:#fff;text-decoration:none}.btn-gray:hover{background:#5a6268}
    .flash{padding:10px 12px;border-radius:8px;margin:12px 0;font-weight:600}
    .ok{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
    .err{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:center}
    th{background:#f7f7f7;font-weight:600}
    .badge{display:inline-block;padding:3px 8px;border-radius:12px;background:#eee}
    @media (max-width:640px){ th:nth-child(1),td:nth-child(1){display:none} }
    .select{width:100%;max-width:420px;padding:10px;border:1px solid #ccc;border-radius:8px}
  </style>
</head>
<body>
  <div class="wrap">

    <?php if ($mensaje): ?>
      <div class="flash <?= (strpos($mensaje,'✅')!==false ? 'ok' : 'err') ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <?php if ($placa === ''): ?>
      <!-- SIN PLACA: mostrar SELECT con TODAS las placas -->
      <div class="card">
        <h2 class="title">Seleccionar Placa</h2>
        <?php if (empty($placas)): ?>
          <p style="text-align:center;color:#666">No hay placas registradas.</p>
        <?php else: ?>
          <form method="POST" action="conductor.php" style="text-align:center;">
            <input type="hidden" name="action" value="set_placa">
            <select name="placa" class="select" required>
              <option value="">-- Selecciona una placa --</option>
              <?php foreach ($placas as $p): ?>
                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
              <?php endforeach; ?>
            </select>
            <div style="margin-top:12px;">
              <button type="submit" class="btn">Guardar y continuar</button>
            </div>
          </form>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2 class="title">Panel del Conductor</h2>
        <div class="toolbar">
          <a class="btn-gray" href="cerrar_sesion.php">🔒 Cerrar Sesión</a>
        </div>
      </div>

    <?php else: ?>
      <!-- CON PLACA: panel tipo CEDI/Almacén -->
      <div class="card">
        <h2 class="title">Traslados pendientes</h2>
        <?php if (empty($traslados)): ?>
          <p style="text-align:center;color:#666">No tienes traslados pendientes para la placa <strong><?= htmlspecialchars($placa) ?></strong>.</p>
        <?php else: ?>
          <div style="overflow:auto">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Lote</th>
                  <th>Producto</th>
                  <th>Unid.</th>
                  <th>Origen</th>
                  <th>Fecha</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($traslados as $t): ?>
                  <tr>
                    <td><?= (int)$t['id_movimiento'] ?></td>
                    <td><?= htmlspecialchars($t['numero_lote'] ?? (string)$t['id_lote']) ?></td>
                    <td><?= htmlspecialchars($t['tipo_producto'] ?? 'No definido') ?></td>
                    <td><span class="badge"><?= (int)$t['cantidad'] ?></span></td>
                    <td><?= htmlspecialchars($t['origen'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($t['fecha_movimiento']) ?></td>
                    <td>
                      <form action="includes/aceptar_rechazar_logic.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id_movimiento" value="<?= (int)$t['id_movimiento'] ?>">
                        <button class="btn" type="submit" name="accion" value="aceptar">✔ Aceptar</button>
                      </form>
                      <form action="includes/aceptar_rechazar_logic.php" method="POST" style="display:inline;margin-left:6px;">
                        <input type="hidden" name="id_movimiento" value="<?= (int)$t['id_movimiento'] ?>">
                        <button class="btn-gray" type="submit" name="accion" value="rechazar">✖ Rechazar</button>
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
        <h2 class="title">Panel del Conductor</h2>
        <div class="toolbar">
          <!-- redirecciones las maneja conductor.php (view=...) -->
          <a class="btn" href="conductor.php?view=traslados">📋 Traslados<?= $cnt>0 ? '<span class="badge">'.$cnt.'</span>' : '' ?></a>
          <a class="btn" href="conductor.php?view=despachar">🧾 Despachar</a>
          <a class="btn" href="conductor.php?view=inventario">📦 Inventario</a>
          <a class="btn-gray" href="cerrar_sesion.php">🔒 Cerrar Sesión</a>
        </div>
        <div style="text-align:right;color:#666;margin-top:10px">
          Bienvenido, <?= htmlspecialchars($nombre) ?> (CONDUCTOR) — Placa: <strong><?= htmlspecialchars($placa) ?></strong>
        </div>
      </div>
    <?php endif; ?>

  </div>
</body>
</html>
