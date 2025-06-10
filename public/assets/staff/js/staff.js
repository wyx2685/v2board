
const token = localStorage.getItem('authorization');
if (!token) {
  window.location.href = '/#/login';
}

const headers = { 'Authorization': token };


function renderDashboard() {
  const infoUrl = '/api/v1/staff/info';
  const statUrl = '/api/v1/staff/stat';

  document.getElementById('page-title').innerText = 'Dashboard Nhân Viên';
  document.getElementById('content-area').innerHTML = `
    <div class="row" id="info-cards"></div>
    <div class="row mt-4" id="stat-cards"></div>
  `;

  fetch(infoUrl, { headers })
    .then(res => res.json())
    .then(data => {
      document.getElementById('info-cards').innerHTML = `
        <div class="col-md-4">
          <div class="card bg-primary text-white p-3">
            <h5>Số dư</h5>
            <p>${(data.balance / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })}</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-success text-white p-3">
            <h5>Hoa hồng có thể rút</h5>
            <p>${(data.commission_balance / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })}</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-warning text-dark p-3">
            <h5>Chiết khấu hoa hồng</h5>
            <p>${data.commission_rate}%</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-info text-white p-3">
            <h5>Chiết khấu độc quyền</h5>
            <p>${data.discount}%</p>
          </div>
        </div>
      `;
    })
    .catch(() => Swal.fire({
          icon: 'error',
          title: 'Lỗi!',
          text: 'không thể lấy thông tin nhân viên.'
        }));

  fetch(statUrl, { headers })
    .then(res => res.json())
    .then(data => {
      document.getElementById('stat-cards').innerHTML = `
      <div class="col-md-4">
        <div class="card bg-secondary text-white p-3">
          <h5>Thu nhập hôm nay</h5>
          <p>${(data.today_income / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })}</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-dark text-white p-3">
          <h5>Thu nhập tháng này</h5>
          <p>${(data.month_income / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })}</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-light text-dark p-3">
          <h5>Người dùng mới tháng này</h5>
          <p>${data.new_users}</p>
        </div>
      </div>
    `;
    })
    .catch(() => Swal.fire({
          icon: 'error',
          title: 'Lỗi!',
          text: 'không thể lấy thống kê.'
        }));
}

function renderConfig() {
  document.getElementById('page-title').innerText = 'Chỉnh sửa Webcon';
  document.getElementById('content-area').innerHTML = '<p>Đang tải dữ liệu...</p>';

  fetch('/api/v1/staff/config', { headers })
    .then(res => res.json())
    .then(data => {
      document.getElementById('content-area').innerHTML = `
        <form id="config-form" class="p-3">
          <div class="mb-3"><label>Email admin webcon</label><input class="form-control" name="email" value="${data.email || ''}" disabled></div>
          <div class="mb-3"><label>Domain</label><input class="form-control" name="domain" value="${data.domain || ''}" disabled></div>
          <div class="mb-3">
          <label>Tiêu đề web</label>
          <input class="form-control" name="title" value="${data.title || ''}" placeholder="Nhập tiêu đề web">
        </div>
        
        <div class="mb-3">
          <label>Mô tả web</label>
          <input class="form-control" name="description" value="${data.description || ''}" placeholder="Nhập mô tả web">
        </div>
        
        <div class="mb-3">
          <label>Link Logo</label>
          <input class="form-control" name="logo" value="${data.logo || ''}" placeholder="Nhập URL logo đuôi .png .jpg ...">
        </div>
        
        <div class="mb-3">
          <label>Link hình nền</label>
          <input class="form-control" name="background_url" value="${data.background_url || ''}" placeholder="Nhập URL hình nền đuôi .png jpg ...">
        </div>
        
        <div class="mb-3">
          <label>Html chân trang</label>
          <textarea class="form-control" name="custom_html" placeholder="Nhập HTML chân trang">${data.custom_html || ''}</textarea>
        </div>

          <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary me-2" onclick="handleRouting()">reload</button>
            <button type="submit" class="btn btn-primary">Cập Nhật</button>
          </div>
        </form>
      `;

      document.getElementById('config-form').addEventListener('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(this);
          const payload = {};
          formData.forEach((value, key) => payload[key] = value);
        
          fetch('/api/v1/staff/configsave', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': token
            },
            body: JSON.stringify(payload)
          })
          .then(async res => {
            const response = await res.json();
        
            if (res.ok && response.status === 'success') {
              Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Lưu thành công!',
                showConfirmButton: false,
                timer: 3000
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: response.message || 'Có lỗi xảy ra!'
              });
            }
          })
          .catch(() => Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: 'Có lỗi xảy ra khi lưu.'
          }));
        });

      
    })
    .catch(() => document.getElementById('content-area').innerHTML = '<p>Lỗi tải dữ liệu!</p>');
}

