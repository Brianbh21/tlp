<?php
require_once 'includes/traslado_lote_logic.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Traslado de Lotes</title>
  <link rel="stylesheet" href="./css/global.css">
</head>
<body>
  <div class="container">
    <h2>🚚 Traslado de Lotes entre Bodegas</h2>

    <div class="debug-info">
      <strong>👤 Usuario:</strong> <?= htmlspecialchars($nombre_usuario ?? '') ?> |
      <strong>Rol:</strong> <?= htmlspecialchars($rol ?? '') ?> |
      <strong>ID:</strong> <?= htmlspecialchars($id_usuario ?? '') ?><br>
      <?php
        // Si quieres mostrar total de lotes disponibles:
        $total_lotes = 0;
        foreach ($lotes_por_tipo as $arr) { $total_lotes += count($arr); }
      ?>
      <strong>Total lotes disponibles:</strong> <?= $total_lotes ?>
    </div>

    <?php
    $bodega_actual = match ($rol ?? '') {
        'empacador' => 'Empaque',
        'almacen'   => 'Almacén',
        'cedi'      => 'CEDI',
        'inventario'=> 'Inventario',
        'admin'     => 'Administrador',
        default     => 'Desconocida'
    };
    ?>
    <div class="bodega-info">
      <strong>Bodega actual:</strong> <?= htmlspecialchars($bodega_actual) ?><br>
      <strong>Destinos disponibles:</strong> Inventario → Empaque → CEDI → Almacén
    </div>

    <?php if (!empty($mensaje)) : ?>
      <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" id="formulario-traslado">
      <label for="id_lote">Selecciona un lote:</label>
      <select name="id_lote" id="id_lote" required>
        <option value="">-- Selecciona un lote --</option>
        <?php foreach ($lotes_por_tipo as $tipo => $lotes): ?>
          <optgroup label="<?= htmlspecialchars($tipo) ?>">
            <?php foreach ($lotes as $lote): ?>
              <option value="<?= (int)$lote['id_lote'] ?>"
                      data-cantidad="<?= (int)$lote['cantidad_total'] ?>"
                      data-estado="<?= htmlspecialchars($lote['estado']) ?>"
                      data-tipo="<?= htmlspecialchars($lote['tipo_producto']) ?>"
                      data-fecha="<?= htmlspecialchars($lote['fecha_empaque']) ?>"
                      data-vencimiento="<?= htmlspecialchars($lote['fecha_vencimiento']) ?>"
                      data-planta="<?= htmlspecialchars($lote['planta_origen']) ?>">
                Lote #<?= htmlspecialchars($lote['numero_lote']) ?> - <?= (int)$lote['cantidad_total'] ?> unidades
              </option>
            <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>

      <div class="info-lote" id="info-lote" style="display:none;"></div>

      <label for="cantidad_trasladar">Cantidad a trasladar:</label>
      <div class="inline">
        <input type="number" name="cantidad_trasladar" id="cantidad_trasladar" min="1" required>
        <button type="button" onclick="trasladoCompleto()">Completo</button>
      </div>

      <label for="nuevo_estado">Trasladar a:</label>
      <select name="nuevo_estado" id="nuevo_estado" required>
        <option value="">-- Selecciona destino --</option>
        <?php
          $destinos = ['empaque','CEDI','almacen'];
          // evitar mismo origen:
          foreach ($destinos as $d) {
            if (strcasecmp($d, $bodega_actual) !== 0) {
              echo '<option value="'.htmlspecialchars($d).'">'.htmlspecialchars($d).'</option>';
            }
          }
        ?>
      </select>

      <button type="submit" class="btn-rojo">Realizar traslado</button>
    </form>

    <div class="text-center" style="margin-top:20px;">
      <a href="volver.php" class="btn-gris">⬅ Regresar</a>
    </div>
  </div>

  <script>
    function trasladoCompleto(){
      const sel = document.getElementById('id_lote');
      const opt = sel.options[sel.selectedIndex];
      const cant = opt ? parseInt(opt.getAttribute('data-cantidad') || 0) : 0;
      if(cant>0){
        document.getElementById('cantidad_trasladar').value = cant;
      }
    }

    const selectLote = document.getElementById('id_lote');
    const infoDiv    = document.getElementById('info-lote');

    selectLote.addEventListener('change', function(){
      const opt = this.options[this.selectedIndex];
      if(!opt.value){
        infoDiv.style.display='none';
        infoDiv.innerHTML='';
        return;
      }
      const data = {
        cantidad:     opt.getAttribute('data-cantidad'),
        estado:       opt.getAttribute('data-estado'),
        tipo:         opt.getAttribute('data-tipo'),
        fecha:        opt.getAttribute('data-fecha'),
        vencimiento:  opt.getAttribute('data-vencimiento'),
        planta:       opt.getAttribute('data-planta')
      };
      infoDiv.innerHTML = `
        <p><strong>Tipo:</strong> ${data.tipo}</p>
        <p><strong>Cantidad:</strong> ${data.cantidad}</p>
        <p><strong>Estado actual:</strong> ${data.estado}</p>
        <p><strong>Empaque:</strong> ${data.fecha}</p>
        <p><strong>Vence:</strong> ${data.vencimiento}</p>
        <p><strong>Planta:</strong> ${data.planta}</p>
      `;
      infoDiv.style.display='block';
    });
  </script>
</body>
</html>
