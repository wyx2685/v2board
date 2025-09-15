/**
 * API Service module
 * Centralized API calls with error handling
 */

const API = {
    BASE_URL: '/api/v1/staff',
    
    /**
     * Make authenticated API request
     */
    async request(endpoint, options = {}) {
        const token = Auth.getToken();
        if (!token) {
            throw new Error('No authentication token');
        }
        
        // Always prepend BASE_URL unless it's already a full API path
        const url = endpoint.startsWith('/api/') ? endpoint : `${this.BASE_URL}${endpoint}`;
        
        const config = {
            ...options,
            headers: {
                'Authorization': token,
                'Content-Type': 'application/json',
                ...options.headers
            }
        };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    },
    
    /**
     * GET request
     */
    get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    },
    
    /**
     * POST request
     */
    post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },
    
    /**
     * PUT request
     */
    put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },
    
    /**
     * DELETE request
     */
    delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    },
    
    // API Endpoints - sử dụng full paths
    dashboard: {
        getInfo: () => API.get('/api/v1/staff/info'),
        getStats: () => API.get('/api/v1/staff/stat')
    },
    
    users: {
        search: (params) => API.get('/api/v1/staff/finduser', params),
        getById: (id) => API.get('/api/v1/staff/user/getUserInfoById', { id }),
        update: (data) => API.post('/api/v1/staff/user/update', data),
        sendMail: (data) => API.post('/api/v1/staff/user/sendMail', data),
        ban: (params) => API.post('/api/v1/staff/user/ban', params),
        resetSecurity: (userId) => API.post('/api/v1/staff/user/resetSecurity', { target_user_id: userId })
    },
    
    orders: {
        fetch: (params) => API.get('/api/v1/staff/order/fetch', params),
        getDetail: (id) => API.get('/api/v1/staff/order/detail', { id }),
        getStats: () => API.get('/api/v1/staff/order/stat'),
        getSummary: () => API.get('/api/v1/staff/order/summary'),
        assign: (data) => API.post('/api/v1/staff/order/assign', data)
    },
    
    tickets: {
        fetch: (params) => API.get('/api/v1/staff/ticket/fetch', params),
        reply: (id, message) => API.post('/api/v1/staff/ticket/reply', { id, message }),
        close: (id) => API.post('/api/v1/staff/ticket/close', { id })
    },
    
    notices: {
        fetch: () => API.get('/api/v1/staff/notice/fetch'),
        save: (data) => API.post('/api/v1/staff/notice/save', data),
        update: (data) => API.post('/api/v1/staff/notice/update', data),
        delete: (id) => API.post('/api/v1/staff/notice/drop', { id })
    },
    
    config: {
        get: () => API.get('/api/v1/staff/config'),
        save: (data) => API.post('/api/v1/staff/configsave', data)
    },
    
    plans: {
        fetch: () => API.get('/api/v1/staff/plan/fetch')
    }
};
