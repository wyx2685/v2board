/**
 * Tickets Management Module
 */

const Tickets = {
    currentPage: 1,
    pageSize: 20,
    currentTicket: null,
    
    /**
     * Render tickets page
     */
    async render() {
        const content = document.getElementById('pageContent');
        
        content.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Support Tickets</h3>
                </div>
                <div class="card-body">
                    <div class="filters mb-3">
                        <select class="form-control" id="filterTicketStatus" style="width: 200px;">
                            <option value="">Tất cả trạng thái</option>
                            <option value="0">Đang mở</option>
                            <option value="1">Đã đóng</option>
                        </select>
                        <button class="btn btn-primary" onclick="Tickets.applyFilter()">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                    </div>
                    
                    <div id="ticketsTable">
                        <div class="loading-container">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        await Tickets.loadTickets();
    },
    
    /**
     * Load tickets list
     */
    async     loadTickets(page = 1) {
        Tickets.currentPage = page;
        
        try {
            const status = document.getElementById('filterTicketStatus')?.value;
            const params = {
                current: Tickets.currentPage,
                pageSize: Tickets.pageSize
            };
            
            if (status !== '' && status !== undefined) {
                params.status = parseInt(status);
            }
            
            const response = await API.tickets.fetch(params);
            Tickets.renderTable(response);
        } catch (error) {
            console.error('Failed to load tickets:', error);
            document.getElementById('ticketsTable').innerHTML = `
                <div class="alert alert-danger">
                    Không thể tải danh sách ticket
                </div>
            `;
        }
    },
    
    /**
     * Render tickets table
     */
    renderTable(response) {
        const { data, total } = response;
        const totalPages = Math.ceil(total / Tickets.pageSize);
        
        if (!data || data.length === 0) {
            document.getElementById('ticketsTable').innerHTML = `
                <div class="alert alert-info">
                    Không có ticket nào
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
                            <th>Người dùng</th>
                            <th>Tiêu đề</th>
                            <th>Cấp độ</th>
                            <th>Trạng thái</th>
                            <th>Cập nhật</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        data.forEach(ticket => {
            const statusClass = ticket.status === 0 ? 'text-success' : 'text-danger';
            const statusText = ticket.status === 0 ? 'Đang mở' : 'Đã đóng';
            const levelClass = ticket.level === 2 ? 'text-danger' : ticket.level === 1 ? 'text-warning' : 'text-info';
            const levelText = ticket.level === 2 ? 'Cao' : ticket.level === 1 ? 'Trung bình' : 'Thấp';
            
            html += `
                <tr>
                    <td>#${ticket.id}</td>
                    <td>ID: ${ticket.user_id}</td>
                    <td>${ticket.subject}</td>
                    <td><span class="${levelClass}">${levelText}</span></td>
                    <td><span class="${statusClass}">${statusText}</span></td>
                    <td>${App.formatDate(ticket.updated_at)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="Tickets.viewTicket(${ticket.id})" title="Xem & Trả lời">
                            <i class="fas fa-comment"></i>
                        </button>
                        ${ticket.status === 0 ? `
                        <button class="btn btn-sm btn-danger" onclick="Tickets.closeTicket(${ticket.id})" title="Đóng ticket">
                            <i class="fas fa-times"></i>
                        </button>
                        ` : ''}
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
            html += App.createPagination(Tickets.currentPage, totalPages, 'Tickets.loadTickets');
        }
        
        document.getElementById('ticketsTable').innerHTML = html;
    },
    
    /**
     * Apply filter
     */
    applyFilter() {
        Tickets.loadTickets(1);
    },
    
    /**
     * View ticket details
     */
    async viewTicket(ticketId) {
        try {
            const response = await API.tickets.fetch({ id: ticketId });
            const ticket = response.data;
            Tickets.currentTicket = ticket;
            
            let messagesHtml = '';
            if (ticket.message && ticket.message.length > 0) {
                messagesHtml = ticket.message.map(msg => {
                    const isStaff = msg.is_me;
                    const alignClass = isStaff ? 'message-right' : 'message-left';
                    const bgClass = isStaff ? 'bg-primary text-white' : 'bg-light';
                    
                    return `
                        <div class="message ${alignClass}">
                            <div class="message-bubble ${bgClass}">
                                <div class="message-content">${msg.message}</div>
                                <div class="message-time">${App.formatDate(msg.created_at)}</div>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                messagesHtml = '<p class="text-center text-muted">Chưa có tin nhắn</p>';
            }
            
            const content = `
                <div class="ticket-detail">
                    <div class="ticket-info mb-3">
                        <table class="detail-table">
                            <tr>
                                <td><strong>Tiêu đề:</strong></td>
                                <td>${ticket.subject}</td>
                            </tr>
                            <tr>
                                <td><strong>Người dùng:</strong></td>
                                <td>ID: ${ticket.user_id}</td>
                            </tr>
                            <tr>
                                <td><strong>Cấp độ:</strong></td>
                                <td>${ticket.level === 2 ? 'Cao' : ticket.level === 1 ? 'Trung bình' : 'Thấp'}</td>
                            </tr>
                            <tr>
                                <td><strong>Trạng thái:</strong></td>
                                <td>${ticket.status === 0 ? '<span class="text-success">Đang mở</span>' : '<span class="text-danger">Đã đóng</span>'}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="messages-container">
                        <h5>Lịch sử tin nhắn</h5>
                        <div class="messages-list">
                            ${messagesHtml}
                        </div>
                    </div>
                    
                    ${ticket.status === 0 ? `
                    <div class="reply-form mt-3">
                        <h5>Trả lời ticket</h5>
                        <textarea class="form-control" id="replyMessage" rows="4" placeholder="Nhập nội dung trả lời..."></textarea>
                    </div>
                    ` : ''}
                </div>
                
                <style>
                    .ticket-detail { padding: 20px; }
                    .detail-table { width: 100%; margin-bottom: 20px; }
                    .detail-table td { padding: 8px; border-bottom: 1px solid #eee; }
                    .messages-container { max-height: 400px; overflow-y: auto; padding: 20px; background: #f8f9fa; border-radius: 8px; }
                    .message { margin-bottom: 15px; display: flex; }
                    .message-left { justify-content: flex-start; }
                    .message-right { justify-content: flex-end; }
                    .message-bubble { max-width: 70%; padding: 10px 15px; border-radius: 10px; }
                    .message-content { margin-bottom: 5px; word-wrap: break-word; }
                    .message-time { font-size: 11px; opacity: 0.7; }
                </style>
            `;
            
            const footer = ticket.status === 0 ? `
                <button class="btn btn-outline" onclick="App.closeModal(this)">Đóng</button>
                <button class="btn btn-primary" onclick="Tickets.sendReply()">
                    <i class="fas fa-paper-plane"></i> Gửi trả lời
                </button>
                <button class="btn btn-danger" onclick="Tickets.closeTicketFromModal(${ticketId})">
                    <i class="fas fa-times"></i> Đóng ticket
                </button>
            ` : '';
            
            App.showModal(`Ticket #${ticketId}`, content, footer);
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể tải chi tiết ticket');
        }
    },
    
    /**
     * Send reply
     */
    async sendReply() {
        const message = document.getElementById('replyMessage')?.value.trim();
        
        if (!message) {
            App.showToast('warning', 'Cảnh báo', 'Vui lòng nhập nội dung trả lời');
            return;
        }
        
        if (!Tickets.currentTicket) {
            App.showToast('error', 'Lỗi', 'Không tìm thấy ticket');
            return;
        }
        
        try {
            await API.tickets.reply(Tickets.currentTicket.id, message);
            App.showToast('success', 'Thành công', 'Đã gửi trả lời');
            document.querySelector('.modal-close').click();
            Tickets.loadTickets(Tickets.currentPage);
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể gửi trả lời');
        }
    },
    
    /**
     * Close ticket
     */
    async closeTicket(ticketId) {
        if (!confirm('Bạn có chắc chắn muốn đóng ticket này?')) {
            return;
        }
        
        try {
            await API.tickets.close(ticketId);
            App.showToast('success', 'Thành công', 'Đã đóng ticket');
            Tickets.loadTickets(Tickets.currentPage);
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể đóng ticket');
        }
    },
    
    /**
     * Close ticket from modal
     */
    async closeTicketFromModal(ticketId) {
        if (!confirm('Bạn có chắc chắn muốn đóng ticket này?')) {
            return;
        }
        
        try {
            await API.tickets.close(ticketId);
            App.showToast('success', 'Thành công', 'Đã đóng ticket');
            document.querySelector('.modal-close').click();
            Tickets.loadTickets(Tickets.currentPage);
        } catch (error) {
            App.showToast('error', 'Lỗi', 'Không thể đóng ticket');
        }
    }
};
