import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.withCredentials = true;

// Axios automatically handles the X-XSRF-TOKEN cookie from Laravel
// We don't need to manually set the X-CSRF-TOKEN header from the meta tag
// as that can become stale in an SPA/Inertia app.
