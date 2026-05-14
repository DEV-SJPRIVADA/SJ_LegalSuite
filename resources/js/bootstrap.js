import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfMeta?.content) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.content;
}

/**
 * Echo (Pusher) se configura en `echo-notification-bell.js` (valores desde Blade).
 *
 * No sincronizar CSRF desde `script[data-csrf]`: con `wire:navigate` el script de Livewire
 * lleva `data-navigate-once` y el hash ignora `data-csrf`, así que el atributo puede quedar
 * obsoleto mientras el `<meta name="csrf-token">` sí se actualiza — copiarlo al meta/cuerpo
 * provocaba 419 (TokenMismatch) al sobrescribir un token válido.
 */
document.addEventListener('livewire:init', () => {
    if (!window.Livewire?.hook) {
        return;
    }
    window.Livewire.hook('request', ({ options }) => {
        if (!options.credentials) {
            options.credentials = 'same-origin';
        }
    });
});
