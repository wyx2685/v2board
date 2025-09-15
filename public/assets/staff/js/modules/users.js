/**
 * Users Management Module
 */

const Users = {
    currentPage: 1,
    pageSize: 20,
    searchParams: {},
    
    /**
     * Render users page
     */
    async render() {
        const content = document.getElementById('pageContent');
        
        // Create mobile search
        const mobileSearch = App.createMobileSearch({
            searchTypes: [
                { value: 'email', text: 'Email' },
                { value: 'id', text: 'ID' }
            ],
            placeholder: 'Nhập từ khóa tìm kiếm...',
            onSearch: 'Users.mobileSearch',
            onReset: 'Users.mobileReset'
        });
        
        content.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quản lý người dùng</h3>
                </div>
                <div class="card-body">
                    ${mobileSearch}
                    
                    <div class="search-box mb-3">
                        <select class="form-control" id="searchType" style="width: 150px;">
                            <option value="email">Email</option>
                            <option value="id">ID</option>
                        </select>
                        <input type="text" class="form-control search-input" id="searchInput" 
                               placeholder="Nhập từ khóa tìm kiếm...">
                        <button class="btn btn-primary" onclick="Users.search()">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                        <button class="btn btn-outline" onclick="Users.reset()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                    
                    <div id="usersTable">
                        <div class="loading-container">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        await Users.loadUsers();
    },
    
    /**
     * Load users list
     */
    async     loadUsers(page = 1) {
        Users.currentPage = page;
        
        try {
            const params = {
                page: Users.currentPage,
                limit: Users.pageSize,
                ...Users.searchParams
            };
            
            const response = await API.users.search(params);
            Users.renderTable(response);
        } catch (error) {
            console.error('Failed to load users:', error);
            document.getElementById('usersTable').innerHTML = `
                <div class="alert alert-danger">
                    Không thể tải danh sách người dùng
                </div>
            `;
        }
    },
    
    /**
     * Render users table
     */
    renderTable(response) {
        const { data, total, current, pageSize } = response;
        const totalPages = Math.ceil(total / pageSize);
        
        if (!data || data.length === 0) {
            document.getElementById('usersTable').innerHTML = `
                <div class="alert alert-info">
                    Không tìm thấy người dùng nào
                </div>
            `;
            return;
        }
        
        // Generate mobile card table with dropdown actions
        let mobileCardsHtml = '<div class="mobile-card-table">';
        
        data.forEach(user => {
            const usedData = App.formatBytes((user.u || 0) + (user.d || 0));
            const totalData = App.formatBytes(user.transfer_enable || 0);
            const planName = user.plan_name || '<span class="text-muted">Không có</span>';
            const expiredAt = user.expired_at ? App.formatDate(user.expired_at) : '-';
            const expiredClass = user.expired_at && user.expired_at > Date.now()/1000 ? 'text-success' : 'text-danger';
            
            mobileCardsHtml += `
                <div class="mobile-card-item">
                    <div class="mobile-card-header">
                        <div class="mobile-card-title">${user.email}</div>
                        <div class="mobile-card-id">#${user.id}</div>
                    </div>
                    <div class="mobile-card-body">
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">GÓI DỊCH VỤ</div>
                            <div class="mobile-card-value">${planName}</div>
                        </div>
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">ĐÃ DÙNG</div>
                            <div class="mobile-card-value">${usedData}</div>
                        </div>
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">GIỚI HẠN</div>
                            <div class="mobile-card-value">${totalData}</div>
                        </div>
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">THIẾT BỊ</div>
                            <div class="mobile-card-value">${user.alive_ip || 0}</div>
                        </div>
                        <div class="mobile-card-field">
                            <div class="mobile-card-label">HẠN DÙNG</div>
                            <div class="mobile-card-value"><span class="${expiredClass}">${expiredAt}</span></div>
                        </div>
                    </div>
                    <div class="mobile-card-actions">
                        <div class="dropdown" style="width: 100%;">
                            <button class="btn btn-primary dropdown-toggle" type="button" 
                                    data-user-id="${user.id}" 
                                    data-user-email="${user.email.replace(/"/g, '&quot;')}" 
                                    data-subscribe-url="${user.subscribe_url}" 
                                    onclick="Users.toggleDropdown(this)"
                                    style="width: 100%;">
                                <i class="fas fa-cog"></i> Hành động
                            </button>
                            <ul class="dropdown-menu" style="width: 100%;">
                                <li><a class="dropdown-item" onclick="Users.closeDropdown(this); Users.viewDetails(${user.id})"><i class="fas fa-eye"></i> Chi tiết</a></li>
                                <li><a class="dropdown-item" onclick="Users.closeDropdown(this); Users.showAssignModalForUser(${user.id})"><i class="fas fa-plus"></i> Gán đơn hàng</a></li>
                                <li><a class="dropdown-item" onclick="Users.closeDropdown(this); Users.copySubscribe('${user.subscribe_url}')"><i class="fas fa-copy"></i> Copy link</a></li>
                                <li><a class="dropdown-item" onclick="Users.closeDropdown(this); App.showQRCode('${user.subscribe_url}')"><i class="fas fa-qrcode"></i> QR Code</a></li>
                                <li><a class="dropdown-item" onclick="Users.closeDropdown(this); Users.resetSecurity(${user.id})"><i class="fas fa-shield-alt"></i> Reset Security</a></li>
                            </ul>
                        </div>
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
                            <th>ID</th>
                            <th>Email</th>
                            <th>Gói dịch vụ</th>
                            <th>Đã dùng</th>
                            <th>Giới hạn</th>
                            <th>Thiết bị</th>
                            <th>Hạn dùng</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        data.forEach(user => {
            const usedData = App.formatBytes((user.u || 0) + (user.d || 0));
            const totalData = App.formatBytes(user.transfer_enable || 0);
            const planName = user.plan_name || '<span class="text-muted">Không có</span>';
            const expiredAt = user.expired_at ? App.formatDate(user.expired_at) : '-';
            const expiredClass = user.expired_at && user.expired_at > Date.now()/1000 ? 'text-success' : 'text-danger';
            
            html += `
                <tr>
                    <td>${user.id}</td>
                    <td>${user.email}</td>
                    <td>${planName}</td>
                    <td>${usedData}</td>
                    <td>${totalData}</td>
                    <td>${user.alive_ip || 0}</td>
                    <td><span class="${expiredClass}">${expiredAt}</span></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" 
                                    data-user-id="${user.id}" 
                                    data-user-email="${user.email.replace(/"/g, '&quot;')}" 
                                    data-subscribe-url="${user.subscribe_url}" 
                                    onclick="Users.toggleDropdown(this)">
                                <i class="fas fa-cog"></i> Chỉnh sửa
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" onclick="Users.viewDetails(${user.id})"><i class="fas fa-eye"></i> Chi tiết</a></li>
                                <li><a class="dropdown-item" onclick="Users.showAssignModalForUser(${user.id})"><i class="fas fa-plus"></i> Gán đơn hàng</a></li>
                                <li><a class="dropdown-item" onclick="Users.copySubscribe(this.closest('tr').querySelector('[data-subscribe-url]').dataset.subscribeUrl)"><i class="fas fa-copy"></i> Copy link</a></li>
                                <li><a class="dropdown-item" onclick="App.showQRCode(this.closest('tr').querySelector('[data-subscribe-url]').dataset.subscribeUrl)"><i class="fas fa-qrcode"></i> QR Code</a></li>
                                <li><a class="dropdown-item" onclick="Users.resetSecurity(${user.id})"><i class="fas fa-shield-alt"></i> Reset Security</a></li>
                            </ul>
                        </div>
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
            combinedHtml += App.createPagination(current, totalPages, 'Users.loadUsers');
        }
        
        // Show total records
        combinedHtml += `
            <div class="mt-3 text-muted">
                Hiển thị ${(current - 1) * pageSize + 1} - ${Math.min(current * pageSize, total)} trong tổng số ${total} người dùng
            </div>
        `;
        
        document.getElementById('usersTable').innerHTML = combinedHtml;
    },
    
    /**
     * Search users
     */
    search() {
        const searchType = document.getElementById('searchType').value;
        const searchInput = document.getElementById('searchInput').value.trim();
        
        if (searchInput) {
            Users.searchParams = { [searchType]: searchInput };
        } else {
            Users.searchParams = {};
        }
        
        Users.loadUsers(1);
    },
    
    /**
     * Reset search
     */
    reset() {
        document.getElementById('searchInput').value = '';
        Users.searchParams = {};
        Users.loadUsers(1);
    },
    
    /**
     * Mobile search
     */
    mobileSearch() {
        const searchType = document.getElementById('mobileSearchType').value;
        const searchInput = document.getElementById('mobileSearchInput').value.trim();
        
        // Sync desktop search
        const desktopType = document.getElementById('searchType');
        const desktopInput = document.getElementById('searchInput');
        
        if (desktopType && desktopInput) {
            desktopType.value = searchType;
            desktopInput.value = searchInput;
        }
        
        if (searchInput) {
            Users.searchParams = { [searchType]: searchInput };
        } else {
            Users.searchParams = {};
        }
        
        Users.loadUsers(1);
    },
    
    /**
     * Mobile reset
     */
    mobileReset() {
        const mobileInput = document.getElementById('mobileSearchInput');
        const desktopInput = document.getElementById('searchInput');
        
        if (mobileInput) mobileInput.value = '';
        if (desktopInput) desktopInput.value = '';
        
        Users.searchParams = {};
        Users.loadUsers(1);
    },
    
    /**
     * Copy subscribe URL
     */
    copySubscribe(url) {
        App.copyToClipboard(url);
    },
    
    /**
     * View user details
     */
    async viewDetails(userId) {
        try {
            const user = await API.users.getById(userId);
            const userData = user.data;
            
            const content = `
                <div class="user-details">
                    <table class="detail-table">
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td>${userData.id}</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>${userData.email}</td>
                        </tr>
                        <tr>
                            <td><strong>Số dư:</strong></td>
                            <td>${App.formatCurrency(userData.balance)}</td>
                        </tr>
                        <tr>
                            <td><strong>Hoa hồng:</strong></td>
                            <td>${App.formatCurrency(userData.commission_balance)}</td>
                        </tr>
                        <tr>
                            <td><strong>Gói dịch vụ:</strong></td>
                            <td>${userData.plan_id ? `ID: ${userData.plan_id}` : 'Không có'}</td>
                        </tr>
                        <tr>
                            <td><strong>Tốc độ:</strong></td>
                            <td>${userData.speed_limit || 0} Mbps</td>
                        </tr>
                        <tr>
                            <td><strong>Thiết bị tối đa:</strong></td>
                            <td>${userData.device_limit || 0}</td>
                        </tr>
                        <tr>
                            <td><strong>Đã dùng:</strong></td>
                            <td>${App.formatBytes((userData.u || 0) + (userData.d || 0))}</td>
                        </tr>
                        <tr>
                            <td><strong>Giới hạn:</strong></td>
                            <td>${App.formatBytes(userData.transfer_enable || 0)}</td>
                        </tr>
                        <tr>
                            <td><strong>Ngày hết hạn:</strong></td>
                            <td>${userData.expired_at ? App.formatDate(userData.expired_at) : 'Không giới hạn'}</td>
                        </tr>
                        <tr>
                            <td><strong>UUID:</strong></td>
                            <td style="word-break: break-all;">${userData.uuid}</td>
                        </tr>
                        <tr>
                            <td><strong>Token:</strong></td>
                            <td style="word-break: break-all;">${userData.token}</td>
                        </tr>
                        <tr>
                            <td><strong>Trạng thái:</strong></td>
                            <td>${userData.banned ? '<span class="text-danger">Đã khóa</span>' : '<span class="text-success">Hoạt động</span>'}</td>
                        </tr>
                        <tr>
                            <td><strong>Ngày đăng ký:</strong></td>
                            <td>${App.formatDate(userData.created_at)}</td>
                        </tr>
                    </table>
                </div>
                <style>
                    .user-details { padding: 20px; }
                    .detail-table { width: 100%; }
                    .detail-table td { padding: 8px; border-bottom: 1px solid #eee; }
                    .detail-table tr:last-child td { border-bottom: none; }
                </style>
            `;
            
            App.showModal(`Chi tiết người dùng #${userId}`, content);
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể tải thông tin người dùng');
        }
    },
    
    /**
     * Reset user security (UUID, token)
     */
    async resetSecurity(userId) {
        if (!confirm('Bạn có chắc chắn muốn reset security cho user này?\n\nViệc này sẽ tạo mới UUID và token, làm mất hiệu lực tất cả subscription URLs cũ.')) {
            return;
        }
        
        try {
            const result = await API.users.resetSecurity(userId);
            
            if (result.data && result.data.success) {
                const newUrl = result.data.new_subscribe_url;
                
                // Show success modal with new URL
                const content = `
                    <div class="reset-success">
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Reset Security thành công!</h5>
                            <p>UUID và token mới đã được tạo. Subscription URL cũ không còn hiệu lực.</p>
                        </div>
                        
                        <div class="new-url-section">
                            <label class="form-label"><strong>Subscription URL mới:</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="newSubscribeUrl" value="${newUrl}" readonly>
                                <button class="btn btn-primary" onclick="App.copyToClipboard('${newUrl}')">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                        
                        <div class="qr-section mt-3">
                            <label class="form-label"><strong>QR Code mới:</strong></label>
                            <div class="text-center">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(newUrl)}" 
                                     alt="QR Code" style="border: 1px solid #ddd; border-radius: 8px;">
                            </div>
                        </div>
                    </div>
                    
                    <style>
                        .reset-success { padding: 20px; }
                        .input-group { display: flex; }
                        .input-group input { flex: 1; margin-right: 10px; }
                        .new-url-section, .qr-section { margin: 20px 0; }
                    </style>
                `;
                
                const footer = `
                    <button class="btn btn-primary" onclick="App.closeModal(this)">Đóng</button>
                    <button class="btn btn-outline" onclick="Users.loadUsers(Users.currentPage)">
                        <i class="fas fa-sync"></i> Tải lại danh sách
                    </button>
                `;
                
                App.showModal(`Reset Security thành công - User #${userId}`, content, footer);
            } else {
                App.showToast('error', 'Lỗi', 'Reset security thất bại');
            }
        } catch (error) {
            App.showToast('error', 'Lỗi', error.message || 'Không thể reset security');
        }
    },

    /**
     * Close dropdown (called when clicking on dropdown items)
     */
    closeDropdown(element) {
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
     * Toggle dropdown menu
     */
    toggleDropdown(button) {
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
    },

    /**
     * Show assign modal for specific user
     */
    async showAssignModalForUser(userId) {
        // Get user email from the dropdown button's data attribute
        const dropdownButton = document.querySelector(`[data-user-id="${userId}"]`);
        const userEmail = dropdownButton ? dropdownButton.dataset.userEmail : '';
        
        if (!userEmail) {
            App.showToast('error', 'Lỗi', 'Không thể lấy thông tin email người dùng');
            return;
        }
        
        try {
            // Load available plans
            const plans = await API.plans.fetch();
            const plansData = plans.data || [];
            
            if (plansData.length === 0) {
                App.showToast('warning', 'Thông báo', 'Không có gói dịch vụ nào khả dụng');
                return;
            }
            
            // Generate plans options
            let plansOptions = '<option value="">-- Chọn gói dịch vụ --</option>';
            plansData.forEach(plan => {
                plansOptions += `<option value="${plan.id}">${plan.name}</option>`;
            });
            
            const modalContent = `
                <form id="assignUserOrderForm" onsubmit="return Users.submitAssignOrderForUser(event, ${userId})">
                    <div class="form-group mb-3">
                        <label for="assignUserEmail">Email người dùng</label>
                        <input type="email" class="form-control" id="assignUserEmail" 
                               value="${userEmail}" readonly style="background-color: #f8f9fa;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="assignUserPlan">Gói dịch vụ *</label>
                        <select class="form-control" id="assignUserPlan" onchange="Users.updateUserPeriodOptions()" required>
                            ${plansOptions}
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="assignUserPeriod">Chu kỳ thanh toán *</label>
                        <select class="form-control" id="assignUserPeriod" required>
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
            `;
            
            const footer = `
                <button type="button" class="btn btn-secondary" onclick="App.closeModal(this)">
                    Hủy
                </button>
                <button type="submit" form="assignUserOrderForm" class="btn btn-success">
                    <i class="fas fa-check"></i> Gán đơn hàng
                </button>
            `;
            
            // Store plans data for later use
            Users.availablePlans = plansData;
            
            App.showModal(`Gán đơn hàng cho: ${userEmail}`, modalContent, footer);
        } catch (error) {
            console.error('Failed to load assign modal:', error);
            App.showToast('error', 'Lỗi', 'Không thể tải form gán đơn hàng');
        }
    },

    /**
     * Update period options based on selected plan for user
     */
    updateUserPeriodOptions() {
        const planSelect = document.getElementById('assignUserPlan');
        const periodSelect = document.getElementById('assignUserPeriod');
        
        if (!planSelect || !periodSelect) return;
        
        const selectedPlanId = parseInt(planSelect.value);
        if (!selectedPlanId) {
            periodSelect.innerHTML = '<option value="">-- Chọn gói dịch vụ trước --</option>';
            return;
        }
        
        const plan = Users.availablePlans.find(p => p.id === selectedPlanId);
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
     * Submit assign order form for user
     */
    async submitAssignOrderForUser(event, userId) {
        console.log('submitAssignOrderForUser called', event, userId);
        event.preventDefault();
        
        // Find the submit button - it's in modal footer, not in form
        const submitBtn = document.querySelector('#assignUserOrderForm').closest('.modal').querySelector('button[type="submit"]') || 
                          document.querySelector('button[form="assignUserOrderForm"]');
        console.log('Submit button found:', submitBtn);
        
        if (!submitBtn) {
            console.error('Submit button not found');
            App.showToast('error', 'Lỗi', 'Không tìm thấy nút submit');
            return false;
        }
        
        const originalText = submitBtn.innerHTML;
        
        try {
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            
            const email = document.getElementById('assignUserEmail').value.trim();
            const planId = parseInt(document.getElementById('assignUserPlan').value);
            const period = document.getElementById('assignUserPeriod').value;
            
            console.log('Form data:', { email, planId, period });
            
            // Validate form
            if (!planId || !period) {
                console.error('Form validation failed:', { email, planId, period });
                throw new Error('Vui lòng chọn gói dịch vụ và chu kỳ thanh toán');
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
            App.showToast('success', 'Thành công', `Đơn hàng đã được gán thành công cho ${email}. Trade No: ${response.data}`);
            
            // Refresh users list
            await Users.loadUsers(Users.currentPage);
            
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
    
    // editUser() and saveUser() methods removed
    // Staff users can only view user details, not edit them
};
