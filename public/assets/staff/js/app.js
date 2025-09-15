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
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
