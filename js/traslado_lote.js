let codigosEscaneados = [];
let tiempoUltimoScan = 0;

function actualizarInfo() {
  const select = document.getElementById('id_lote');
  const infoDiv = document.getElementById('info-lote');
  const cantidadInput = document.getElementById('cantidad_trasladar');

  if (select.value) {
    const option = select.options[select.selectedIndex];
    const cantidad = option.dataset.cantidad;
    const estado = option.dataset.estado;
    const tipo = option.dataset.tipo;
    const fecha = option.dataset.fecha;
    const vencimiento = option.dataset.vencimiento;
    const planta = option.dataset.planta;

    infoDiv.innerHTML = `
      <strong>Producto:</strong> ${tipo}<br>
      <strong>Estado actual:</strong> ${estado.toUpperCase()}<br>
      <strong>Cantidad disponible:</strong> ${cantidad}<br>
      <strong>Fecha empaque:</strong> ${fecha}<br>
      <strong>Fecha vencimiento:</strong> ${vencimiento}<br>
      <strong>Planta origen:</strong> ${planta}
    `;
    infoDiv.style.display = 'block';
    cantidadInput.max = cantidad;
    cantidadInput.placeholder = `Máximo: ${cantidad}`;
  } else {
    infoDiv.style.display = 'none';
    cantidadInput.max = '';
    cantidadInput.placeholder = '';
  }
}

function trasladoCompleto() {
  const select = document.getElementById('id_lote');
  const input = document.getElementById('cantidad_trasladar');
  if (select.value) {
    const option = select.options[select.selectedIndex];
    input.value = option.dataset.cantidad;
  }
}

function iniciarEscaneoQR() {
  const seccion = document.getElementById("qr-section");
  seccion.style.display = "block";

  const reader = new Html5Qrcode("reader");

  reader.start(
    { facingMode: "environment" },
    { fps: 5, qrbox: 250 },
    (decodedText, decodedResult) => {
      const ahora = Date.now();
      if (ahora - tiempoUltimoScan > 1500) { // 1.5 segundos entre escaneos
        tiempoUltimoScan = ahora;

        if (!codigosEscaneados.includes(decodedText)) {
          codigosEscaneados.push(decodedText);
          actualizarListaEscaneos();
        }
      }
    },
    (errorMessage) => {
      // Errores silenciosos del lector
    }
  );
}

function actualizarListaEscaneos() {
  const lista = document.getElementById("lista-codigos");
  const conteo = document.getElementById("conteo");
  const hiddenInput = document.getElementById("codigos_qr");

  lista.innerHTML = "";
  codigosEscaneados.forEach(codigo => {
    const li = document.createElement("li");
    li.textContent = codigo;
    lista.appendChild(li);
  });

  conteo.textContent = codigosEscaneados.length;
  hiddenInput.value = codigosEscaneados.join(",");
}

document.getElementById('id_lote').addEventListener('change', actualizarInfo);
