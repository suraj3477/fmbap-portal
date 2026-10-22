import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withXSRFToken = true;
window.axios.defaults.withCredentials = true;

// Attach CSRF token from document meta tag if available
if (typeof document !== 'undefined') {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token && token.content) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    }

    // Dynamic request interceptor to always attach the latest CSRF token
    window.axios.interceptors.request.use((config) => {
        const currentToken = document.querySelector('meta[name="csrf-token"]');
        if (currentToken && currentToken.content) {
            config.headers['X-CSRF-TOKEN'] = currentToken.content;
        }
        return config;
    });
}
