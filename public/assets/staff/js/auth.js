/**
 * Authentication module for Staff Dashboard
 * Handles login, logout, and token management
 */

const Auth = {
    TOKEN_KEY: 'staff_token',
    USER_KEY: 'staff_user',
    
    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return !!this.getToken();
    },
    
    /**
     * Get stored auth token
     */
    getToken() {
        return localStorage.getItem(this.TOKEN_KEY);
    },
    
    /**
     * Get stored user data
     */
    getUser() {
        const userData = localStorage.getItem(this.USER_KEY);
        return userData ? JSON.parse(userData) : null;
    },
    
    /**
     * Save auth data
     */
    setAuth(token, user) {
        localStorage.setItem(this.TOKEN_KEY, token);
        localStorage.setItem(this.USER_KEY, JSON.stringify(user));
    },
    
    /**
     * Clear auth data
     */
    clearAuth() {
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.USER_KEY);
    },
    
    /**
     * Verify token validity and staff access
     */
    async verifyToken() {
        const token = this.getToken();
        if (!token) return false;
        
        try {
            const response = await fetch('/api/v1/staff/info', {
                headers: {
                    'Authorization': token
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                return data.status === 'success' && data.data && data.data.is_staff;
            }
            return false;
        } catch (error) {
            console.error('Token verification failed:', error);
            return false;
        }
    },
    
    /**
     * Logout user
     */
    async logout() {
        this.clearAuth();
        window.location.href = `/${window.staffPath}/login`;
    },
    
    /**
     * Redirect to login if not authenticated
     */
    requireAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = `/${window.staffPath}/login`;
            return false;
        }
        return true;
    },
    
    /**
     * Initialize auth check
     */
    async init() {
        // Check staffPath is available
        if (!window.staffPath) {
            console.error('Staff path not defined');
            return;
        }
        
        // Check if we're on login page
        if (window.location.pathname.includes(`/${window.staffPath}/login`)) {
            return;
        }
        
        // Require authentication for dashboard
        if (!this.requireAuth()) {
            return;
        }
        
        // Verify token is still valid
        try {
            const isValid = await this.verifyToken();
            if (!isValid) {
                console.log('Token verification failed, redirecting to login');
                this.clearAuth();
                window.location.href = `/${window.staffPath}/login`;
            } else {
                console.log('Auth verification successful');
            }
        } catch (error) {
            console.error('Auth verification error:', error);
            this.clearAuth();
            window.location.href = `/${window.staffPath}/login`;
        }
    }
};
