<?php include 'includes/header.php'; ?>
<div class="container">
    <div class="header">
        <div class="user-info">
            👤 <?php echo htmlspecialchars($_SESSION['nombre']); ?> (<?php echo htmlspecialchars($_SESSION['rol']); ?>)
        </div>
        <h1>📦 Ver Lotes Registrados</h1>
        <p>Busca y gestiona códigos QR de los lotes</p>
    </div>

    <!-- Búsqueda -->
    <div class="search-section">
        <form method="GET" class="search-form">
            <div class="form-group">
                <label for="buscar_producto">🔍 Buscar por producto:</label>
                <input type="text" id="buscar_producto" name="buscar_producto"
                       value="<?php echo htmlspecialchars($buscar_producto); ?>"
                       placeholder="producto">
            </div>
            <div class="form-group">
                <label for="producto_select">📋 O selecciona:</label>
                <select id="producto_select" onchange="seleccionarProducto(this.value)">
                    <option value="">-- Todos los productos --</option>
                    <?php foreach ($productos_disponibles as $producto): ?>
                        <option value="<?php echo htmlspecialchars($producto); ?>"
                                <?php echo ($buscar_producto === $producto) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($producto); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <a href="ver_lotes.php" class="btn btn-secondary">🔄 Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    <div class="results-section">
        <?php if (!empty($lotes_encontrados)): ?>
            <div class="results-header">
                <h2>📋 Lotes disponibles</h2>
                <div class="resumen-lotes">
                    📦 <?php echo count($lotes_encontrados); ?> lote(s) |
                    ✅ <?php echo count(array_filter($lotes_encontrados, fn($lote) => $lote['qr_disponible'])); ?> con QR
                </div>
            </div>

            <div class="lotes-grid">
                <?php foreach ($lotes_encontrados as $lote): ?>
                    <div class="lote-card">
                        <div class="lote-header">
                            <div class="lote-numero">📦 Lote: <?php echo $lote['numero_lote']; ?></div>
                            <div class="lote-fecha">📅 <?php echo date('d/m/Y', strtotime($lote['fecha_empaque'])); ?></div>
                        </div>
                        <div class="info-grid">
                            <div class="info-item"><span>🏷️ Producto</span> <?php echo $lote['tipo_producto']; ?></div>
                            <div class="info-item"><span>🏭 Planta</span> <?php echo $lote['planta_origen']; ?></div>
                            <div class="info-item"><span>📊 Cantidad</span> <?php echo $lote['cantidad_total']; ?></div>
                            <div class="info-item"><span>⏰ Vencimiento</span> <?php echo date('d/m/Y', strtotime($lote['fecha_vencimiento'])); ?></div>
                            <div class="info-item"><span>📱 Archivo QR</span> <?php echo $lote['qr_codigo'] ?? 'No asignado'; ?></div>
                            <div class="qr-status <?php echo $lote['qr_disponible'] ? 'qr-disponible' : 'qr-no-disponible'; ?>">
                                <?php echo $lote['qr_disponible'] ? '✅ QR Disponible' : '❌ QR No Disponible'; ?>
                            </div>
                        </div>
                        <div class="lote-actions">
                            <?php if ($lote['qr_disponible']): ?>
                                <button onclick="imprimirQR('<?php echo $lote['qr_url']; ?>', '<?php echo $lote['numero_lote']; ?>', '<?php echo $lote['qr_codigo']; ?>')" class="btn btn-success">
                                    🖨️ Imprimir
                                </button>
                            <?php else: ?>
                                <div class="qr-alert">
                                    ⚠️ <?php echo empty($lote['qr_codigo']) ? 'Sin código QR asignado' : 'Archivo QR no encontrado'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h3>😔 No se encontraron lotes</h3>
                <p>No hay lotes disponibles<?php if ($buscar_producto) echo ' para "' . $buscar_producto . '"'; ?>.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="back-link" style="margin-top: 30px; text-align: left;">
        <a href="<?php echo redireccionarSegunRol($_SESSION['rol']); ?>" class="btn btn-secondary">⬅ Regresar</a>
    </div>
</div>

<!-- Modal (si deseas dejarlo, descomenta el HTML y activa cerrarModal) -->

<script>
function seleccionarProducto(valor) {
    if (valor) {
        document.getElementById('buscar_producto').value = valor;
        document.querySelector('form').submit();
    }
}

function imprimirQR(urlQR, numeroLote, archivoQR) {
    const ventanaImpresion = window.open('', '_blank', 'width=300,height=400');
    ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Imprimir QR</title>
            <style>
                @page { size: auto; margin: 0; }
                body {
                    margin: 0;
                    padding: 0;
                    width: 58mm;
                    text-align: center;
                    font-family: Arial, sans-serif;
                }
                img {
                    margin-top: 10px;
                    width: 90%;
                    max-width: 100%;
                }
                .info {
                    margin-top: 5px;
                    font-size: 12px;
                }
            </style>
        </head>
        <body onload="window.print(); setTimeout(() => window.close(), 100)">
            <div class="info">
                <p><strong>📅 Fecha:</strong> ${new Date().toLocaleDateString('es-ES')}</p>
                <p><strong>🕐 Hora:</strong> ${new Date().toLocaleTimeString('es-ES')}</p>
                <p><strong>Lote:</strong> ${numeroLote}</p>
            </div>
            <img src="${urlQR}" alt="Código QR del lote ${numeroLote}">
        </body>
        </html>
    `);
    ventanaImpresion.document.close();
}
</script>

<?php include 'includes/footer.php'; ?>
