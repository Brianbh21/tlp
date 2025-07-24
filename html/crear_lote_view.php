<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Lote</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/global.css">
</head>
<body>
    
    <div class="usuario-info">
        Usuario: <?php echo htmlspecialchars($_SESSION['nombre']); ?> (<?php echo htmlspecialchars($_SESSION['rol']); ?>)
    </div>

    <div class="form-container">
        <h2>Nuevo lote</h2>
        <form action="includes/procesar_lote.php" method="POST" id="form-lote">
            <label for="fecha_empaque">Fecha de empaque:</label>
            <input type="date" name="fecha_empaque" required>

            <label for="fecha_vencimiento">Fecha de vencimiento:</label>
            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" required>

            <label for="numero_lote">Número de Lote:</label>
            <input type="text" id="numero_lote" name="numero_lote"
       pattern="[A-Za-z0-9]{1,20}"
       title="Solo letras y números (máx. 20 caracteres)"
       placeholder="LOTE12345"
       required>

            
            <label for="planta_origen">Planta de origen:</label>
            <select name="planta_origen" id="planta_origen" required>
                <option value="">-- Selecciona planta --</option>
                <option value="Krumer">Krumer</option>
                <option value="Yoko">Yoko</option>
            </select>

            <label for="tipo_producto">Tipo de producto:</label>
            <select name="tipo_producto" id="tipo_producto" required>
                <option value="">-- Selecciona producto --</option>
                <?php foreach ($productos as $prod): ?>
                    <option value="<?= htmlspecialchars($prod) ?>"><?= htmlspecialchars($prod) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="cantidad_total">Cantidad total:</label>
            <input type="number" name="cantidad_total" min="1" required>

            <input type="hidden" name="id_empacador" value="<?= htmlspecialchars($id_empacador) ?>">

            <div class="empacador-info">
                <strong>Empacador:</strong> <?= htmlspecialchars($_SESSION['nombre']) ?>
                <?php if ($id_empacador): ?>
                    (ID: <?= htmlspecialchars($id_empacador) ?>)
                <?php else: ?>
                    <span style="color: red;">(ID no encontrado)</span>
                <?php endif; ?>
            </div>

            <input type="submit" value="Generar Lote y Código QR">
        </form>
        <div class="text-link">
            <a href="empacador.php">← Regresar al inicio</a>
        </div>
    </div>

    <script src="js/crear_lote.js"></script>
</body>
</html>
