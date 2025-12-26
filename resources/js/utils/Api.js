export const api = {
    // Current prefix logic
    base: '/@/api',

    async request(method, path, data = null, options = {}) {
        const url = path.startsWith('http') || path.startsWith('/')
            ? path
            : `${this.base}/${path.replace(/^\//, '')}`;

        const isFormData = data instanceof FormData;

        const fetchOptions = {
            method,
            headers: {
                ...options.headers
            },
            ...options
        };

        if (data) {
            if (isFormData) {
                fetchOptions.body = data;
                // Important: Don't set Content-Type for FormData, the browser will set it with boundary
            } else {
                fetchOptions.body = JSON.stringify(data);
                fetchOptions.headers['Content-Type'] = 'application/json';
            }
        }

        const response = await fetch(url, fetchOptions);

        // Global Error Handling
        if (response.status === 401) {
            // Handle unauthorized (e.g., store.logout or redirect)
            if (window.location.hash !== '#/login') {
                window.location.hash = '#/login';
            }
        }

        if (!response.ok) {
            const error = await response.json().catch(() => ({ error: 'Unknown error' }));
            throw new Error(error.error || `HTTP ${response.status}`);
        }

        return response.json();
    },

    get: (path, options) => api.request('GET', path, null, options),
    post: (path, data, options) => api.request('POST', path, data, options),
    patch: (path, data, options) => api.request('PATCH', path, data, options),
    put: (path, data, options) => api.request('PUT', path, data, options),
    delete: (path, options) => api.request('DELETE', path, null, options)
};
