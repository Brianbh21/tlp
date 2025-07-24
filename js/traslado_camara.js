
let codigosEscaneados = {};
let ultimoCodigo = '';
let ultimaLectura = 0;

function limpiarNumeroLote(texto) {
    // Extrae solo el valor después de "Lote: " hasta el primer salto de línea
    const match = texto.match(/Lote:\s*([^\n]+)/);
    return match ? match[1].trim() : null;
}

function agregarCodigo(numeroLote) {
    const ahora = Date.now();
    if (numeroLote === ultimoCodigo && ahora - ultimaLectura < 2000) {
        // Ignorar repeticiones muy rápidas (< 2 segundos)
        return;
    }

    ultimoCodigo = numeroLote;
    ultimaLectura = ahora;

    if (!codigosEscaneados[numeroLote]) {
        codigosEscaneados[numeroLote] = 1;
    } else {
        codigosEscaneados[numeroLote]++;
    }

    actualizarListado();
    console.log('Escaneado:', numeroLote, 'Cantidad:', codigosEscaneados[numeroLote]);
}

function actualizarListado() {
    const contenedor = document.getElementById("listado-codigos");
    contenedor.innerHTML = "";
    for (const codigo in codigosEscaneados) {
        const div = document.createElement("div");
        div.className = "codigo-item";
        div.innerText = `Lote: ${codigo} - Cantidad: ${codigosEscaneados[codigo]}`;
        contenedor.appendChild(div);
    }

    // Actualiza campo oculto para el formulario
    document.getElementById("datos_codigos").value = JSON.stringify(codigosEscaneados);
}

// Configura escáner
function iniciarCamara() {
    const html5QrCode = new Html5Qrcode("lector");
    const config = { fps: 10, qrbox: 250 };

    html5QrCode.start(
        { facingMode: "environment" },
        config,
        (decodedText, decodedResult) => {
            const lote = limpiarNumeroLote(decodedText);
            if (lote) agregarCodigo(lote);
        },
        (error) => {
            // Ignorar errores individuales
        }
    ).catch(err => {
        document.getElementById("estado-scan").innerText = "❌ Error accediendo a la cámara.";
        console.error("Error cámara:", err);
    });
}

// Validación de formulario antes de enviar
document.getElementById("formulario-camara").addEventListener("submit", function(e) {
    const destino = document.getElementById("destino").value;
    if (!destino) {
        e.preventDefault();
        alert("⚠️ Debes seleccionar una bodega destino.");
        return;
    }

    document.getElementById("destino_hidden").value = destino;
});

window.addEventListener("load", iniciarCamara);
