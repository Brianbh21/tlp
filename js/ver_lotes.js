
function seleccionarProducto(valor) {
    if (valor) {
        document.getElementById('buscar_producto').value = valor;
        document.querySelector('form').submit();
    }
}

function verQR(urlQR, numeroLote, archivoQR) {
    document.getElementById('qrModalTitle').textContent = `📱 Código QR - Lote ${numeroLote}`;
    document.getElementById('qrModalImage').src = urlQR;

    const debugInfo = document.getElementById('qrDebugInfo');
    debugInfo.innerHTML = `
        <strong>Información de debug:</strong><br>
        Lote: ${numeroLote}<br>
        Archivo QR: ${archivoQR}<br>
        URL: ${urlQR}
    `;
    debugInfo.style.display = 'block';
    document.getElementById('qrModal').style.display = 'block';
}

function mostrarErrorImagen(img) {
    img.style.display = 'none';
    const error = document.createElement('div');
    error.style.cssText = 'color: #dc3545; padding: 20px; border: 2px dashed #dc3545; border-radius: 8px; margin: 20px 0;';
    error.innerHTML = '❌ Error: No se pudo cargar la imagen del código QR<br><small>Verifica que el archivo existe en la ruta especificada</small>';
    img.parentNode.insertBefore(error, img);
}

function cerrarModal() {
    document.getElementById('qrModal').style.display = 'none';
    const errors = document.querySelectorAll('#qrModal .modal-content > div[style*="color: #dc3545"]');
    errors.forEach(error => error.remove());
    document.getElementById('qrModalImage').style.display = 'block';
}

function imprimirQR(urlQR, numeroLote, archivoQR) {
    const ventanaImpresion = window.open('', '_blank', 'width=300,height=400');
    ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Imprimir QR</title>
            <style>
                @page {
                    size: auto;
                    margin: 0;
                }
                body {
                    margin: 0;
                    padding: 0;
                    width: 58mm; /* Ajusta aquí si tu rollo es 80mm */
                    text-align: center;
                    font-family: Arial, sans-serif;
                }
                .qr-only {
                    margin-top: 10px;
                }
                img {
                    width: 90%;
                    max-width: 100%;
                }
                @media print {
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body onload="window.print(); setTimeout(() => window.close(), 100)">
            <div class="info">
                    <p><strong>📅 Fecha de impresión:</strong> ${new Date().toLocaleDateString('es-ES')}</p>
                    <p><strong>🕐 Hora:</strong> ${new Date().toLocaleTimeString('es-ES')}</p>
                </div>
                <div class="qr-only">
                <img src="${urlQR}" alt="Código QR del lote ${numeroLote}" />
            </div>
        </body>
        </html>
    `);
    ventanaImpresion.document.close();
}


document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('qrModal').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModal();
    });
});
