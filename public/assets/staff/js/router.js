/**
 * Router module for SPA navigation
 */

const Router = {
    routes: {},
    currentRoute: null,
    
    /**
     * Register a route
     */
    register(path, handler) {
        this.routes[path] = handler;
    },
    
    /**
     * Navigate to a route
     */
    navigate(path) {
        window.location.hash = path;
    },
    
    /**
     * Get current route
     */
    getCurrentRoute() {
        const hash = window.location.hash.slice(1) || '/dashboard';
        return hash.split('?')[0];
    },
    
    /**
     * Get route params
     */
    getParams() {
        const hash = window.location.hash.slice(1);
        const parts = hash.split('?');
        if (parts.length < 2) return {};
        
        const params = {};
        const queryString = parts[1];
        const pairs = queryString.split('&');
        
        pairs.forEach(pair => {
            const [key, value] = pair.split('=');
            params[key] = decodeURIComponent(value || '');
        });
        
        return params;
    },
    
    /**
     * Handle route change
     */
    async handleRoute() {
        const path = this.getCurrentRoute();
        
        // Update active nav link
        document.querySelectorAll('.nav-link').forEach(link => {
            const route = link.getAttribute('data-route');
            if (`/${route}` === path) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
        
        // Update page title and breadcrumb
        const routeName = path.slice(1) || 'dashboard';
        const pageTitle = routeName.charAt(0).toUpperCase() + routeName.slice(1);
        document.getElementById('pageTitle').textContent = pageTitle;
        
        // Update breadcrumb
        const breadcrumb = document.getElementById('breadcrumb');
        breadcrumb.innerHTML = `<span>Home</span> / <span>${pageTitle}</span>`;
        
        // Execute route handler
        const handler = this.routes[path];
        if (handler) {
            try {
                // Show loading
                const content = document.getElementById('pageContent');
                content.innerHTML = `
                    <div class="loading-container">
                        <div class="loading-spinner"></div>
                        <p>Đang tải...</p>
                    </div>
                `;
                
                // Execute handler with proper context
                console.log('Executing route handler for:', path);
                await handler();
                
                this.currentRoute = path;
            } catch (error) {
                console.error('Route handler error:', error);
                // Show error instead of toast that might not be available
                const content = document.getElementById('pageContent');
                if (content) {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <h4>Lỗi tải trang</h4>
                            <p>Có lỗi xảy ra: ${error.message}</p>
                            <button onclick="location.reload()" class="btn btn-primary">Tải lại</button>
                        </div>
                    `;
                }
            }
        } else {
            // 404 - Route not found
            document.getElementById('pageContent').innerHTML = `
                <div class="card">
                    <div class="card-body text-center">
                        <h2>404 - Trang không tồn tại</h2>
                        <p>Trang bạn tìm kiếm không tồn tại.</p>
                        <a href="#/dashboard" class="btn btn-primary mt-3">
                            <i class="fas fa-home"></i> Về Dashboard
                        </a>
                    </div>
                </div>
            `;
        }
    },
    
    /**
     * Initialize router
     */
    init() {
        // Register routes with proper binding
        this.register('/dashboard', () => Dashboard.render());
        this.register('/users', () => Users.render());
        this.register('/orders', () => Orders.render());
        this.register('/tickets', () => Tickets.render());
        // this.register('/notices', () => Notices.render()); // Tạm ẩn notices
        this.register('/config', () => Config.render());
        
        // Handle hash change
        window.addEventListener('hashchange', () => this.handleRoute());
        
        // Handle initial route
        if (!window.location.hash) {
            window.location.hash = '/dashboard';
        } else {
            this.handleRoute();
        }
    }
};
