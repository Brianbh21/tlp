document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.getElementById("tabla-movimientos");

    if (!movimientos || movimientos.length === 0) {
        tbody.innerHTML = "<tr><td colspan='9' style='text-align:center;'>No hay movimientos registrados.</td></tr>";
        return;
    }

    movimientos.forEach(m => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${m.id_movimiento}</td>
            <td>${m.id_lote}</td>
            <td>${m.cantidad}</td>
            <td>${m.fecha_movimiento}</td>
            <td>${m.origen}</td>
            <td>${m.destino}</td>
            <td>${m.id_responsable}</td>
            <td>${m.estado_origen}</td>
            <td>${m.estado_destino}</td>
        `;
        tbody.appendChild(tr);
    });
});
