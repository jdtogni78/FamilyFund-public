/**
 * Bootstrap 5 + axios bootstrap (Vite / ESM).
 *
 * Migrated from the legacy Laravel Mix CommonJS `require()` setup, which Vite
 * could not bundle — the old build emitted a 24-byte `require("./bootstrap")`
 * file that threw `ReferenceError: require is not defined` and took the whole
 * app.js bundle (and every `data-bs-*` interaction) down with it.
 *
 * Bootstrap 5 bundles Popper itself; jQuery is still loaded via CDN in
 * layouts/app.blade.php for DataTables, so it is intentionally not imported
 * here.
 */
import * as bootstrap from 'bootstrap';
import axios from 'axios';

window.bootstrap = bootstrap;

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}
