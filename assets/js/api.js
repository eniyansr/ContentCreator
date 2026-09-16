/**
 * CreatorAI - API Client Module
 * Centralized fetch wrapper for all API calls
 */

const API = {
    baseUrl: '',

    /**
     * Initialize with base URL
     */
    init(baseUrl) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
    },

    /**
     * Get CSRF token from meta tag
     */
    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    },

    /**
     * Make API request
     */
    async request(endpoint, options = {}) {
        const url = endpoint.startsWith('http') ? endpoint : `${this.baseUrl}/${endpoint.replace(/^\//, '')}`;
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.getCsrfToken(),
            },
            credentials: 'same-origin',
        };

        const config = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers,
            },
        };

        // Don't set Content-Type for FormData
        if (options.body instanceof FormData) {
            delete config.headers['Content-Type'];
        }

        try {
            const response = await fetch(url, config);
            
            // Handle non-JSON responses
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}`);
                }
                return { success: true, data: await response.text() };
            }

            const data = await response.json();

            if (!response.ok) {
                throw {
                    status: response.status,
                    message: data.message || 'An error occurred',
                    errors: data.errors || {},
                };
            }

            return data;
        } catch (error) {
            if (error.status === 401) {
                // Redirect to login on auth failure
                window.location.href = `${this.baseUrl}/public/login.php`;
                return;
            }
            throw error;
        }
    },

    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    },

    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    },

    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    },

    /**
     * DELETE request
     */
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    },

    /**
     * Upload file
     */
    async upload(endpoint, formData) {
        return this.request(endpoint, {
            method: 'POST',
            body: formData,
        });
    },
};

// Initialize with base URL from meta tag or default
document.addEventListener('DOMContentLoaded', () => {
    const baseMeta = document.querySelector('meta[name="base-url"]');
    API.init(baseMeta ? baseMeta.getAttribute('content') : '');
});
