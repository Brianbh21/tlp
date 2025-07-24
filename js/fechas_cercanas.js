function renderTabla(lotes) {
    const tbody = document.querySelector("#tablaFechas tbody");
    tbody.innerHTML = "";

    const hoy = new Date();

    lotes.forEach(lote => {
        const fechaVenc = new Date(lote.fecha_vencimiento);
        const diferenciaDias = Math.floor((fechaVenc - hoy) / (1000 * 60 * 60 * 24));

        let claseFila = "";
        if (diferenciaDias < 0) {
            claseFila = "fila-vencido"; // rojo
        } else if (diferenciaDias <= 30) {
            claseFila = "fila-proximo"; // amarillo
        }

        const fila = `
            <tr class="${claseFila}">
                <td>${lote.numero_lote}</td>
                <td>${lote.tipo_producto}</td>
                <td>${lote.fecha_vencimiento}</td>
                <td>${lote.cantidad_total}</td>
                <td>${lote.estado}</td>
            </tr>`;
        tbody.innerHTML += fila;
    });
}

window.onload = () => renderTabla(lotes);

function exportarExcel() {
    window.location.href = "exportar_fechas.php";
}
