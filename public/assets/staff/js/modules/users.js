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
        
        content.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quản lý người dùng</h3>
                </div>
                <div class="card-body">
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
                        <button class="btn btn-sm btn-primary" onclick="Users.viewDetails(${user.id})" title="Chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-info" onclick="Users.copySubscribe('${user.subscribe_url}')" title="Copy link">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="App.showQRCode('${user.subscribe_url}')" title="QR Code">
                            <i class="fas fa-qrcode"></i>
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
        
        // Add pagination
        if (totalPages > 1) {
            html += App.createPagination(current, totalPages, 'Users.loadUsers');
        }
        
        // Show total records
        html += `
            <div class="mt-3 text-muted">
                Hiển thị ${(current - 1) * pageSize + 1} - ${Math.min(current * pageSize, total)} trong tổng số ${total} người dùng
            </div>
        `;
        
        document.getElementById('usersTable').innerHTML = html;
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
    
    // editUser() and saveUser() methods removed
    // Staff users can only view user details, not edit them
};
