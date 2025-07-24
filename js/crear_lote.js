// crear_lote.js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', (event) => {
            const empaque = new Date(document.querySelector('input[name="fecha_empaque"]').value);
            const vencimiento = new Date(document.querySelector('input[name="fecha_vencimiento"]').value);
            if (empaque > vencimiento) {
                alert('⚠️ La fecha de empaque no puede ser posterior a la fecha de vencimiento.');
                event.preventDefault();
            }
        });
    }
});