function renderUser() {
  // Cập nhật tiêu đề và nội dung
  document.getElementById('page-title').innerText = 'Quản lý User';
  document.getElementById('content-area').innerHTML = `
    <div class="d-flex mb-3">
      <select id="search-type" class="form-select me-2" style="max-width: 120px;">
        <option value="email">Email</option>
        <option value="id">ID</option>
      </select>
      <input type="text" id="search-user" class="form-control me-2" placeholder="Nhập giá trị tìm kiếm">
      <button class="btn btn-primary" id="search-button">Tìm kiếm</button>
    </div>
    <div id="user-table">Đang tải danh sách user...</div>
  `;

  // Khởi tạo trạng thái
  let currentSearchType = 'email';
  let currentSearchKeyword = '';
  const urlParams = new URLSearchParams(window.location.search);
  let currentPage = parseInt(urlParams.get('page')) || 1;

  // Hàm cập nhật URL
  function updateUrl(page) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', page);
    window.history.pushState({}, '', `${window.location.pathname}?${params}`);
  }

  // Hàm fetch danh sách user
  function fetchUserList(type = 'email', keyword = '', page = 1, limit = 10) {
    let query = `${type}=${encodeURIComponent(keyword)}&page=${page}&limit=${limit}`;
    if (!keyword) query = `page=${page}&limit=${limit}`;

    document.getElementById('user-table').innerHTML = 'Đang tải dữ liệu...';

    fetch(`/api/v1/staff/finduser?${query}`, { headers })
      .then(res => res.json())
      .then(res => {
        const data = res.data || [];
        const total = res.total || 0;
        const current = res.current || 1;
        const pageSize = res.pageSize || 10;
        const totalPages = Math.ceil(total / pageSize);

        if (!Array.isArray(data) || data.length === 0) {
          document.getElementById('user-table').innerHTML = '<p>Không tìm thấy user nào.</p>';
          return;
        }

        let tableHTML = `
          <table class="table table-bordered table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Gói dịch vụ</th>
                <th>Đã dùng</th>
                <th>Giới hạn</th>
                <th>Thiết bị online</th>
                <th>Hạn dùng</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
        `;

        data.forEach(user => {
          const datauserGB = formatSize(user.u + user.d);
          const dataLimitGB = formatSize(user.transfer_enable);
          const plan = user.plan_name ? user.plan_name : '<span class="text-muted">Không có gói</span>';
          const expDate = formatTimestamp(user.expired_at);
          tableHTML += `
            <tr>
              <td>${user.id}</td>
              <td>${user.email}</td>
              <td>${plan}</td>
              <td>${datauserGB}</td>
              <td>${dataLimitGB}</td>
              <td>${user.alive_ip}</td>
              <td>${expDate}</td>
              <td>
                <button class="btn btn-sm btn-secondary me-1" onclick="copyToken('${user.subscribe_url}')">Sao chép</button>
                <button class="btn btn-sm btn-warning me-1" onclick="getQR('${user.subscribe_url}')">Lấy QR</button>
              </td>
            </tr>
          `;
        });

        tableHTML += '</tbody></table>';

        let paginationHTML = `<nav><ul class="pagination justify-content-center mt-3">`;
        if (current > 1) {
          paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${current - 1})">Previous</a></li>`;
        }
        for (let i = 1; i <= totalPages; i++) {
          paginationHTML += `<li class="page-item ${i === current ? 'active' : ''}"><a class="page-link" href="#" onclick="goToPage(${i})">${i}</a></li>`;
        }
        if (current < totalPages) {
          paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${current + 1})">Next</a></li>`;
        }
        paginationHTML += `</ul></nav>`;

        document.getElementById('user-table').innerHTML = tableHTML + paginationHTML;
      })
      .catch(() => {
        document.getElementById('user-table').innerHTML = '<p>Lỗi tải dữ liệu user.</p>';
      });
  }

  // Xử lý nút tìm kiếm
  document.getElementById('search-button').addEventListener('click', () => {
    currentSearchType = document.getElementById('search-type').value;
    currentSearchKeyword = document.getElementById('search-user').value.trim();
    currentPage = 1;
    updateUrl(currentPage);
    fetchUserList(currentSearchType, currentSearchKeyword, currentPage);
  });

  // Xử lý phân trang
  window.goToPage = function(page) {
    currentPage = page;
    updateUrl(page);
    fetchUserList(currentSearchType, currentSearchKeyword, page);
  };

  // Loại bỏ listener cũ trước khi thêm mới
  const popstateHandler = () => {
    const params = new URLSearchParams(window.location.search);
    currentPage = parseInt(params.get('page')) || 1;
    fetchUserList(currentSearchType, currentSearchKeyword, currentPage);
  };

  // Xóa tất cả listener popstate cũ (nếu có)
  window.removeEventListener('popstate', window.popstateHandler);
  // Gán listener mới
  window.popstateHandler = popstateHandler;
  window.addEventListener('popstate', popstateHandler);

  // Tải danh sách ban đầu
  fetchUserList(currentSearchType, currentSearchKeyword, currentPage);

  // Các hàm hỗ trợ
  function formatSize(bytes) {
    if (bytes === null || bytes === undefined || isNaN(bytes)) return '0 GB';
    const GB = 1024 * 1024 * 1024;
    const TB = GB * 1024;
    if (bytes >= TB) return Math.round(bytes / TB) + ' TB';
    if (bytes >= GB) return Math.round(bytes / GB) + ' GB';
    return '0 GB';
  }

  function formatTimestamp(timestamp) {
    if (!timestamp || timestamp === 0) {
      return `<span class="badge bg-danger">-</span>`;
    }
    const now = Math.floor(Date.now() / 1000);
    const date = new Date(timestamp * 1000);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const formatted = `${day}-${month}-${year} ${hours}:${minutes}`;
    return timestamp > now
      ? `<span class="badge bg-success">${formatted}</span>`
      : `<span class="badge bg-danger">${formatted}</span>`;
  }

  window.copyToken = function(subscribe_url) {
    navigator.clipboard.writeText(subscribe_url).then(() => {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Đã sao chép URL đăng ký!',
        showConfirmButton: false,
        timer: 3000
      });
    });
  };

  window.getQR = function(subscribe_url) {
    Swal.fire({
      title: 'Mã QR',
      html: `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(subscribe_url)}" alt="QR Code">`
    });
  };
}



function renderOrder() {
  document.getElementById('page-title').innerText = 'Quản lý Order';
  document.getElementById('content-area').innerHTML = '<p>Chức năng này đang phát triển...</p>';
}

function handleRouting() {
  const path = window.location.pathname;
  if (path.includes('/config')) {
    renderConfig();
  } else if (path.includes('/user')) {
    renderUser();
  } else if (path.includes('/order')) {
    renderOrder();
  } else {
    renderDashboard();
  }
}

window.onpopstate = handleRouting;
document.addEventListener('DOMContentLoaded', handleRouting);
