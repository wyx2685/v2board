/**
 * Main Application Controller
 */

const App = {
    /**
     * Initialize application
     */
    async init() {
        try {
            console.log('Initializing Staff Dashboard...');
            console.log('Staff path:', window.staffPath);
            
            // Check authentication
            await Auth.init();
            
            // Initialize UI
            this.initializeUI();
            
            // Initialize router
            Router.init();
            
            // Load user info
            await this.loadUserInfo();
            
            console.log('Staff Dashboard initialized successfully');
        } catch (error) {
            console.error('Failed to initialize Staff Dashboard:', error);
            document.body.innerHTML = `
                <div style="display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column;">
                    <h2>Lỗi khởi tạo hệ thống</h2>
                    <p>Có lỗi xảy ra khi tải trang. Vui lòng thử lại sau.</p>
                    <button onclick="location.reload()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px;">
                        Tải lại trang
                    </button>
                </div>
            `;
        }
    },
    
    /**
     * Initialize UI components
     */
    initializeUI() {
        // Initialize mobile sidebar
        this.initMobileSidebar();
        
        // Enable mobile enhancements
        if (window.innerWidth <= 768) {
            this.enableMobileEnhancements();
        }
        
        // Logout button
        document.getElementById('btnLogout')?.addEventListener('click', () => {
            this.confirmLogout();
        });
        
        // Refresh button
        document.getElementById('btnRefresh')?.addEventListener('click', () => {
            Router.handleRoute();
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                this.closeMobileSidebar();
                document.body.classList.remove('mobile-enhanced');
            } else {
                this.enableMobileEnhancements();
            }
        });

        // Add swipe from edge to open sidebar
        this.initEdgeSwipe();
    },

    /**
     * Initialize mobile sidebar functionality
     */
    initMobileSidebar() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        // Create backdrop element
        const backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        backdrop.id = 'sidebarBackdrop';
        document.body.appendChild(backdrop);

        if (!menuToggle || !sidebar) return;
        
        // Menu toggle click handler
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggleMobileSidebar();
        });
        
        // Backdrop click handler
        backdrop.addEventListener('click', () => {
            this.closeMobileSidebar();
        });

        // Touch events for sidebar
        let startX, startY, currentX, currentY, isDragging = false;
        
        sidebar.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            isDragging = false;
        }, { passive: true });
        
        sidebar.addEventListener('touchmove', (e) => {
            if (!startX) return;
            
            currentX = e.touches[0].clientX;
            currentY = e.touches[0].clientY;
            
            const deltaX = startX - currentX;
            const deltaY = Math.abs(startY - currentY);
            
            // Only handle horizontal swipes
            if (deltaY < 50 && Math.abs(deltaX) > 10) {
                isDragging = true;
                e.preventDefault();
            }
        }, { passive: false });
        
        sidebar.addEventListener('touchend', (e) => {
            if (isDragging && startX && currentX) {
                const deltaX = startX - currentX;
                
                // Swipe left to close (only if sidebar is open)
                if (deltaX > 50 && sidebar.classList.contains('mobile-open')) {
                    this.closeMobileSidebar();
                }
            }
            
            startX = null;
            startY = null;
            currentX = null;
            currentY = null;
            isDragging = false;
        });

        // Close sidebar when clicking nav links on mobile
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    setTimeout(() => this.closeMobileSidebar(), 150);
                }
            });
        });

        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && window.innerWidth <= 768) {
                this.closeMobileSidebar();
            }
        });
    },

    /**
     * Toggle mobile sidebar
     */
    toggleMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const menuToggle = document.getElementById('menuToggle');
        
        if (!sidebar || !backdrop) return;
        
        const isOpen = sidebar.classList.contains('mobile-open');
        
        if (isOpen) {
            this.closeMobileSidebar();
        } else {
            this.openMobileSidebar();
        }
    },

    /**
     * Open mobile sidebar
     */
    openMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const menuToggle = document.getElementById('menuToggle');
        
        if (!sidebar || !backdrop) return;
        
        // Add classes with slight delay for smooth animation
        sidebar.classList.add('mobile-open');
        backdrop.classList.add('show');
        menuToggle?.classList.add('active');
        
        // Prevent body scroll when sidebar is open
        document.body.style.overflow = 'hidden';
        document.body.classList.add('mobile-menu-open');
        
        // Focus first nav item for accessibility
        setTimeout(() => {
            const firstNavLink = sidebar.querySelector('.nav-link');
            firstNavLink?.focus();
        }, 300);
    },

    /**
     * Close mobile sidebar
     */
    closeMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const menuToggle = document.getElementById('menuToggle');
        
        if (!sidebar || !backdrop) return;
        
        sidebar.classList.remove('mobile-open');
        backdrop.classList.remove('show');
        menuToggle?.classList.remove('active');
        
        // Restore body scroll
        document.body.style.overflow = '';
        document.body.classList.remove('mobile-menu-open');
    },

    /**
     * Initialize edge swipe to open sidebar
     */
    initEdgeSwipe() {
        let startX, startY, startTime;
        const edgeThreshold = 20; // pixels from edge
        const minSwipeDistance = 100;
        const maxSwipeTime = 300;

        document.addEventListener('touchstart', (e) => {
            if (window.innerWidth > 768) return;
            
            const touch = e.touches[0];
            startX = touch.clientX;
            startY = touch.clientY;
            startTime = Date.now();
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            if (window.innerWidth > 768 || !startX) return;
            
            const touch = e.changedTouches[0];
            const endX = touch.clientX;
            const endY = touch.clientY;
            const endTime = Date.now();
            
            const deltaX = endX - startX;
            const deltaY = Math.abs(endY - startY);
            const deltaTime = endTime - startTime;
            
            // Check if it's a valid edge swipe
            const isFromLeftEdge = startX <= edgeThreshold;
            const isRightSwipe = deltaX >= minSwipeDistance;
            const isFastEnough = deltaTime <= maxSwipeTime;
            const isHorizontal = deltaY <= 50;
            const sidebar = document.getElementById('sidebar');
            const isNotOpen = !sidebar?.classList.contains('mobile-open');
            
            if (isFromLeftEdge && isRightSwipe && isFastEnough && isHorizontal && isNotOpen) {
                e.preventDefault();
                this.openMobileSidebar();
            }
            
            // Reset
            startX = null;
            startY = null;
            startTime = null;
        }, { passive: false });
    },
    
    /**
     * Load user info
     */
    async loadUserInfo() {
        try {
            const user = Auth.getUser();
            if (user) {
                const emailEl = document.getElementById('userEmail');
                if (emailEl) {
                    emailEl.textContent = user.email;
                }
                console.log('User info loaded:', user.email);
            } else {
                console.log('No user info found in localStorage');
            }
        } catch (error) {
            console.error('Failed to load user info:', error);
        }
    },
    
    /**
     * Confirm logout
     */
    confirmLogout() {
        if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
            Auth.logout();
        }
    },
    
    /**
     * Show toast notification
     */
    showToast(type, title, message) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icons[type]}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
        `;
        
        container.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s forwards';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    },
    
    /**
     * Show modal
     */
    showModal(title, content, footer = '') {
        const container = document.getElementById('modalContainer');
        
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">${title}</h3>
                    <button class="modal-close" onclick="App.closeModal(this)">&times;</button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
            </div>
        `;
        
        container.appendChild(modal);
        
        // Close on overlay click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        return modal;
    },
    
    /**
     * Close modal
     */
    closeModal(button) {
        const modal = button.closest('.modal-overlay');
        if (modal) {
            modal.remove();
        }
    },
    
    /**
     * Format currency
     */
    formatCurrency(amount) {
        // Handle null/undefined/NaN values
        if (amount === null || amount === undefined || isNaN(amount)) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(0);
        }
        
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount / 100);
    },
    
    /**
     * Format date
     */
    formatDate(timestamp) {
        if (!timestamp) return '-';
        const date = new Date(timestamp * 1000);
        return date.toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    /**
     * Format bytes to human readable
     */
    formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 GB';
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    },
    
    /**
     * Copy to clipboard
     */
    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showToast('success', 'Thành công', 'Đã sao chép vào clipboard');
        }).catch(() => {
            this.showToast('error', 'Lỗi', 'Không thể sao chép');
        });
    },
    
    /**
     * Generate QR Code
     */
    showQRCode(text) {
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(text)}`;
        this.showModal(
            'QR Code',
            `<div class="text-center">
                <img src="${qrUrl}" alt="QR Code" style="max-width: 100%;">
                <div class="mt-2">
                    <button class="btn btn-primary" onclick="App.copyToClipboard('${text}')">
                        <i class="fas fa-copy"></i> Copy Link
                    </button>
                </div>
            </div>`
        );
    },
    
    /**
     * Create pagination
     */
    createPagination(currentPage, totalPages, onPageChange) {
        let html = '<div class="pagination">';
        
        // Previous button
        if (currentPage > 1) {
            html += `<a class="page-link" onclick="${onPageChange}(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>`;
        }
        
        // Page numbers
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            html += `<a class="page-link" onclick="${onPageChange}(1)">1</a>`;
            if (startPage > 2) {
                html += '<span class="page-link disabled">...</span>';
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<a class="page-link ${i === currentPage ? 'active' : ''}" 
                     onclick="${onPageChange}(${i})">${i}</a>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += '<span class="page-link disabled">...</span>';
            }
            html += `<a class="page-link" onclick="${onPageChange}(${totalPages})">${totalPages}</a>`;
        }
        
        // Next button
        if (currentPage < totalPages) {
            html += `<a class="page-link" onclick="${onPageChange}(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>`;
        }
        
        html += '</div>';
        return html;
    },
    
    /**
     * Create mobile-friendly search box
     */
    createMobileSearch(options) {
        const {
            searchTypes = [{ value: 'email', text: 'Email' }],
            placeholder = 'Nhập từ khóa tìm kiếm...',
            onSearch,
            onReset
        } = options;
        
        const searchTypeOptions = searchTypes.map(type => 
            `<option value="${type.value}">${type.text}</option>`
        ).join('');
        
        return `
            <div class="mobile-search-container">
                <div class="mobile-search-row">
                    <select class="form-control" id="mobileSearchType">
                        ${searchTypeOptions}
                    </select>
                    <input type="text" class="form-control" id="mobileSearchInput" 
                           placeholder="${placeholder}">
                </div>
                <div class="mobile-search-actions">
                    <button class="btn btn-primary" onclick="${onSearch}()">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                    <button class="btn btn-outline" onclick="${onReset}()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
        `;
    },
    
    /**
     * Create mobile-friendly filter box
     */
    createMobileFilters(options) {
        const {
            filters = [],
            onApply,
            onReset,
            additionalActions = []
        } = options;
        
        let filtersHtml = '';
        
        filters.forEach((filter, index) => {
            if (filter.type === 'select') {
                const options = filter.options.map(opt => 
                    `<option value="${opt.value}">${opt.text}</option>`
                ).join('');
                
                filtersHtml += `
                    <div class="mobile-search-row" style="flex-direction: column; align-items: stretch; gap: 6px;">
                        <label class="form-label">${filter.label}:</label>
                        <select class="form-control" id="${filter.id}">
                            ${options}
                        </select>
                    </div>
                `;
            } else if (filter.type === 'text') {
                filtersHtml += `
                    <div class="mobile-search-row" style="flex-direction: column; align-items: stretch; gap: 6px;">
                        <label class="form-label">${filter.label}:</label>
                        <input type="text" class="form-control" id="${filter.id}" 
                               placeholder="${filter.placeholder || ''}">
                    </div>
                `;
            } else if (filter.type === 'date') {
                filtersHtml += `
                    <div class="mobile-search-row" style="flex-direction: column; align-items: stretch; gap: 6px;">
                        <label class="form-label">${filter.label}:</label>
                        <input type="date" class="form-control" id="${filter.id}">
                    </div>
                `;
            }
        });
        
        let actionsHtml = `
            <button class="btn btn-primary" onclick="${onApply}()">
                <i class="fas fa-filter"></i> Áp dụng
            </button>
            <button class="btn btn-outline" onclick="${onReset}()">
                <i class="fas fa-redo"></i> Reset
            </button>
        `;
        
        additionalActions.forEach(action => {
            actionsHtml += `
                <button class="btn ${action.class || 'btn-secondary'}" onclick="${action.onclick}">
                    ${action.icon ? `<i class="${action.icon}"></i>` : ''} ${action.text}
                </button>
            `;
        });
        
        return `
            <div class="mobile-search-container">
                ${filtersHtml}
                <div class="mobile-search-actions">
                    ${actionsHtml}
                </div>
            </div>
        `;
    },
    
    /**
     * Create mobile card table
     */
    createMobileCardTable(data, config) {
        const { 
            idField = 'id',
            titleField = 'email',
            fields = [],
            actions = [],
            formatters = {}
        } = config;
        
        if (!data || data.length === 0) {
            return `
                <div class="mobile-card-table">
                    <div class="alert alert-info">
                        Không có dữ liệu để hiển thị
                    </div>
                </div>
            `;
        }
        
        let html = '<div class="mobile-card-table">';
        
        data.forEach(item => {
            const id = item[idField];
            const title = item[titleField] || `#${id}`;
            
            html += `
                <div class="mobile-card-item">
                    <div class="mobile-card-header">
                        <div class="mobile-card-title">${title}</div>
                        <div class="mobile-card-id">#${id}</div>
                    </div>
                    <div class="mobile-card-body">
            `;
            
            // Render fields
            fields.forEach(field => {
                let value = item[field.key];
                
                // Apply formatter if exists
                if (formatters[field.key]) {
                    value = formatters[field.key](value, item);
                } else if (typeof value === 'boolean') {
                    value = value ? '✓' : '✗';
                } else if (value === null || value === undefined) {
                    value = '-';
                }
                
                html += `
                    <div class="mobile-card-field">
                        <div class="mobile-card-label">${field.label}</div>
                        <div class="mobile-card-value">${value}</div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Render actions
            if (actions && actions.length > 0) {
                html += '<div class="mobile-card-actions">';
                
                // If more than 3 actions, use dropdown, otherwise use individual buttons
                if (actions.length > 3) {
                    html += `
                        <div class="dropdown" style="width: 100%;">
                            <button class="btn btn-primary dropdown-toggle" type="button" 
                                    onclick="App.toggleMobileDropdown(this)"
                                    style="width: 100%;">
                                <i class="fas fa-cog"></i> Hành động
                            </button>
                            <ul class="dropdown-menu" style="width: 100%;">
                    `;
                    
                    actions.forEach(action => {
                        const onclick = typeof action.onclick === 'function' 
                            ? action.onclick(item) 
                            : action.onclick.replace('{id}', id);
                            
                        html += `
                            <li><a class="dropdown-item" onclick="App.closeMobileDropdown(this); ${onclick}">
                                ${action.icon ? `<i class="${action.icon}"></i>` : ''} 
                                ${action.text}
                            </a></li>
                        `;
                    });
                    
                    html += '</ul></div>';
                } else {
                    // Use individual buttons for 3 or fewer actions
                    actions.forEach(action => {
                        const onclick = typeof action.onclick === 'function' 
                            ? action.onclick(item) 
                            : action.onclick.replace('{id}', id);
                            
                        html += `
                            <button class="btn ${action.class || 'btn-primary'}" 
                                    onclick="${onclick}" style="flex: 1; margin: 0 2px;">
                                ${action.icon ? `<i class="${action.icon}"></i>` : ''} 
                                ${action.text}
                            </button>
                        `;
                    });
                }
                
                html += '</div>';
            }
            
            html += '</div>';
        });
        
        html += '</div>';
        return html;
    },
    
    /**
     * Enable mobile-enhanced features
     */
    enableMobileEnhancements() {
        // Add mobile classes to containers
        document.body.classList.add('mobile-enhanced');
        
        const statsGrid = document.querySelector('.stats-grid');
        if (statsGrid) {
            statsGrid.classList.add('mobile-stats-enhanced');
        }
        
        // Enable pull-to-refresh
        this.initPullToRefresh();
        
        // Enhanced modal behavior
        document.addEventListener('click', (e) => {
            if (e.target.closest('.modal-overlay')) {
                const overlay = e.target.closest('.modal-overlay');
                overlay.classList.add('mobile-modal-enhanced');
            }
        });
    },
    
    /**
     * Initialize pull-to-refresh
     */
    initPullToRefresh() {
        let startY = 0;
        let currentY = 0;
        let pullDistance = 0;
        let isPulling = false;
        let indicator = null;
        
        // Create indicator if not exists
        if (!document.getElementById('pullRefreshIndicator')) {
            indicator = document.createElement('div');
            indicator.id = 'pullRefreshIndicator';
            indicator.className = 'pull-refresh-indicator';
            indicator.textContent = 'Kéo để làm mới';
            document.body.appendChild(indicator);
        } else {
            indicator = document.getElementById('pullRefreshIndicator');
        }
        
        const pageContent = document.getElementById('pageContent');
        if (!pageContent) return;
        
        pageContent.addEventListener('touchstart', (e) => {
            if (pageContent.scrollTop === 0) {
                startY = e.touches[0].clientY;
                isPulling = false;
            }
        }, { passive: true });
        
        pageContent.addEventListener('touchmove', (e) => {
            if (startY === 0 || pageContent.scrollTop > 0) return;
            
            currentY = e.touches[0].clientY;
            pullDistance = currentY - startY;
            
            if (pullDistance > 50 && !isPulling) {
                isPulling = true;
                indicator.classList.add('show');
                indicator.textContent = 'Thả để làm mới';
            } else if (pullDistance < 50 && isPulling) {
                isPulling = false;
                indicator.classList.remove('show');
                indicator.textContent = 'Kéo để làm mới';
            }
        }, { passive: true });
        
        pageContent.addEventListener('touchend', () => {
            if (isPulling && pullDistance > 80) {
                indicator.textContent = 'Đang làm mới...';
                
                // Trigger refresh
                setTimeout(() => {
                    indicator.classList.remove('show');
                    Router.handleRoute(); // Refresh current page
                }, 1000);
            } else {
                indicator.classList.remove('show');
            }
            
            startY = 0;
            currentY = 0;
            pullDistance = 0;
            isPulling = false;
        }, { passive: true });
    },
    
    /**
     * Close mobile dropdown (called when clicking on dropdown items)
     */
    closeMobileDropdown(element) {
        const dropdown = element.closest('.dropdown');
        if (dropdown) {
            const menu = dropdown.querySelector('.dropdown-menu');
            const card = dropdown.closest('.mobile-card-item');
            
            if (menu) {
                menu.classList.remove('show');
            }
            if (card) {
                card.classList.remove('dropdown-active');
            }
        }
    },

    /**
     * Toggle mobile dropdown for generic mobile card tables
     */
    toggleMobileDropdown(button) {
        // Close all other dropdowns first
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            if (menu.parentNode !== button.parentNode) {
                menu.classList.remove('show');
                // Remove dropdown-active class from other cards
                const otherCard = menu.closest('.mobile-card-item');
                if (otherCard) {
                    otherCard.classList.remove('dropdown-active');
                }
            }
        });

        // Toggle current dropdown
        const menu = button.nextElementSibling;
        const card = button.closest('.mobile-card-item');
        
        menu.classList.toggle('show');

        // Handle z-index for mobile cards
        if (menu.classList.contains('show')) {
            if (card) {
                card.classList.add('dropdown-active');
            }
        } else {
            if (card) {
                card.classList.remove('dropdown-active');
            }
        }

        // Close dropdown when clicking outside
        if (menu.classList.contains('show')) {
            setTimeout(() => {
                document.addEventListener('click', function closeDropdown(e) {
                    if (!button.contains(e.target) && !menu.contains(e.target)) {
                        menu.classList.remove('show');
                        if (card) {
                            card.classList.remove('dropdown-active');
                        }
                        document.removeEventListener('click', closeDropdown);
                    }
                });
            }, 0);
        }
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
