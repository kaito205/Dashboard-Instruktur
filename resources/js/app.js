import './bootstrap';
import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.Alpine = Alpine;
window.flatpickr = flatpickr;

Alpine.start();

document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const input = target.closest('input[type="date"], input[type="time"]');
    if (!(input instanceof HTMLInputElement)) return;
    if (input.disabled || input.readOnly) return;

    if (typeof input.showPicker === 'function') {
        try {
            input.showPicker();
        } catch {
            // Browser may reject showPicker() if picker is already open or lacks activation
        }
    }
});
