/**
 * Dashboard Module
 */

const Dashboard = {
    data: {
        info: null,
        stats: null,
        orderStats: null
    },
    
    /**
     * Render dashboard page
     */
    async render() {
        const content = document.getElementById('pageContent');
        
        // Initial layout
        content.innerHTML = `
            <div class="stats-grid" id="statsGrid">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Thống kê doanh thu</h3>
                        </div>
                        <div class="card-body" id="revenueChart">
                            <div class="loading-container">
                                <div class="loading-spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Trạng thái đơn hàng</h3>
                        </div>
                        <div class="card-body" id="orderStatus">
                            <div class="loading-container">
                                <div class="loading-spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Hoạt động gần đây</h3>
                </div>
                <div class="card-body" id="recentActivity">
                    <div class="loading-container">
                        <div class="loading-spinner"></div>
                    </div>
                </div>
            </div>
        `;
        
        // Load data
        try {
            await Dashboard.loadData();
            Dashboard.renderStats();
            Dashboard.renderCharts();
            Dashboard.renderRecentActivity();
        } catch (error) {
            console.error('Error in dashboard render:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <h4>Lỗi tải dashboard</h4>
                    <p>Có lỗi xảy ra: ${error.message}</p>
                    <button onclick="location.reload()" class="btn btn-primary">Tải lại</button>
                </div>
            `;
        }
    },
    
    /**
     * Load dashboard data
     */
    async loadData() {
        try {
            console.log('Loading dashboard data...');
            // Load all data in parallel
            const [info, stats, orderStats, orderSummary] = await Promise.all([
                API.dashboard.getInfo(),
                API.dashboard.getStats(),
                API.orders.getStats(),
                API.orders.getSummary()
            ]);
            
            // Extract data from API responses - staff APIs return {status: "success", data: {...}}
            Dashboard.data.info = info.data || info;
            Dashboard.data.stats = stats.data || stats;
            Dashboard.data.orderStats = orderStats.data || orderStats;
            Dashboard.data.orderSummary = orderSummary.data || orderSummary;
            console.log('Dashboard data loaded successfully');
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            // Don't show toast in case it's not loaded yet
            if (typeof App !== 'undefined' && App.showToast) {
                App.showToast('error', 'Lỗi', 'Không thể tải dữ liệu dashboard');
            }
            // Create simple error display
            document.getElementById('pageContent').innerHTML = `
                <div class="alert alert-danger">
                    <h4>Lỗi tải dữ liệu</h4>
                    <p>Không thể kết nối đến server. Vui lòng thử lại sau.</p>
                    <button onclick="location.reload()" class="btn btn-primary">Tải lại</button>
                </div>
            `;
        }
    },
    
    /**
     * Render statistics cards
     */
    renderStats() {
        const statsGrid = document.getElementById('statsGrid');
        const { info, stats, orderStats } = Dashboard.data;
        
        console.log('Dashboard.data:', Dashboard.data);
        console.log('Info data:', info);
        
        if (!info || !stats || !orderStats) {
            console.log('Missing data for stats rendering');
            statsGrid.innerHTML = '<p>Không thể tải thống kê</p>';
            return;
        }
        
        const statsCards = [
            {
                icon: 'fa-wallet',
                label: 'Số dư',
                value: App.formatCurrency(info.balance || 0),
                color: 'primary'
            },
            {
                icon: 'fa-coins',
                label: 'Hoa hồng có thể rút',
                value: App.formatCurrency(info.commission_balance || 0),
                color: 'success'
            },
            {
                icon: 'fa-percentage',
                label: 'Tỷ lệ hoa hồng',
                value: `${info.commission_rate || 0}%`,
                color: 'warning'
            },
            {
                icon: 'fa-tag',
                label: 'Chiết khấu độc quyền',
                value: `${info.discount || 0}%`,
                color: 'info'
            },
            {
                icon: 'fa-chart-line',
                label: 'Doanh thu hôm nay',
                value: App.formatCurrency(orderStats.today?.amount || 0),
                change: `${orderStats.today?.count || 0} đơn`,
                color: 'success'
            },
            {
                icon: 'fa-calendar-alt',
                label: 'Doanh thu tháng này',
                value: App.formatCurrency(orderStats.month?.amount || 0),
                change: `${orderStats.month?.count || 0} đơn`,
                color: 'primary'
            },
            {
                icon: 'fa-users',
                label: 'Người dùng mới',
                value: stats.new_users || 0,
                change: 'Tháng này',
                color: 'info'
            },
            {
                icon: 'fa-shopping-cart',
                label: 'Đơn hàng chờ',
                value: orderStats.pending_count || 0,
                color: 'warning'
            }
        ];
        
        statsGrid.innerHTML = statsCards.map(stat => `
            <div class="stat-card">
                <div class="stat-icon ${stat.color}">
                    <i class="fas ${stat.icon}"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">${stat.label}</div>
                    <div class="stat-value">${stat.value}</div>
                    ${stat.change ? `<div class="stat-change">${stat.change}</div>` : ''}
                </div>
            </div>
        `).join('');
    },
    
    /**
     * Render charts
     */
    renderCharts() {
        // Revenue chart placeholder
        const revenueChart = document.getElementById('revenueChart');
        const { orderStats } = Dashboard.data;
        
        console.log('Rendering charts with orderStats:', orderStats);
        
        if (!orderStats) {
            revenueChart.innerHTML = '<p>Không có dữ liệu</p>';
            return;
        }
        
        const orderStatsData = orderStats.data || orderStats;
        
        // Simple revenue summary
        revenueChart.innerHTML = `
            <div class="revenue-summary">
                <div class="revenue-item">
                    <span class="revenue-label">Tổng doanh thu:</span>
                    <span class="revenue-value">${App.formatCurrency(orderStatsData.total?.amount || 0)}</span>
                </div>
                <div class="revenue-item">
                    <span class="revenue-label">Tổng đơn hàng:</span>
                    <span class="revenue-value">${orderStatsData.total?.count || 0}</span>
                </div>
                <div class="revenue-item">
                    <span class="revenue-label">Tổng hoa hồng:</span>
                    <span class="revenue-value">${App.formatCurrency(orderStatsData.total_commission || 0)}</span>
                </div>
            </div>
            <style>
                .revenue-summary {
                    padding: 20px 0;
                }
                .revenue-item {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid var(--border-color);
                }
                .revenue-item:last-child {
                    border-bottom: none;
                }
                .revenue-label {
                    color: var(--text-color);
                }
                .revenue-value {
                    font-weight: 600;
                    color: var(--dark-color);
                }
            </style>
        `;
        
        // Order status chart
        const orderStatusEl = document.getElementById('orderStatus');
        let orderSummary = Dashboard.data.orderSummary;
        
        console.log('Rendering order status with:', orderSummary);
        
        if (!orderSummary) {
            orderStatusEl.innerHTML = '<p>Không có dữ liệu</p>';
            return;
        }
        
        // Handle nested data structure
        if (orderSummary.data) {
            orderSummary = orderSummary.data;
        }
        
        const statusLabels = {
            pending: { label: 'Chờ xử lý', color: '#f6ad55' },
            processing: { label: 'Đang xử lý', color: '#4299e1' },
            completed: { label: 'Hoàn thành', color: '#48bb78' },
            cancelled: { label: 'Đã hủy', color: '#f56565' },
            discounted: { label: 'Đã giảm giá', color: '#9f7aea' }
        };
        
        let statusHtml = '<div class="status-list">';
        for (const [key, data] of Object.entries(orderSummary)) {
            const status = statusLabels[key] || { label: key, color: '#a0aec0' };
            statusHtml += `
                <div class="status-item">
                    <div class="status-info">
                        <span class="status-dot" style="background: ${status.color}"></span>
                        <span class="status-label">${status.label}</span>
                    </div>
                    <div class="status-value">
                        <span class="status-count">${data.count || 0}</span>
                        <span class="status-amount">${App.formatCurrency(data.amount || 0)}</span>
                    </div>
                </div>
            `;
        }
        statusHtml += '</div>';
        
        orderStatusEl.innerHTML = statusHtml + `
            <style>
                .status-list {
                    padding: 10px 0;
                }
                .status-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 8px 0;
                    border-bottom: 1px solid var(--border-color);
                }
                .status-item:last-child {
                    border-bottom: none;
                }
                .status-info {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .status-dot {
                    width: 10px;
                    height: 10px;
                    border-radius: 50%;
                }
                .status-value {
                    text-align: right;
                }
                .status-count {
                    font-weight: 600;
                    margin-right: 10px;
                }
                .status-amount {
                    color: #718096;
                    font-size: 13px;
                }
            </style>
        `;
    },
    
    /**
     * Render recent activity
     */
    async renderRecentActivity() {
        const activityEl = document.getElementById('recentActivity');
        
        try {
            // Get recent orders
            const orders = await API.orders.fetch({ limit: 5 });
            
            if (!orders.data || orders.data.length === 0) {
                activityEl.innerHTML = '<p>Không có hoạt động gần đây</p>';
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Email</th>
                                <th>Gói</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            orders.data.forEach(order => {
                const statusClass = order.status === 3 ? 'text-success' : 
                                  order.status === 0 ? 'text-warning' : 'text-info';
                const statusText = order.status === 0 ? 'Chờ' :
                                 order.status === 1 ? 'Xử lý' :
                                 order.status === 2 ? 'Hủy' :
                                 order.status === 3 ? 'Hoàn thành' : 'Khác';
                
                html += `
                    <tr>
                        <td>#${order.trade_no}</td>
                        <td>${order.user_email || '-'}</td>
                        <td>${order.plan_name || '-'}</td>
                        <td>${App.formatCurrency(order.total_amount)}</td>
                        <td><span class="${statusClass}">${statusText}</span></td>
                        <td>${App.formatDate(order.created_at)}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="#/orders" class="btn btn-outline">
                        Xem tất cả đơn hàng <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            `;
            
            activityEl.innerHTML = html;
        } catch (error) {
            console.error('Failed to load recent activity:', error);
            activityEl.innerHTML = '<p>Không thể tải hoạt động gần đây</p>';
        }
    }
};
