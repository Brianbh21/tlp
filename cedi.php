<?php
// tlp/cedi.php
session_start();
if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'cedi') {
  header('Location: index.php');
  exit;
}
require_once __DIR__ . '/includes/db.php';

$nombre = $_SESSION['nombre'] ?? 'usuario';

// Traer traslados pendientes para CEDI
$stmt = $conn->prepare("
  SELECT m.id_movimiento,
         m.id_lote,
         m.cantidad,
         m.estado_origen AS origen,
         m.fecha_movimiento,
         COALESCE(l.tipo_producto,'No definido') AS tipo_producto
    FROM movimientos m
    LEFT JOIN lotes l ON l.id_lote = m.id_lote
   WHERE m.estado_destino = 'CEDI'
     AND m.estado_aceptacion = 'pendiente'
   ORDER BY m.fecha_movimiento DESC
");
$stmt->execute();
$pend = $stmt->get_result();
$traslados = $pend->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel del CEDI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./css/global.css">
  <style>
    .wrap{max-width:980px;margin:20px auto;padding:0 12px}
    .card{background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:18px;margin-bottom:20px}
    .title{font-weight:700;text-align:center;color:#c1121f;margin:6px 0 14px}
    .toolbar{display:flex;flex-direction:column;gap:12px;max-width:420px;margin:10px auto}
    .btn{display:block;width:100%;text-align:center;border:none;border-radius:10px;padding:12px 14px;color:#fff;background:#d62828;cursor:pointer}
    .btn:hover{background:#b81f1f}
    .btn-green{background:#2e7d32}.btn-green:hover{background:#1f5b23}
    .btn-gray{background:#6c757d}.btn-gray:hover{background:#5a6268}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:center}
    th{background:#f7f7f7;font-weight:600}
    .badge{display:inline-block;padding:3px 8px;border-radius:12px;background:#eee}
    @media (max-width:640px){
      th:nth-child(1),td:nth-child(1){display:none} /* oculta # */
    }
  </style>
</head>
<body>
  <div class="wrap">

    <?php if ($mensaje): ?>
      <div class="card" style="background:#fff3cd;color:#856404;border:1px solid #ffeeba">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2 class="title">Traslados pendientes</h2>
      <?php if (empty($traslados)): ?>
        <p style="text-align:center;color:#666;margin:10px 0">No hay traslados pendientes en CEDI.</p>
      <?php else: ?>
        <div style="overflow:auto">
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
              <?php foreach ($traslados as $t): ?>
                <tr>
                  <td><?= (int)$t['id_movimiento'] ?></td>
                  <td><?= (int)$t['id_lote'] ?></td>
                  <td><?= htmlspecialchars($t['tipo_producto']) ?></td>
                  <td><span class="badge"><?= (int)$t['cantidad'] ?></span></td>
                  <td><?= htmlspecialchars($t['origen']) ?></td>
                  <td><?= htmlspecialchars($t['fecha_movimiento']) ?></td>
                  <td>
                    <form action="includes/aceptar_rechazar_logic.php" method="POST" style="display:flex;gap:8px;justify-content:center">
                      <input type="hidden" name="id_movimiento" value="<?= (int)$t['id_movimiento'] ?>">
                      <button class="btn btn-green"   type="submit" name="accion" value="aceptar">✔ Aceptar</button>
                      <button class="btn btn-gray"    type="submit" name="accion" value="rechazar">✖ Rechazar</button>
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
      <h2 class="title">Panel del CEDI</h2>
      <div class="toolbar">
        <a class="btn" href="traslado_lote.php">🚚 Traslado Manual</a>
        <a class="btn" href="traslado_camara.php">📷 Traslado por Cámara</a>
        <a class="btn" href="ver_lotes.php">🖨️ Imprimir Lotes</a>
        <a class="btn" href="movimientos.php">📦 Ver Movimientos</a>
        <a class="btn" href="fechas_cercanas.php">📅 Fechas Próximas</a>
        <a class="btn" style="background:#9d0b0b" href="cerrar_sesion.php">🔒 Cerrar Sesión</a>
      </div>
      <div style="text-align:right;color:#666;margin-top:10px">
        Bienvenido, <?= htmlspecialchars($nombre) ?> (CEDI)
      </div>
    </div>
  </div>
</body>
</html>
