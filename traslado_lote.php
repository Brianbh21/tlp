<?php
require_once __DIR__ . '/includes/traslado_lote_logic.php';
$lotes_por_tipo = $GLOBALS['lotes_por_tipo'] ?? [];
$placas         = $GLOBALS['placas'] ?? [];
$mensaje        = $GLOBALS['mensaje'] ?? '';
$rol            = $GLOBALS['rol'] ?? '';
$bodega_actual  = $GLOBALS['bodega_actual'] ?? '';
$nombre_usuario = $GLOBALS['nombre_usuario'] ?? '';
$id_usuario     = $GLOBALS['id_usuario'] ?? 0;

// Total lotes para el banner (evita warning)
$total_lotes = 0;
foreach ($lotes_por_tipo as $arr) { $total_lotes += count($arr); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Traslado de Lotes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./css/global.css" />
  <style>
    .box { background:#fff3cd; border:1px solid #ffeeba; color:#856404; padding:12px; border-radius:8px; margin:12px 0; }
    .ok { background:#d4edda; border-color:#c3e6cb; color:#155724; }
    .err{ background:#f8d7da; border-color:#f5c6cb; color:#721c24; }
    .form-card { max-width:720px; margin:0 auto; }
    .inline { display:flex; gap:8px; align-items:center; }
    @media (max-width:600px){
      .inline{ flex-direction:column; align-items:stretch; }
    }
  </style>
</head>
<body>
  <div class="container form-card">
    <h2>🚚 Traslado de Lotes entre Bodegas</h2>

    <div class="box">
      <strong>👤 Usuario:</strong> <?= htmlspecialchars($nombre_usuario) ?> |
      <strong>Rol:</strong> <?= htmlspecialchars($rol) ?> |
      <strong>ID:</strong> <?= (int)$id_usuario ?><br>
      <strong>Total lotes disponibles:</strong> <?= (int)$total_lotes ?>
    </div>

    <div class="box">
      <strong>Bodega actual:</strong> <?= htmlspecialchars($bodega_actual ?: 'Todas') ?><br>
      <strong>Destinos disponibles:</strong> Conductor | Empaque → CEDI → Almacén
    </div>

    <?php if ($mensaje): ?>
      <div class="box <?= (strpos($mensaje,'✅')!==false?'ok':'err') ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form action="includes/traslado_lote_logic.php" method="POST" id="form-traslado">
      <label for="id_lote">Selecciona un lote:</label>
      <select name="id_lote" id="id_lote" required>
        <option value="">-- Selecciona un lote --</option>
        <?php foreach ($lotes_por_tipo as $tipo => $lotes): ?>
          <optgroup label="<?= htmlspecialchars($tipo) ?>">
            <?php foreach ($lotes as $lote): ?>
              <option
                value="<?= (int)$lote['id_lote'] ?>"
                data-cantidad="<?= (int)$lote['cantidad_total'] ?>"
              >
                Lote #<?= htmlspecialchars($lote['numero_lote']) ?> - <?= (int)$lote['cantidad_total'] ?> unidades
              </option>
            <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>

      <label for="cantidad_trasladar">Cantidad a trasladar:</label>
      <div class="inline">
        <input type="number" name="cantidad_trasladar" id="cantidad_trasladar" min="1" required />
        <button type="button" class="btn-rojo" id="btn-completo">Completo</button>
      </div>

      <label for="destino_tipo">Trasladar a:</label>
      <select name="destino_tipo" id="destino_tipo" required>
        <option value="">-- Selecciona destino --</option>
        <option value="conductor">Conductor</option>
        <option value="CEDI">CEDI</option>
        <option value="almacen">Almacén</option>
        <option value="empaque">Empaque</option>
      </select>

      <div id="bloque-placa" style="display:none; margin-top:8px;">
        <label for="placa_destino">Placa destino:</label>
        <select name="placa_destino" id="placa_destino">
          <option value="">-- Selecciona placa --</option>
          <?php foreach ($placas as $pl): ?>
            <option value="<?= htmlspecialchars($pl) ?>"><?= htmlspecialchars($pl) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn-rojo" style="margin-top:12px;">Realizar traslado</button>
      <div style="text-align:center; margin-top:12px;">
        <a href="volver.php" class="btn-gris">⬅ Regresar</a>
      </div>
    </form>
  </div>

  <script>
    const selLote   = document.getElementById('id_lote');
    const inpCant   = document.getElementById('cantidad_trasladar');
    const btnFull   = document.getElementById('btn-completo');
    const selTipo   = document.getElementById('destino_tipo');
    const bloquePl  = document.getElementById('bloque-placa');

    btnFull.addEventListener('click', () => {
      const opt = selLote.options[selLote.selectedIndex];
      const cant = opt ? parseInt(opt.getAttribute('data-cantidad') || '0', 10) : 0;
      if (cant > 0) inpCant.value = cant;
    });

    function togglePlaca(){
      bloquePl.style.display = (selTipo.value === 'conductor') ? 'block' : 'none';
    }
    selTipo.addEventListener('change', togglePlaca);
    togglePlaca();
  </script>
</body>
</html>
