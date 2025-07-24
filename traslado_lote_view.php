
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Traslado de Lotes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/global.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">🚚 Traslado de Lotes entre Bodegas</h2>

        <!-- Información de usuario -->
        <div class="debug-info">
            <strong>👤 Usuario:</strong> <?= htmlspecialchars($nombre_usuario ?? 'Sin nombre') ?> |
            <strong>Rol:</strong> <?= htmlspecialchars($rol ?? 'Sin rol') ?> |
            <strong>ID:</strong> <?= htmlspecialchars($id_usuario ?? 'Sin ID') ?><br>
            <strong>Total lotes disponibles:</strong> <?= $total_lotes_disponibles ?>
        </div>

        <!-- Bodega actual y mensaje -->
        <div class="alert alert-info">
            <strong>Bodega actual:</strong> <?= $bodega_actual ?><br>
            <strong>Destinos disponibles:</strong> Inventario → Empaque → CEDI → Almacén
        </div>

        <?php if (!empty($mensaje)) : ?>
            <div class="alert alert-info text-center"><?= $mensaje ?></div>
        <?php endif; ?>

        <!-- Formulario de traslado -->
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="id_lote" class="form-label">Selecciona un lote:</label>
                <select class="form-select" name="id_lote" id="id_lote" required onchange="actualizarInfo()">
                    <option value="">-- Selecciona un lote --</option>
                    <?= $opciones_lotes ?>
                </select>
            </div>

            <div id="info-lote" class="info-lote" style="display:none;"></div>

            <div class="mb-3">
                <label for="cantidad_trasladar" class="form-label">Cantidad a trasladar:</label>
                <div class="input-group">
                    <input type="number" class="form-control" name="cantidad_trasladar" id="cantidad_trasladar" min="1" required>
                    <button type="button" class="btn btn-outline-primary" onclick="trasladoCompleto()">Completo</button>
                </div>
            </div>

            <div class="mb-3">
                <label for="nuevo_estado" class="form-label">Trasladar a:</label>
                <select class="form-select" name="nuevo_estado" id="nuevo_estado" required>
                    <option value="">-- Selecciona destino --</option>
                    <option value="inventario">📦 Inventario</option>
                    <option value="empaque">📋 Empaque</option>
                    <option value="CEDI">🏢 CEDI</option>
                    <option value="almacen">🏪 Almacén</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100" <?= empty($lotes_por_tipo) ? 'disabled' : '' ?>>
                Realizar traslado
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="volver.php" class="btn btn-secondary">⬅ Regresar</a>
        </div>
    </div>
    
<script src="js/html5-qrcode.min.js"></script>
<script src="js/traslado_lote.js"></script>

    <script src="js/traslado_lote.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
