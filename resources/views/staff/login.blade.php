<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Staff</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #555;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
            display: none;
        }

        .success-message {
            background: #efe;
            color: #3c3;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
            display: none;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Staff Dashboard</h1>
            <p>Đăng nhập vào hệ thống quản lý</p>
        </div>

        <div id="errorMessage" class="error-message"></div>
        <div id="successMessage" class="success-message"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Nhập email của bạn">
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu">
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <span id="btnText">Đăng nhập</span>
            </button>
        </form>

        <div class="back-link">
            <a href="/">← Quay về trang chủ</a>
        </div>
    </div>

    <script>
        const staffPath = '{{ $staff_path }}';
        
        // Check if already logged in
        const token = localStorage.getItem('staff_token');
        if (token) {
            // Verify token and staff access
            fetch('/api/v1/staff/info', {
                headers: {
                    'Authorization': token
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                } else {
                    throw new Error('Staff access denied');
                }
            })
            .then(data => {
                if (data.status === 'success' && data.data && data.data.is_staff) {
                    // Update stored user data with latest info
                    localStorage.setItem('staff_user', JSON.stringify(data.data));
                    window.location.href = '/' + staffPath + '/';
                } else {
                    // Not staff or error
                    localStorage.removeItem('staff_token');
                    localStorage.removeItem('staff_user');
                }
            })
            .catch(() => {
                localStorage.removeItem('staff_token');
                localStorage.removeItem('staff_user');
            });
        }

        // Handle login form
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const errorDiv = document.getElementById('errorMessage');
            const successDiv = document.getElementById('successMessage');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            
            // Hide messages
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            // Show loading
            loginBtn.disabled = true;
            btnText.innerHTML = '<span class="loading"></span>';
            
            const formData = new FormData(e.target);
            const data = {
                email: formData.get('email'),
                password: formData.get('password')
            };
            
            try {
                const response = await fetch('/api/v1/passport/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok && result.data) {
                    // Get auth_data (JWT token) from passport login result
                    const authData = result.data.auth_data;
                    
                    if (!authData) {
                        errorDiv.textContent = 'Không thể lấy token xác thực';
                        errorDiv.style.display = 'block';
                        loginBtn.disabled = false;
                        btnText.textContent = 'Đăng nhập';
                        return;
                    }
                    
                    // Verify staff access using auth_data
                    const verifyResponse = await fetch('/api/v1/staff/info', {
                        headers: {
                            'Authorization': authData
                        }
                    });
                    
                    if (verifyResponse.ok) {
                        const staffInfo = await verifyResponse.json();
                        
                        if (staffInfo.status === 'success' && staffInfo.data && staffInfo.data.is_staff) {
                            // Save auth_data as staff token
                            localStorage.setItem('staff_token', authData);
                            localStorage.setItem('staff_user', JSON.stringify(staffInfo.data));
                            
                            // Show success message
                            successDiv.textContent = 'Đăng nhập thành công! Đang chuyển hướng...';
                            successDiv.style.display = 'block';
                            
                            // Redirect to dashboard
                            setTimeout(() => {
                                window.location.href = '/' + staffPath + '/';
                            }, 1000);
                        } else {
                            // Not staff
                            errorDiv.textContent = 'Bạn không có quyền truy cập hệ thống staff';
                            errorDiv.style.display = 'block';
                            
                            // Reset button
                            loginBtn.disabled = false;
                            btnText.textContent = 'Đăng nhập';
                        }
                    } else {
                        // Can't verify staff access
                        const errorData = await verifyResponse.json().catch(() => ({}));
                        errorDiv.textContent = errorData.message || 'Không thể xác thực quyền truy cập staff';
                        errorDiv.style.display = 'block';
                        
                        // Reset button
                        loginBtn.disabled = false;
                        btnText.textContent = 'Đăng nhập';
                    }
                } else {
                    // Login failed
                    errorDiv.textContent = result.message || 'Email hoặc mật khẩu không đúng';
                    errorDiv.style.display = 'block';
                    
                    // Reset button
                    loginBtn.disabled = false;
                    btnText.textContent = 'Đăng nhập';
                }
            } catch (error) {
                // Show error
                errorDiv.textContent = 'Lỗi kết nối. Vui lòng thử lại sau.';
                errorDiv.style.display = 'block';
                
                // Reset button
                loginBtn.disabled = false;
                btnText.textContent = 'Đăng nhập';
            }
        });
    </script>
</body>
</html>
