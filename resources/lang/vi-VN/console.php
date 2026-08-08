<?php

return [
    'descriptions' => [
        'reset_user' => 'Đặt lại thông tin bảo mật của tất cả người dùng',
        'check_ticket' => 'Kiểm tra trạng thái yêu cầu hỗ trợ',
        'install' => 'Cài đặt V2Board',
        'statistics' => 'Tổng hợp số liệu thống kê',
        'check_server' => 'Kiểm tra trạng thái máy chủ',
        'reset_traffic' => 'Đặt lại lưu lượng sử dụng',
        'send_remind_mail' => 'Gửi email nhắc nhở',
        'reset_password' => 'Đặt lại mật khẩu người dùng',
        'traffic_update' => 'Cập nhật lưu lượng sử dụng',
        'check_commission' => 'Xử lý hoa hồng',
        'check_order' => 'Kiểm tra đơn hàng',
        'update' => 'Cập nhật V2Board',
        'reset_log' => 'Dọn dẹp nhật ký',
        'check_renewal' => 'Xử lý gia hạn tự động',
        'clear_user' => 'Xóa người dùng không có dữ liệu',
    ],
    'reset_user' => [
        'confirmation' => 'Bạn có chắc muốn đặt lại thông tin bảo mật của tất cả người dùng không?',
        'completed' => 'Đã đặt lại thông tin bảo mật của người dùng :email.',
    ],
    'install' => [
        'panel_url' => 'Truy cập bảng quản trị tại http(s)://your-domain/:path. Bạn có thể đổi mật khẩu trong trung tâm người dùng.',
        'remove_env_first' => 'Để cài đặt lại, hãy xóa tệp .env trong thư mục dự án.',
        'copy_env_failed' => 'Không thể sao chép tệp môi trường. Vui lòng kiểm tra quyền truy cập thư mục.',
        'database_host_prompt' => 'Nhập địa chỉ máy chủ cơ sở dữ liệu (mặc định: localhost)',
        'database_name_prompt' => 'Nhập tên cơ sở dữ liệu',
        'database_username_prompt' => 'Nhập tên người dùng cơ sở dữ liệu',
        'database_password_prompt' => 'Nhập mật khẩu cơ sở dữ liệu',
        'admin_email_prompt' => 'Nhập email quản trị viên',
        'admin_registration_failed' => 'Không thể tạo tài khoản quản trị viên. Vui lòng thử lại.',
        'ready' => 'Cài đặt hoàn tất.',
        'admin_email' => 'Email quản trị viên: :email',
        'admin_password' => 'Mật khẩu quản trị viên: :password',
        'admin_password_too_short' => 'Mật khẩu quản trị viên phải có ít nhất 8 ký tự.',
    ],
    'database' => [
        'connection_failed' => 'Không thể kết nối tới cơ sở dữ liệu.',
        'file_missing' => 'Không tìm thấy tệp cơ sở dữ liệu.',
        'file_invalid' => 'Tệp cơ sở dữ liệu không đúng định dạng.',
        'importing' => 'Đang nhập cơ sở dữ liệu, vui lòng chờ...',
        'imported' => 'Đã nhập cơ sở dữ liệu.',
    ],
    'check_server' => [
        'offline_notification' => "Cảnh báo máy chủ ngoại tuyến\n----\nTên máy chủ: :name\nĐịa chỉ máy chủ: :host\n",
    ],
    'reset_traffic' => [
        'failed' => 'Không thể đặt lại lưu lượng người dùng: :error',
        'failed_notification' => ':date Không thể đặt lại lưu lượng người dùng: :error',
    ],
    'reset_password' => [
        'email_not_found' => 'Không tìm thấy địa chỉ email.',
        'failed' => 'Không thể đặt lại mật khẩu.',
        'completed' => 'Đặt lại mật khẩu thành công.',
        'new_password' => 'Mật khẩu mới là :password. Vui lòng đổi mật khẩu sớm nhất có thể.',
    ],
    'update' => [
        'completed' => 'Cập nhật hoàn tất và dịch vụ hàng đợi đã được khởi động lại. Bạn không cần thực hiện thêm thao tác nào.',
    ],
    'clear_user' => [
        'completed' => 'Đã xóa người dùng không có dữ liệu: :count',
    ],
    'statistics' => [
        'completed' => 'Đã hoàn tất thống kê trong :seconds giây.',
        'server_failed' => 'Không thể lưu thống kê máy chủ.',
        'user_failed' => 'Không thể lưu thống kê người dùng.',
    ],
    'traffic_update' => [
        'failed' => 'Cập nhật lưu lượng thất bại: :error',
    ],
    'renewal' => [
        'insufficient_balance' => 'Số dư người dùng không đủ để tự động gia hạn.',
        'failed' => 'Tự động gia hạn thất bại.',
        'disable_failed' => 'Tự động gia hạn thất bại và không thể tắt tính năng này cho người dùng.',
    ],
];
