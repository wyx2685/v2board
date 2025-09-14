<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="{{ asset('assets/staff/css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-chart-line"></i> Staff Panel</h2>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="#/dashboard" class="nav-link active" data-route="dashboard">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#/users" class="nav-link" data-route="users">
                            <i class="fas fa-users"></i>
                            <span>Quản lý Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#/orders" class="nav-link" data-route="orders">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Quản lý Orders</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#/tickets" class="nav-link" data-route="tickets">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Support Tickets</span>
                        </a>
                    </li>
                    <!-- <li class="nav-item">
                        <a href="#/notices" class="nav-link" data-route="notices">
                            <i class="fas fa-bullhorn"></i>
                            <span>Thông báo</span>
                        </a>
                    </li> -->
                    <li class="nav-item">
                        <a href="#/config" class="nav-link" data-route="config">
                            <i class="fas fa-cog"></i>
                            <span>Cấu hình</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span id="userEmail">Loading...</span>
                </div>
                <button class="btn-logout" id="btnLogout">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="app-header">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="header-title">
                    <h1 id="pageTitle">Dashboard</h1>
                    <nav class="breadcrumb" id="breadcrumb">
                        <span>Home</span> / <span>Dashboard</span>
                    </nav>
                </div>
                
                <div class="header-actions">
                    <button class="btn-refresh" id="btnRefresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="notification-wrapper">
                        <button class="btn-notification">
                            <i class="fas fa-bell"></i>
                            <span class="badge" id="notificationBadge" style="display: none;">0</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content" id="pageContent">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p>Đang tải...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals will be injected here -->
    <div id="modalContainer"></div>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Core Scripts -->
    <script>
        // Pass staff_path to JavaScript
        window.staffPath = '{{ $staff_path }}';
        
        // Add cache buster to avoid old cached files
        const cacheBuster = '?v=' + Date.now();
    </script>
    <script src="{{ asset('assets/staff/js/auth.js') }}{{ '?v=' . time() }}"></script>
    <script src="{{ asset('assets/staff/js/api.js') }}{{ '?v=' . time() }}"></script>
    <script src="{{ asset('assets/staff/js/router.js') }}{{ '?v=' . time() }}"></script>
    
    <!-- Module Scripts -->
    <script src="{{ asset('assets/staff/js/modules/dashboard.js') }}{{ '?v=' . time() }}"></script>
    <script src="{{ asset('assets/staff/js/modules/users.js') }}{{ '?v=' . time() }}"></script>
    <script src="{{ asset('assets/staff/js/modules/orders.js') }}{{ '?v=' . time() }}"></script>
    <script src="{{ asset('assets/staff/js/modules/tickets.js') }}{{ '?v=' . time() }}"></script>
    <script src="{{ asset('assets/staff/js/modules/notices.js') }}{{ '?v=' . time() }}"></script>
    <script src="{{ asset('assets/staff/js/modules/config.js') }}{{ '?v=' . time() }}"></script>
    
    <!-- Main App -->
    <script src="{{ asset('assets/staff/js/app.js') }}{{ '?v=' . time() }}"></script>
</body>
</html>
