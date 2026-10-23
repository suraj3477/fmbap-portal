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

    // Response interceptor to automatically recover from 419 (CSRF token mismatch)
    window.axios.interceptors.response.use(
        (response) => response,
        async (error) => {
            const originalRequest = error.config;
            if (error.response && error.response.status === 419 && originalRequest && !originalRequest._retry) {
                originalRequest._retry = true;
                try {
                    const res = await window.axios.get('/csrf-token');
                    const freshToken = res.data?.csrf_token;
                    if (freshToken) {
                        const meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.content = freshToken;
                        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = freshToken;
                        originalRequest.headers['X-CSRF-TOKEN'] = freshToken;
                        if (originalRequest.data instanceof FormData && originalRequest.data.has('_token')) {
                            originalRequest.data.set('_token', freshToken);
                        }
                        return window.axios(originalRequest);
                    }
                } catch (refreshErr) {
                    console.error('Failed to refresh CSRF token automatically:', refreshErr);
                }
            }
            return Promise.reject(error);
        }
    );
}
