<?php
// views/cedi_view.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de CEDI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/global.css">
</head>
<body>

<?php if ($mensaje !== ''): ?>
  <div style="
    margin:15px;padding:12px;
    background-color: <?= strpos($mensaje,'✅')!==false ? '#d4edda' : '#f8d7da' ?>;
    color:           <?= strpos($mensaje,'✅')!==false ? '#155724' : '#721c24' ?>;
    border:1px solid <?= strpos($mensaje,'✅')!==false ? '#c3e6cb' : '#f5c6cb' ?>;
    border-radius:5px;font-weight:bold;font-size:16px;">
    <?= htmlspecialchars($mensaje) ?>
  </div>
<?php endif; ?>

<div class="usuario-info" style="margin:15px;">
  Bienvenido, <?= htmlspecialchars($nombre) ?> (<?= strtoupper(htmlspecialchars($rol)) ?>)
</div>

<?php if (count($traslados_pendientes) > 0): ?>
  <div style="padding:15px;background:#fff3cd;color:#856404;border:1px solid #ffeeba;margin:15px;border-radius:5px;">
    <h3 style="color:red;margin-top:0;">⚠️ Tienes traslados pendientes</h3>

    <?php foreach ($traslados_pendientes as $t): ?>
      <div style="background:#f9f9f9;border:1px solid #ccc;padding:12px;margin-bottom:12px;border-radius:5px;">
        <p><strong>Producto:</strong> <?= htmlspecialchars($t['tipo_producto'] ?? 'No definido') ?></p>
        <p><strong>N° Lote:</strong> <?= htmlspecialchars($t['numero_lote'] ?? '-') ?></p>
        <p><strong>Cantidad:</strong> <?= (int)$t['cantidad'] ?></p>
        <p><strong>Desde:</strong> <?= htmlspecialchars($t['origen'] ?? '') ?></p>

        <form action="/tlp/includes/aceptar_rechazar_logic.php" method="POST" style="display:flex;gap:10px;margin-top:10px;">
          <input type="hidden" name="id_traslado" value="<?= (int)$t['id_movimiento'] ?>">
          <button type="submit" name="accion" value="aceptar"
            style="background:#28a745;color:#fff;border:none;padding:6px 12px;border-radius:3px;cursor:pointer;">
            ✅ Aceptar
          </button>
          <button type="submit" name="accion" value="rechazar"
            style="background:#dc3545;color:#fff;border:none;padding:6px 12px;border-radius:3px;cursor:pointer;">
            ❌ Rechazar
          </button>
        </form>
      </div>
    <?php endforeach; ?>

  </div>
<?php endif; ?>

<div style="text-align:center;margin-top:30px;">
  <a href="traslado_lote.php"    class="boton-institucional">🚚 Traslado Manual</a>
  <a href="traslado_camara.php"  class="boton-institucional">📷 Traslado por Cámara</a>
  <a href="ver_lotes.php"        class="boton-institucional">🖨️ Imprimir Lotes</a>
  <a href="movimientos.php"      class="boton-institucional">📦 Ver Movimientos</a>
  <a href="fechas_cercanas.php"  class="boton-institucional">📅 Fechas Próximas</a>
  <a href="cerrar_sesion.php"    class="boton-institucional rojo">🔒 Cerrar Sesión</a>
</div>

</body>
</html>
