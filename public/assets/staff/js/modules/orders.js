/**
 * Orders Management Module
 */

const Orders = {
    currentPage: 1,
    pageSize: 20,
    filters: {},
    
    /**
     * Render orders page
     */
    async render() {
        const content = document.getElementById('pageContent');
        
        // Create mobile filters
        const mobileFilters = App.createMobileFilters({
            filters: [
                {
                    id: 'mobileFilterStatus',
                    type: 'select',
                    label: 'Trạng thái',
                    options: [
                        { value: '', text: 'Tất cả trạng thái' },
                        { value: '0', text: 'Chờ thanh toán' },
                        { value: '1', text: 'Đang xử lý' },
                        { value: '2', text: 'Đã hủy' },
                        { value: '3', text: 'Hoàn thành' },
                        { value: '4', text: 'Đã giảm giá' }
                    ]
                },
                {
                    id: 'mobileFilterTradeNo',
                    type: 'text',
                    label: 'Mã đơn hàng',
                    placeholder: 'Nhập mã đơn hàng'
                },
                {
                    id: 'mobileFilterUserId',
                    type: 'text',
                    label: 'User ID',
                    placeholder: 'Nhập User ID'
                },
                {
                    id: 'mobileFilterStartDate',
                    type: 'date',
                    label: 'Từ ngày'
                },
                {
                    id: 'mobileFilterEndDate',
                    type: 'date',
                    label: 'Đến ngày'
                }
            ],
            onApply: 'Orders.mobileApplyFilters',
            onReset: 'Orders.mobileResetFilters',
            additionalActions: [
                {
                    text: 'Gán đơn hàng',
                    icon: 'fas fa-plus',
                    class: 'btn-success',
                    onclick: 'Orders.showAssignModal()'
                }
            ]
        });
        
        content.innerHTML = `
            <div class="stats-grid mb-3" id="orderStats">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Danh sách đơn hàng</h3>
                    <button class="btn btn-success" onclick="Orders.showAssignModal()">
                        <i class="fas fa-plus"></i> Gán đơn hàng
                    </button>
                </div>
                <div class="card-body">
                    ${mobileFilters}
                    
                    <div class="filters mb-3">
                        <div class="d-flex gap-2" style="flex-wrap: wrap;">
                            <select class="form-control" id="filterStatus" style="width: 150px;">
                                <option value="">Tất cả trạng thái</option>
                                <option value="0">Chờ thanh toán</option>
                                <option value="1">Đang xử lý</option>
                                <option value="2">Đã hủy</option>
                                <option value="3">Hoàn thành</option>
                                <option value="4">Đã giảm giá</option>
                            </select>
                            
                            <input type="text" class="form-control" id="filterTradeNo" 
                                   placeholder="Mã đơn hàng" style="width: 200px;">
                            
                            <input type="text" class="form-control" id="filterUserId" 
                                   placeholder="User ID" style="width: 120px;">
                            
                            <input type="date" class="form-control" id="filterStartDate" style="width: 150px;">
                            <input type="date" class="form-control" id="filterEndDate" style="width: 150px;">
                            
                            <button class="btn btn-primary" onclick="Orders.applyFilters()">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                            <button class="btn btn-outline" onclick="Orders.resetFilters()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                    
                    <div id="ordersTable">
                        <div class="loading-container">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        await Orders.loadStats();
        await Orders.loadOrders();
    },
    
    /**
     * Load order statistics
     */
    async loadStats() {
        try {
            const stats = await API.orders.getStats();
            
            const statsHtml = `
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Doanh thu hôm nay</div>
                        <div class="stat-value">${App.formatCurrency(stats.today.amount)}</div>
                        <div class="stat-change">${stats.today.count} đơn</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Doanh thu tháng</div>
                        <div class="stat-value">${App.formatCurrency(stats.month.amount)}</div>
                        <div class="stat-change">${stats.month.count} đơn</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Tổng doanh thu</div>
                        <div class="stat-value">${App.formatCurrency(stats.total.amount)}</div>
                        <div class="stat-change">${stats.total.count} đơn</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Đơn chờ xử lý</div>
                        <div class="stat-value">${stats.pending_count}</div>
                        <div class="stat-change">Cần xử lý</div>
                    </div>
                </div>
            `;
            
            document.getElementById('orderStats').innerHTML = statsHtml;
        } catch (error) {
            console.error('Failed to load order stats:', error);
            document.getElementById('orderStats').innerHTML = '';
        }
    },
    
    /**
     * Load orders list
     */
    async     loadOrders(page = 1) {
        Orders.currentPage = page;
        
        try {
            const params = {
                page: Orders.currentPage,
                limit: Orders.pageSize,
                ...Orders.filters
            };
            
            const response = await API.orders.fetch(params);
            Orders.renderTable(response);
        } catch (error) {
            console.error('Failed to load orders:', error);
            document.getElementById('ordersTable').innerHTML = `
                <div class="alert alert-danger">
                    Không thể tải danh sách đơn hàng
                </div>
            `;
        }
    },
    
    /**
     * Render orders table
     */
    renderTable(response) {
        const { data, total, current, pageSize } = response;
        const totalPages = Math.ceil(total / pageSize);
        
        if (!data || data.length === 0) {
            document.getElementById('ordersTable').innerHTML = `
                <div class="alert alert-info">
                    Không tìm thấy đơn hàng nào
                </div>
            `;
            return;
        }
        
        // Generate mobile card table
        let mobileCardsHtml = '<div class="mobile-card-table">';
        
        data.forEach(order => {
            const statusClass = Orders.getStatusClass(order.status);
            const statusText = Orders.getStatusText(order.status);
            const typeText = Orders.getTypeText(order.type);
            const periodText = Orders.getPeriodText(order.period);
            
            mobileCardsHtml += `
                <div class="mobile-card-item">
                    <div class="mobile-card-header">
                        <div class="mobile-card-title">#${order.trade_no}</div>
                        <div class="mobile-card-id"><span class="${statusClass}">${statusText}</span></div>
                    </div>
                    <div class="mobile-card-body">
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">NGƯỜI DÙNG</div>
                            <div class="mobile-card-value">${order.user_email || `ID: ${order.user_id}`}</div>
                        </div>
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">GÓI DỊCH VỤ</div>
                            <div class="mobile-card-value">${order.plan_name || '-'}</div>
                        </div>
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">CHU KỲ</div>
                            <div class="mobile-card-value">${periodText}</div>
                        </div>
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">LOẠI</div>
                            <div class="mobile-card-value">${typeText}</div>
                        </div>
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">SỐ TIỀN</div>
                            <div class="mobile-card-value">${App.formatCurrency(order.total_amount)}</div>
                        </div>
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">THỜI GIAN</div>
                            <div class="mobile-card-value">${App.formatDate(order.created_at)}</div>
                        </div>
                    </div>
                    <div class="mobile-card-actions">
                        <button class="btn btn-primary" onclick="Orders.viewDetail(${order.id})" style="width: 100%;">
                            <i class="fas fa-eye"></i> Chi tiết đơn hàng
                        </button>
                    </div>
                </div>
            `;
        });
        
        mobileCardsHtml += '</div>';
        const mobileCards = mobileCardsHtml;
        
        // Generate desktop table
        let html = `
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Người dùng</th>
                            <th>Gói dịch vụ</th>
                            <th>Chu kỳ</th>
                            <th>Loại</th>
                            <th>Số tiền</th>
                            <th>Hoa hồng</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        data.forEach(order => {
            const statusClass = Orders.getStatusClass(order.status);
            const statusText = Orders.getStatusText(order.status);
            const typeText = Orders.getTypeText(order.type);
            const periodText = Orders.getPeriodText(order.period);
            
            html += `
                <tr>
                    <td>#${order.trade_no}</td>
                    <td>${order.user_email || `ID: ${order.user_id}`}</td>
                    <td>${order.plan_name || '-'}</td>
                    <td>${periodText}</td>
                    <td>${typeText}</td>
                    <td>${App.formatCurrency(order.total_amount)}</td>
                    <td>${App.formatCurrency(order.commission_balance || 0)}</td>
                    <td><span class="${statusClass}">${statusText}</span></td>
                    <td>${App.formatDate(order.created_at)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="Orders.viewDetail(${order.id})" title="Chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        // Combine mobile and desktop views
        let combinedHtml = mobileCards + html;
        
        // Add pagination
        if (totalPages > 1) {
            combinedHtml += App.createPagination(current, totalPages, 'Orders.loadOrders');
        }
        
        // Show total records
        combinedHtml += `
            <div class="mt-3 text-muted">
                Hiển thị ${(current - 1) * pageSize + 1} - ${Math.min(current * pageSize, total)} trong tổng số ${total} đơn hàng
            </div>
        `;
        
        document.getElementById('ordersTable').innerHTML = combinedHtml;
    },
    
    /**
     * Apply filters
     */
    applyFilters() {
        Orders.filters = {};
        
        const status = document.getElementById('filterStatus').value;
        if (status !== '') {
            Orders.filters.status = status;
        }
        
        const tradeNo = document.getElementById('filterTradeNo').value.trim();
        if (tradeNo) {
            Orders.filters.trade_no = tradeNo;
        }
        
        const userId = document.getElementById('filterUserId').value.trim();
        if (userId) {
            Orders.filters.user_id = userId;
        }
        
        const startDate = document.getElementById('filterStartDate').value;
        if (startDate) {
            Orders.filters.start_date = startDate;
        }
        
        const endDate = document.getElementById('filterEndDate').value;
        if (endDate) {
            Orders.filters.end_date = endDate;
        }
        
        Orders.loadOrders(1);
    },
    
    /**
     * Reset filters
     */
    resetFilters() {
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterTradeNo').value = '';
        document.getElementById('filterUserId').value = '';
        document.getElementById('filterStartDate').value = '';
        document.getElementById('filterEndDate').value = '';
        
        Orders.filters = {};
        Orders.loadOrders(1);
    },

    /**
     * Mobile apply filters
     */
    mobileApplyFilters() {
        Orders.filters = {};
        
        const status = document.getElementById('mobileFilterStatus').value;
        if (status !== '') {
            Orders.filters.status = status;
        }
        
        const tradeNo = document.getElementById('mobileFilterTradeNo').value.trim();
        if (tradeNo) {
            Orders.filters.trade_no = tradeNo;
        }
        
        const userId = document.getElementById('mobileFilterUserId').value.trim();
        if (userId) {
            Orders.filters.user_id = userId;
        }
        
        const startDate = document.getElementById('mobileFilterStartDate').value;
        if (startDate) {
            Orders.filters.start_date = startDate;
        }
        
        const endDate = document.getElementById('mobileFilterEndDate').value;
        if (endDate) {
            Orders.filters.end_date = endDate;
        }
        
        // Sync with desktop filters
        const desktopStatus = document.getElementById('filterStatus');
        const desktopTradeNo = document.getElementById('filterTradeNo');
        const desktopUserId = document.getElementById('filterUserId');
        const desktopStartDate = document.getElementById('filterStartDate');
        const desktopEndDate = document.getElementById('filterEndDate');
        
        if (desktopStatus) desktopStatus.value = status;
        if (desktopTradeNo) desktopTradeNo.value = tradeNo;
        if (desktopUserId) desktopUserId.value = userId;
        if (desktopStartDate) desktopStartDate.value = startDate;
        if (desktopEndDate) desktopEndDate.value = endDate;
        
        Orders.loadOrders(1);
    },

    /**
     * Mobile reset filters
     */
    mobileResetFilters() {
        // Reset mobile filters
        document.getElementById('mobileFilterStatus').value = '';
        document.getElementById('mobileFilterTradeNo').value = '';
        document.getElementById('mobileFilterUserId').value = '';
        document.getElementById('mobileFilterStartDate').value = '';
        document.getElementById('mobileFilterEndDate').value = '';
        
        // Reset desktop filters
        const desktopStatus = document.getElementById('filterStatus');
        const desktopTradeNo = document.getElementById('filterTradeNo');
        const desktopUserId = document.getElementById('filterUserId');
        const desktopStartDate = document.getElementById('filterStartDate');
        const desktopEndDate = document.getElementById('filterEndDate');
        
        if (desktopStatus) desktopStatus.value = '';
        if (desktopTradeNo) desktopTradeNo.value = '';
        if (desktopUserId) desktopUserId.value = '';
        if (desktopStartDate) desktopStartDate.value = '';
        if (desktopEndDate) desktopEndDate.value = '';
        
        Orders.filters = {};
        Orders.loadOrders(1);
    },
    
    /**
     * View order detail
     */
    async viewDetail(orderId) {
        try {
            const order = await API.orders.getDetail(orderId);
            const orderData = order.data;
            
            const content = `
                <div class="order-details">
                    <h4>Thông tin đơn hàng</h4>
                    <table class="detail-table">
                        <tr>
                            <td><strong>Mã đơn hàng:</strong></td>
                            <td>${orderData.trade_no}</td>
                        </tr>
                        <tr>
                            <td><strong>Người dùng:</strong></td>
                            <td>${orderData.user_email || `ID: ${orderData.user_id}`}</td>
                        </tr>
                        <tr>
                            <td><strong>Gói dịch vụ:</strong></td>
                            <td>${orderData.plan_name || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Chu kỳ:</strong></td>
                            <td>${Orders.getPeriodText(orderData.period)}</td>
                        </tr>
                        <tr>
                            <td><strong>Loại:</strong></td>
                            <td>${Orders.getTypeText(orderData.type)}</td>
                        </tr>
                        <tr>
                            <td><strong>Số tiền:</strong></td>
                            <td>${App.formatCurrency(orderData.total_amount)}</td>
                        </tr>
                        <tr>
                            <td><strong>Giảm giá:</strong></td>
                            <td>${App.formatCurrency(orderData.discount_amount || 0)}</td>
                        </tr>
                        <tr>
                            <td><strong>Thặng dư:</strong></td>
                            <td>${App.formatCurrency(orderData.surplus_amount || 0)}</td>
                        </tr>
                        <tr>
                            <td><strong>Hoàn tiền:</strong></td>
                            <td>${App.formatCurrency(orderData.refund_amount || 0)}</td>
                        </tr>
                        <tr>
                            <td><strong>Số dư sử dụng:</strong></td>
                            <td>${App.formatCurrency(orderData.balance_amount || 0)}</td>
                        </tr>
                        <tr>
                            <td><strong>Hoa hồng:</strong></td>
                            <td>${App.formatCurrency(orderData.commission_balance || 0)}</td>
                        </tr>
                        <tr>
                            <td><strong>Phương thức thanh toán:</strong></td>
                            <td>${orderData.payment_id || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Mã coupon:</strong></td>
                            <td>${orderData.coupon_id || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Trạng thái:</strong></td>
                            <td><span class="${Orders.getStatusClass(orderData.status)}">${Orders.getStatusText(orderData.status)}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Ngày tạo:</strong></td>
                            <td>${App.formatDate(orderData.created_at)}</td>
                        </tr>
                        <tr>
                            <td><strong>Ngày cập nhật:</strong></td>
                            <td>${App.formatDate(orderData.updated_at)}</td>
                        </tr>
                        ${orderData.callback_no ? `
                        <tr>
                            <td><strong>Callback No:</strong></td>
                            <td>${orderData.callback_no}</td>
                        </tr>
                        ` : ''}
                    </table>
                </div>
                <style>
                    .order-details { padding: 20px; }
                    .order-details h4 { margin-bottom: 20px; color: var(--dark-color); }
                    .detail-table { width: 100%; }
                    .detail-table td { padding: 10px; border-bottom: 1px solid #eee; }
                    .detail-table tr:last-child td { border-bottom: none; }
                </style>
            `;
            
            App.showModal(`Chi tiết đơn hàng #${orderData.trade_no}`, content);
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể tải chi tiết đơn hàng');
        }
    },
    
    /**
     * Get status class
     */
    getStatusClass(status) {
        const classes = {
            0: 'text-warning',
            1: 'text-info',
            2: 'text-danger',
            3: 'text-success',
            4: 'text-primary'
        };
        return classes[status] || 'text-muted';
    },
    
    /**
     * Get status text
     */
    getStatusText(status) {
        const texts = {
            0: 'Chờ thanh toán',
            1: 'Đang xử lý',
            2: 'Đã hủy',
            3: 'Hoàn thành',
            4: 'Đã giảm giá'
        };
        return texts[status] || 'Không xác định';
    },
    
    /**
     * Get type text
     */
    getTypeText(type) {
        const types = {
            1: 'Mua mới',
            2: 'Gia hạn',
            3: 'Nâng cấp',
            4: 'Đặt lại lưu lượng'
        };
        return types[type] || 'Khác';
    },
    
    /**
     * Get period text
     */
    getPeriodText(period) {
        const periods = {
            'month_price': '1 Tháng',
            'quarter_price': '3 Tháng',
            'half_year_price': '6 Tháng',
            'year_price': '1 Năm',
            'two_year_price': '2 Năm',
            'three_year_price': '3 Năm',
            'onetime_price': 'Một lần',
            'reset_price': 'Đặt lại'
        };
        return periods[period] || period || '-';
    },

    /**
     * Show assign order modal
     */
    async showAssignModal() {
        try {
            // Load available plans
            const plans = await API.plans.fetch();
            const plansData = plans.data || [];
            
            if (plansData.length === 0) {
                App.showToast('warning', 'Thông báo', 'Không có plans nào available');
                return;
            }
            
            // Generate plans options
            let plansOptions = '<option value="">-- Chọn gói dịch vụ --</option>';
            plansData.forEach(plan => {
                plansOptions += `<option value="${plan.id}">${plan.name}</option>`;
            });
            
            const modalContent = `
                <form id="assignOrderForm" onsubmit="return Orders.submitAssignOrder(event)">
                    <div class="form-group mb-3">
                        <label for="assignEmail">Email người dùng *</label>
                        <input type="email" class="form-control" id="assignEmail" 
                               placeholder="Nhập email người dùng" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="assignPlan">Gói dịch vụ *</label>
                        <select class="form-control" id="assignPlan" onchange="Orders.updatePeriodOptions()" required>
                            ${plansOptions}
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="assignPeriod">Chu kỳ thanh toán *</label>
                        <select class="form-control" id="assignPeriod" required>
                            <option value="">-- Chọn gói dịch vụ trước --</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Số tiền sẽ được tự động tính dựa trên gói và chu kỳ được chọn.
                        </div>
                    </div>
                </form>
                
                <style>
                    .form-group { margin-bottom: 1rem; }
                    .form-group label { 
                        display: block; 
                        margin-bottom: 0.5rem; 
                        font-weight: 500; 
                        color: #333;
                    }
                    .form-control { 
                        width: 100%; 
                        padding: 0.5rem; 
                        border: 1px solid #ddd; 
                        border-radius: 4px; 
                        font-size: 14px;
                    }
                    .form-control:focus { 
                        border-color: #007bff; 
                        box-shadow: 0 0 0 2px rgba(0,123,255,0.25); 
                        outline: none;
                    }
                    .alert { 
                        padding: 12px 16px; 
                        border-radius: 4px; 
                        background-color: #d1ecf1; 
                        border: 1px solid #bee5eb; 
                        color: #0c5460;
                        font-size: 14px;
                    }
                    .alert i { margin-right: 8px; }
                </style>
            `;
            
            const footer = `
                <button type="button" class="btn btn-secondary" onclick="App.closeModal(this)">
                    Hủy
                </button>
                <button type="submit" form="assignOrderForm" class="btn btn-success">
                    <i class="fas fa-check"></i> Gán đơn hàng
                </button>
            `;
            
            // Store plans data for later use
            Orders.availablePlans = plansData;
            
            App.showModal('Gán đơn hàng cho User', modalContent, footer);
        } catch (error) {
            console.error('Failed to load assign modal:', error);
            App.showToast('error', 'Lỗi', 'Không thể tải form gán đơn hàng');
        }
    },
    
    /**
     * Update period options based on selected plan
     */
    updatePeriodOptions() {
        const planSelect = document.getElementById('assignPlan');
        const periodSelect = document.getElementById('assignPeriod');
        
        if (!planSelect || !periodSelect) return;
        
        const selectedPlanId = parseInt(planSelect.value);
        if (!selectedPlanId) {
            periodSelect.innerHTML = '<option value="">-- Chọn gói dịch vụ trước --</option>';
            return;
        }
        
        const plan = Orders.availablePlans.find(p => p.id === selectedPlanId);
        if (!plan) return;
        
        // Generate period options based on plan pricing
        const periods = [
            { key: 'month_price', label: '1 Tháng', value: plan.month_price },
            { key: 'quarter_price', label: '3 Tháng', value: plan.quarter_price },
            { key: 'half_year_price', label: '6 Tháng', value: plan.half_year_price },
            { key: 'year_price', label: '1 Năm', value: plan.year_price },
            { key: 'two_year_price', label: '2 Năm', value: plan.two_year_price },
            { key: 'three_year_price', label: '3 Năm', value: plan.three_year_price },
            { key: 'onetime_price', label: 'Một lần', value: plan.onetime_price },
            { key: 'reset_price', label: 'Đặt lại lưu lượng', value: plan.reset_price }
        ];
        
        let options = '<option value="">-- Chọn chu kỳ --</option>';
        periods.forEach(period => {
            if (period.value !== null && period.value > 0) {
                const priceText = App.formatCurrency(period.value);
                options += `<option value="${period.key}">${period.label} - ${priceText}</option>`;
            }
        });
        
        periodSelect.innerHTML = options;
    },
    
    /**
     * Submit assign order form  
     */
    async submitAssignOrder(event) {
        console.log('submitAssignOrder called', event);
        event.preventDefault();
        
        // Find the submit button - may be outside the form
        const submitBtn = document.querySelector('#assignOrderForm').closest('.modal').querySelector('button[type="submit"]') || 
                          document.querySelector('button[form="assignOrderForm"]');
        console.log('Submit button found:', submitBtn);
        
        if (!submitBtn) {
            console.error('Submit button not found');
            return false;
        }
        
        const originalText = submitBtn.innerHTML;
        
        try {
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            
            const email = document.getElementById('assignEmail').value.trim();
            const planId = parseInt(document.getElementById('assignPlan').value);
            const period = document.getElementById('assignPeriod').value;
            
            console.log('Form data:', { email, planId, period });
            
            // Validate form
            if (!email || !planId || !period) {
                console.error('Form validation failed:', { email, planId, period });
                throw new Error('Vui lòng điền đầy đủ thông tin');
            }
            
            if (!email.includes('@')) {
                throw new Error('Email không hợp lệ');
            }
            
            // Submit assign order
            console.log('Calling API.orders.assign with:', { email, plan_id: planId, period });
            const response = await API.orders.assign({
                email: email,
                plan_id: planId,
                period: period
            });
            console.log('API response:', response);
            
            // Close modal
            const modal = document.querySelector('.modal-overlay');
            if (modal) modal.remove();
            
            // Show success message
            App.showToast('success', 'Thành công', `Order đã được assign thành công. Trade No: ${response.data}`);
            
            // Refresh orders list
            await Orders.loadOrders(Orders.currentPage);
            
        } catch (error) {
            console.error('Assign order failed:', error);
            App.showToast('error', 'Lỗi', error.message || 'Không thể gán đơn hàng');
        } finally {
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
        
        return false; // Prevent default form submission
    }
};
