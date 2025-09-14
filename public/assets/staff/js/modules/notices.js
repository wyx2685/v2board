/**
 * Notices Management Module
 */

const Notices = {
    /**
     * Render notices page
     */
    async render() {
        const content = document.getElementById('pageContent');
        
        content.innerHTML = `
            <div class="card">
                <div class="card-header d-flex justify-between align-center">
                    <h3 class="card-title">Quản lý thông báo</h3>
                    <button class="btn btn-primary" onclick="Notices.createNotice()">
                        <i class="fas fa-plus"></i> Tạo thông báo mới
                    </button>
                </div>
                <div class="card-body">
                    <div id="noticesTable">
                        <div class="loading-container">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        await Notices.loadNotices();
    },
    
    /**
     * Load notices list
     */
    async loadNotices() {
        try {
            const response = await API.notices.fetch();
            Notices.renderTable(response.data);
        } catch (error) {
            console.error('Failed to load notices:', error);
            document.getElementById('noticesTable').innerHTML = `
                <div class="alert alert-danger">
                    Không thể tải danh sách thông báo
                </div>
            `;
        }
    },
    
    /**
     * Render notices table
     */
    renderTable(notices) {
        if (!notices || notices.length === 0) {
            document.getElementById('noticesTable').innerHTML = `
                <div class="alert alert-info">
                    Chưa có thông báo nào
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
                            <th>Tiêu đề</th>
                            <th>Hình ảnh</th>
                            <th>Ngày tạo</th>
                            <th>Ngày cập nhật</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        notices.forEach(notice => {
            html += `
                <tr>
                    <td>#${notice.id}</td>
                    <td>${notice.title}</td>
                    <td>
                        ${notice.img_url ? 
                            `<img src="${notice.img_url}" alt="Notice image" style="height: 30px; max-width: 100px; object-fit: cover;">` : 
                            '<span class="text-muted">Không có</span>'
                        }
                    </td>
                    <td>${App.formatDate(notice.created_at)}</td>
                    <td>${App.formatDate(notice.updated_at)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="Notices.viewNotice(${notice.id})" title="Xem">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-success" onclick="Notices.editNotice(${notice.id})" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="Notices.deleteNotice(${notice.id})" title="Xóa">
                            <i class="fas fa-trash"></i>
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
        
        document.getElementById('noticesTable').innerHTML = html;
    },
    
    /**
     * Create new notice
     */
    createNotice() {
        const content = `
            <form id="noticeForm">
                <div class="form-group">
                    <label class="form-label">Tiêu đề *</label>
                    <input type="text" class="form-control" name="title" required placeholder="Nhập tiêu đề thông báo">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nội dung *</label>
                    <textarea class="form-control" name="content" rows="10" required placeholder="Nhập nội dung thông báo (hỗ trợ HTML)"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">URL hình ảnh</label>
                    <input type="url" class="form-control" name="img_url" placeholder="https://example.com/image.jpg">
                </div>
            </form>
        `;
        
        const footer = `
            <button class="btn btn-outline" onclick="App.closeModal(this)">Hủy</button>
            <button class="btn btn-primary" onclick="Notices.saveNotice()">
                <i class="fas fa-save"></i> Tạo thông báo
            </button>
        `;
        
        App.showModal('Tạo thông báo mới', content, footer);
    },
    
    /**
     * View notice
     */
    async viewNotice(noticeId) {
        try {
            const response = await API.notices.fetch();
            const notice = response.data.find(n => n.id === noticeId);
            
            if (!notice) {
                App.showToast('error', 'Lỗi', 'Không tìm thấy thông báo');
                return;
            }
            
            const content = `
                <div class="notice-detail">
                    <h4>${notice.title}</h4>
                    ${notice.img_url ? `
                        <div class="mb-3">
                            <img src="${notice.img_url}" alt="Notice image" style="max-width: 100%; height: auto; border-radius: 8px;">
                        </div>
                    ` : ''}
                    <div class="notice-content">
                        ${notice.content}
                    </div>
                    <hr>
                    <div class="text-muted">
                        <small>Tạo lúc: ${App.formatDate(notice.created_at)}</small><br>
                        <small>Cập nhật: ${App.formatDate(notice.updated_at)}</small>
                    </div>
                </div>
                <style>
                    .notice-detail { padding: 20px; }
                    .notice-content { margin: 20px 0; line-height: 1.6; }
                </style>
            `;
            
            App.showModal(`Thông báo #${noticeId}`, content);
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể tải thông báo');
        }
    },
    
    /**
     * Edit notice
     */
    async editNotice(noticeId) {
        try {
            const response = await API.notices.fetch();
            const notice = response.data.find(n => n.id === noticeId);
            
            if (!notice) {
                App.showToast('error', 'Lỗi', 'Không tìm thấy thông báo');
                return;
            }
            
            const content = `
                <form id="noticeForm">
                    <input type="hidden" name="id" value="${notice.id}">
                    
                    <div class="form-group">
                        <label class="form-label">Tiêu đề *</label>
                        <input type="text" class="form-control" name="title" value="${notice.title}" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nội dung *</label>
                        <textarea class="form-control" name="content" rows="10" required>${notice.content}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">URL hình ảnh</label>
                        <input type="url" class="form-control" name="img_url" value="${notice.img_url || ''}">
                    </div>
                </form>
            `;
            
            const footer = `
                <button class="btn btn-outline" onclick="App.closeModal(this)">Hủy</button>
                <button class="btn btn-primary" onclick="Notices.updateNotice()">
                    <i class="fas fa-save"></i> Cập nhật
                </button>
            `;
            
            App.showModal(`Sửa thông báo #${noticeId}`, content, footer);
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể tải thông báo');
        }
    },
    
    /**
     * Save new notice
     */
    async saveNotice() {
        const form = document.getElementById('noticeForm');
        const formData = new FormData(form);
        
        if (!form.checkValidity()) {
            App.showToast('warning', 'Cảnh báo', 'Vui lòng điền đầy đủ thông tin');
            return;
        }
        
        const data = {
            title: formData.get('title'),
            content: formData.get('content'),
            img_url: formData.get('img_url') || null
        };
        
        try {
            await API.notices.save(data);
            App.showToast('success', 'Thành công', 'Đã tạo thông báo mới');
            document.querySelector('.modal-close').click();
            Notices.loadNotices();
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể tạo thông báo');
        }
    },
    
    /**
     * Update notice
     */
    async updateNotice() {
        const form = document.getElementById('noticeForm');
        const formData = new FormData(form);
        
        if (!form.checkValidity()) {
            App.showToast('warning', 'Cảnh báo', 'Vui lòng điền đầy đủ thông tin');
            return;
        }
        
        const data = {
            id: parseInt(formData.get('id')),
            title: formData.get('title'),
            content: formData.get('content'),
            img_url: formData.get('img_url') || null
        };
        
        try {
            await API.notices.save(data);
            App.showToast('success', 'Thành công', 'Đã cập nhật thông báo');
            document.querySelector('.modal-close').click();
            Notices.loadNotices();
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể cập nhật thông báo');
        }
    },
    
    /**
     * Delete notice
     */
    async deleteNotice(noticeId) {
        if (!confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
            return;
        }
        
        try {
            await API.notices.delete(noticeId);
            App.showToast('success', 'Thành công', 'Đã xóa thông báo');
            Notices.loadNotices();
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể xóa thông báo');
        }
    }
};
