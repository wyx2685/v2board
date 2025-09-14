/**
 * Configuration Module
 */

const Config = {
    /**
     * Render config page
     */
    async render() {
        const content = document.getElementById('pageContent');
        
        content.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cấu hình Webcon</h3>
                </div>
                <div class="card-body">
                    <div id="configForm">
                        <div class="loading-container">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        await Config.loadConfig();
    },
    
    /**
     * Load configuration
     */
    async loadConfig() {
        try {
            const data = await API.config.get();
            Config.renderForm(data);
        } catch (error) {
            console.error('Failed to load config:', error);
            document.getElementById('configForm').innerHTML = `
                <div class="alert alert-danger">
                    Không thể tải cấu hình
                </div>
            `;
        }
    },
    
    /**
     * Render configuration form
     */
    renderForm(data) {
        const formHtml = `
            <form id="configFormData">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Email Admin</label>
                            <input type="email" class="form-control" value="${data.email || ''}" disabled>
                            <small class="text-muted">Email không thể thay đổi</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Domain</label>
                            <input type="text" class="form-control" value="${data.domain || ''}" disabled>
                            <small class="text-muted">Domain không thể thay đổi</small>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Tiêu đề website</label>
                            <input type="text" class="form-control" name="title" 
                                   value="${data.title || ''}" placeholder="Nhập tiêu đề website">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Mô tả website</label>
                            <input type="text" class="form-control" name="description" 
                                   value="${data.description || ''}" placeholder="Nhập mô tả website">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">URL Logo</label>
                            <input type="url" class="form-control" name="logo" 
                                   value="${data.logo || ''}" placeholder="https://example.com/logo.png">
                            <small class="text-muted">Nhập URL đầy đủ của logo (PNG, JPG, SVG)</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">URL Hình nền</label>
                            <input type="url" class="form-control" name="background_url" 
                                   value="${data.background_url || ''}" placeholder="https://example.com/background.jpg">
                            <small class="text-muted">Nhập URL đầy đủ của hình nền</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Custom HTML (Footer)</label>
                    <textarea class="form-control" name="custom_html" rows="8" 
                              placeholder="Nhập HTML tùy chỉnh cho footer...">${data.custom_html || ''}</textarea>
                    <small class="text-muted">HTML này sẽ được hiển thị ở footer của website</small>
                </div>
                
                <div class="preview-section mt-4">
                    <h5 class="mb-3">Xem trước</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="preview-box">
                                <label>Logo hiện tại:</label>
                                <div class="preview-image">
                                    ${data.logo ? 
                                        `<img src="${data.logo}" alt="Logo" style="max-height: 60px; max-width: 200px;">` : 
                                        '<span class="text-muted">Chưa có logo</span>'
                                    }
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-box">
                                <label>Hình nền hiện tại:</label>
                                <div class="preview-image">
                                    ${data.background_url ? 
                                        `<img src="${data.background_url}" alt="Background" style="max-height: 100px; max-width: 200px;">` : 
                                        '<span class="text-muted">Chưa có hình nền</span>'
                                    }
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions mt-4">
                    <button type="button" class="btn btn-outline" onclick="Config.resetForm()">
                        <i class="fas fa-undo"></i> Hoàn tác
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu cấu hình
                    </button>
                </div>
            </form>
            
            <style>
                .row { 
                    display: flex; 
                    flex-wrap: wrap; 
                    margin: 0 -10px; 
                }
                .col-md-6 { 
                    flex: 0 0 50%; 
                    padding: 0 10px; 
                }
                @media (max-width: 768px) {
                    .col-md-6 { 
                        flex: 0 0 100%; 
                    }
                }
                .preview-box {
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    margin-bottom: 15px;
                }
                .preview-box label {
                    display: block;
                    margin-bottom: 10px;
                    font-weight: 500;
                }
                .preview-image {
                    padding: 10px;
                    background: white;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    text-align: center;
                    min-height: 80px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .form-actions {
                    display: flex;
                    gap: 10px;
                    justify-content: flex-end;
                    padding-top: 20px;
                    border-top: 1px solid #dee2e6;
                }
            </style>
        `;
        
        document.getElementById('configForm').innerHTML = formHtml;
        
        // Add form submit handler
        document.getElementById('configFormData').addEventListener('submit', (e) => {
            e.preventDefault();
            Config.saveConfig();
        });
    },
    
    /**
     * Save configuration
     */
    async saveConfig() {
        const form = document.getElementById('configFormData');
        const formData = new FormData(form);
        
        const data = {
            title: formData.get('title') || '',
            description: formData.get('description') || '',
            logo: formData.get('logo') || '',
            background_url: formData.get('background_url') || '',
            custom_html: formData.get('custom_html') || ''
        };
        
        // Validate URLs
        if (data.logo && !Config.isValidUrl(data.logo)) {
            App.showToast('warning', 'Cảnh báo', 'URL logo không hợp lệ');
            return;
        }
        
        if (data.background_url && !Config.isValidUrl(data.background_url)) {
            App.showToast('warning', 'Cảnh báo', 'URL hình nền không hợp lệ');
            return;
        }
        
        try {
            await API.config.save(data);
            App.showToast('success', 'Thành công', 'Đã lưu cấu hình');
            
            // Reload to show updated preview
            setTimeout(() => {
                Config.loadConfig();
            }, 1000);
        } catch (error) {
            App.showToast('error', 'Lỗi', error.message || 'Không thể lưu cấu hình');
        }
    },
    
    /**
     * Reset form
     */
    resetForm() {
        if (confirm('Bạn có chắc muốn hoàn tác các thay đổi?')) {
            Config.loadConfig();
        }
    },
    
    /**
     * Validate URL
     */
    isValidUrl(string) {
        try {
            const url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (_) {
            return false;
        }
    }
};
