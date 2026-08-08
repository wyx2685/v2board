(function (window) {
    'use strict';

    var part1 = String.raw`
ID nhóm
Tên nhóm
Số người dùng
Số nút
Thao tác
Chỉnh sửa
Xóa
Quản lý nhóm quyền
 Thêm nhóm quyền
Quốc gia
Cấu hình hệ thống
Trang web
Tên trang web
Dùng ở những nơi cần hiển thị tên trang web.
Vui lòng nhập tên trang web
Mô tả trang web
Dùng ở những nơi cần hiển thị mô tả trang web.
Vui lòng nhập mô tả trang web
URL trang web
URL mới nhất của trang web, được dùng trong email và những nơi cần URL.
Vui lòng nhập URL trang web, không thêm / ở cuối
Bắt buộc HTTPS
Bật khi trang web không dùng HTTPS nhưng CDN hoặc reverse proxy bắt buộc HTTPS.
Dùng ở những nơi cần hiển thị LOGO.
Vui lòng nhập URL LOGO, không thêm / ở cuối
URL đăng ký
Dùng cho đăng ký; để trống sẽ dùng URL trang web. Dùng dấu phẩy để phân tách nhiều URL đăng ký ngẫu nhiên.
Vui lòng nhập URL đăng ký, không thêm / ở cuối. Dùng dấu phẩy cho nhiều tên miền
Đường dẫn đăng ký
Dùng cho đăng ký; để trống sẽ dùng /api/v1/client/subscribe. Hãy đặt nếu cần đường dẫn khác.
URL điều khoản người dùng (TOS)
Dùng để chuyển đến điều khoản người dùng (TOS)
Vui lòng nhập URL điều khoản, không thêm / ở cuối
Dừng đăng ký người dùng mới
Khi bật, không ai có thể đăng ký.
Dùng thử khi đăng ký
Chọn gói dùng thử; nếu không có tùy chọn, hãy thêm gói trong Quản lý gói.
Vui lòng chọn gói dùng thử
Tắt
Thời gian dùng thử (giờ)
Vui lòng nhập
Đơn vị tiền tệ
Chỉ dùng để hiển thị; thay đổi sẽ áp dụng cho tất cả đơn vị tiền trong hệ thống.
Ký hiệu tiền tệ
Bảo mật
Xác minh email
Khi bật, người dùng bắt buộc phải xác minh email.
Cấm nhiều bí danh Gmail
Khi bật, không thể đăng ký bằng nhiều bí danh Gmail.
Chế độ an toàn
Khi bật, các tên miền trỏ tới trang này ngoài URL trang web sẽ bị trả về 403.
Đường dẫn quản trị
Đường dẫn trang quản trị; thay đổi sẽ thay thế đường dẫn admin hiện tại
Danh sách cho phép hậu tố email
Khi bật, chỉ hậu tố email trong danh sách mới được phép đăng ký.
Hậu tố cho phép
Dùng dấu phẩy để phân tách, ví dụ: qq.com,gmail.com.
Nhập tên miền hậu tố, phân tách bằng dấu phẩy, ví dụ: qq.com,gmail.com
Chống bot
Khi bật, Google reCAPTCHA sẽ được dùng để chống bot.
Khóa bí mật
Khóa bí mật đăng ký tại Google reCAPTCHA.
Khóa trang web
Khóa trang web đăng ký tại Google reCAPTCHA.
Giới hạn đăng ký theo IP
Khi bật, IP đạt ngưỡng tài khoản sẽ bị hạn chế đăng ký. Việc xác định IP có thể bị ảnh hưởng bởi CDN hoặc proxy.
Số lần
Bật phạt sau khi đạt số lần đăng ký.
Thời gian phạt (phút)
Phải chờ hết thời gian phạt mới có thể đăng ký lại.
Giới hạn chống brute-force
Khi bật, tài khoản có quá nhiều lần đăng nhập thất bại sẽ bị hạn chế.
Bật phạt sau khi đạt số lần thất bại.
Phải chờ hết thời gian phạt mới có thể đăng nhập lại.
Gói dịch vụ
Cho phép người dùng đổi gói
Khi bật, người dùng có thể thay đổi gói dịch vụ.
Cách đặt lại lưu lượng hàng tháng
Cách đặt lại lưu lượng toàn hệ thống, mặc định ngày 1 hàng tháng. Có thể đặt riêng cho từng gói.
Vui lòng chọn cách đặt lại gói
Ngày 1 hàng tháng
Đặt lại theo tháng
Không đặt lại
Ngày 1 tháng 1 hàng năm
Đặt lại theo năm
Bật phương án khấu trừ
Khi bật, hệ thống sẽ khấu trừ giá trị gói cũ khi người dùng đổi gói; xem tài liệu.
Cho phép bắt đầu sớm chu kỳ lưu lượng
Khi hết lưu lượng, người dùng có thể trừ thời hạn gói để đặt lại. Theo tháng sẽ trừ phần thời hạn còn lại; theo ngày 1 sẽ trừ 30 ngày.
Kích hoạt sự kiện khi mua gói mới
Tác vụ này sẽ chạy khi mua gói mới hoàn tất.
Vui lòng chọn sự kiện
Không thực hiện hành động
Đặt lại lưu lượng người dùng
Kích hoạt sự kiện khi gia hạn gói
Tác vụ này sẽ chạy khi gia hạn gói hoàn tất.
Kích hoạt sự kiện khi thay đổi gói
Tác vụ này sẽ chạy khi thay đổi gói hoàn tất.
Hiển thị thông tin gói trong nội dung đăng ký
Khi bật, thông tin gói sẽ được xuất khi người dùng lấy danh sách nút.
Chế độ hiệu lực của liên kết đăng ký
Thời hạn của liên kết sau khi người dùng lấy.
Vui lòng chọn
Hiệu lực vĩnh viễn
Hiệu lực một lần
Hiệu lực có thời hạn
Thời hạn liên kết đăng ký (phút)
Liên kết đăng ký sẽ hết hạn sau thời gian này.
Nạp tiền
Thưởng nạp tiền
Phần thưởng nhận được khi nạp một số tiền nhất định.
Nhập số tiền nạp:số tiền thưởng, phân tách bằng dấu phẩy\nVí dụ 50:18,100:38, 200:88
Phiếu hỗ trợ
Cài đặt phiếu hỗ trợ
Vui lòng chọn trạng thái phiếu hỗ trợ.
Mở phiếu hoàn toàn
Chỉ người dùng có đơn hàng trả phí
Cấm phiếu hoàn toàn
Giới thiệu & hoa hồng
Bắt buộc có lời mời
Khi bật, chỉ người được mời mới có thể đăng ký.
Phần trăm hoa hồng giới thiệu
Tỷ lệ hoa hồng toàn hệ thống; có thể cấu hình riêng cho từng người dùng.
Số mã mời tối đa người dùng được tạo
Mã mời không bao giờ hết hạn
Khi bật, mã mời không hết hạn sau khi dùng; nếu không, mã sẽ hết hạn ngay sau khi dùng.
Chỉ trả hoa hồng lần đầu
Khi bật, chỉ lần thanh toán đầu của người được mời mới sinh hoa hồng; có thể cấu hình riêng theo người dùng.
Tự động xác nhận hoa hồng
Khi bật, hoa hồng sẽ tự động được xác nhận sau 3 ngày kể từ khi hoàn tất đơn hàng.
Ngưỡng yêu cầu rút tiền (CNY)
Yêu cầu rút nhỏ hơn ngưỡng sẽ không được gửi.
Phương thức rút tiền
Các phương thức rút tiền được hỗ trợ.
Nhập các phương thức, phân tách bằng dấu phẩy, ví dụ: Alipay,USDT,PayPal
Tắt rút tiền
Khi tắt, người dùng không thể yêu cầu rút; hoa hồng giới thiệu sẽ vào thẳng số dư.
Phân phối ba cấp
Khi bật, hoa hồng được chia theo ba tỷ lệ đã đặt; tổng không được vượt 100%.
Tỷ lệ người mời cấp 1
Nhập tỷ lệ, ví dụ: 50
Tỷ lệ người mời cấp 2
Nhập tỷ lệ, ví dụ: 30
Tỷ lệ người mời cấp 3
Nhập tỷ lệ, ví dụ: 20
Cá nhân hóa
Nếu triển khai trang quản trị V2Board tách biệt frontend/backend, cấu hình trang này sẽ không có hiệu lực. Tìm hiểu
Tách frontend/backend
Kiểu thanh bên
Sáng
Tối
Kiểu đầu trang
Màu chủ đề
Mặc định
Đen
Xanh lam đậm
Xanh sữa
Nền
Sẽ hiển thị trên trang đăng nhập quản trị.
Nút
URL API kết nối nút
URL chuyên dụng để kết nối nhanh nút v2node.
Khóa giao tiếp
Khóa giao tiếp giữa V2Board và nút, giúp người khác không lấy được dữ liệu.
Khoảng thăm dò thao tác lấy dữ liệu nút
Tần suất nút lấy dữ liệu từ bảng điều khiển.
giây
Khoảng thăm dò thao tác đẩy dữ liệu nút
Tần suất nút đẩy dữ liệu lên bảng điều khiển.
Ngưỡng tối thiểu báo cáo lưu lượng người dùng
Mỗi lần đẩy chỉ báo cáo người dùng có lưu lượng tích lũy cao hơn ngưỡng; phần chưa báo cáo sẽ tiếp tục tích lũy
Ngưỡng tối thiểu thống kê số thiết bị của nút
Mỗi lần đẩy chỉ thống kê IP thiết bị trực tuyến có lưu lượng cao hơn ngưỡng
Dùng chế độ nới lỏng cho giới hạn thiết bị toàn hệ thống
Khi bật, cùng một IP dùng nhiều nút chỉ được tính là một thiết bị
Email
Sau khi thay đổi cấu hình trang này, cần khởi động lại dịch vụ hàng đợi. Cấu hình này ưu tiên hơn cấu hình email trong .env.
Địa chỉ máy chủ SMTP
Địa chỉ dịch vụ do nhà cung cấp email cung cấp
Cổng SMTP
Các cổng phổ biến: 25, 465, 587
Mã hóa SMTP
Cổng 465 thường dùng SSL, cổng 587 thường dùng TLS
Tài khoản SMTP
Tài khoản do nhà cung cấp email cung cấp
Mật khẩu SMTP
Mật khẩu do nhà cung cấp email cung cấp
Địa chỉ người gửi
Địa chỉ gửi do nhà cung cấp email cung cấp
Mẫu email
Xem tài liệu để biết cách tùy chỉnh mẫu email
Gửi email kiểm tra
Email sẽ được gửi tới hộp thư của quản trị viên hiện tại
Token bot
Vui lòng nhập token do BotFather cung cấp.
Cài đặt Webhook
Cài đặt Webhook cho bot; nếu không sẽ không nhận được thông báo Telegram.
Cài đặt một chạm
Bật thông báo bot
Khi bật, bot sẽ gửi thông báo cơ bản cho quản trị viên và người dùng đã liên kết Telegram.
`.slice(1, -1).split('\n').map(function (value) { return value.replace(/\\n/g, '\n'); });

    var part2 = String.raw`
Địa chỉ nhóm
Sau khi điền, thông tin sẽ hiển thị phía người dùng hoặc được dùng khi cần.
Dùng để quản lý phiên bản và cập nhật ứng dụng khách
Phiên bản và URL tải xuống cho Windows
Phiên bản và URL tải xuống cho macOS
Phiên bản và URL tải xuống cho Android
Quản lý người dùng
Nhật ký lưu lượng của họ
Nhập nội dung trả lời phiếu...
Đường dẫn
Chỉnh sửa nút
Tạo nút
Tên nút
Vui lòng nhập tên nút
Hệ số
Vui lòng nhập hệ số nút
Nhãn nút
Nhập và nhấn Enter để thêm nhãn
Nhóm quyền
Thêm nhóm quyền
Vui lòng chọn nhóm quyền
Địa chỉ nút
Địa chỉ hoặc IP
Cổng kết nối
Cổng kết nối người dùng
Cổng dịch vụ
Cổng mở phía máy chủ
Thuật toán mã hóa
Làm rối
Không có
Nút cha
https://docs.v2board.com/use/node.html#quan-he-nut-cha-va-nut-con
Xem thêm giải đáp
Nhóm tuyến
Vui lòng chọn nhóm tuyến
Hủy
Gửi
Hiển thị
Tiêu đề
Thời gian tạo
Quản lý thông báo
 Thêm thông báo
Chỉnh sửa thông báo
Tạo thông báo
Vui lòng nhập tiêu đề thông báo
Nội dung thông báo
Vui lòng nhập nội dung thông báo
Nhãn thông báo
URL hình ảnh
Vui lòng nhập URL hình ảnh
Giám sát hàng đợi
Tổng quan
Số tác vụ hiện tại
Số tác vụ xử lý trong giờ qua
Số lỗi trong 7 ngày
Trạng thái
Đang chạy
Chưa khởi động
Chi tiết tác vụ hiện tại
Tên hàng đợi
Hàng đợi đơn hàng
Hàng đợi email
Hàng đợi email hàng loạt
Hàng đợi tin nhắn Telegram
Hàng đợi thống kê
Hàng đợi tiêu thụ lưu lượng
Số tác vụ
Số nhiệm vụ
Thời gian chiếm dụng
Hôm nay
Bây giờ
Về hôm nay
Xác nhận
Chọn giờ
Chọn ngày
Chọn tuần
Xóa
Tháng
Năm
Tháng trước (Page Up)
Tháng sau (Page Down)
Chọn tháng
Chọn năm
Chọn thập kỷ
Năm YYYY
Ngày D
DD/MM/YYYY
DD/MM/YYYY HH:mm:ss
Năm trước (Control + phím trái)
Năm sau (Control + phím phải)
Thập kỷ trước
Thập kỷ sau
Thế kỷ trước
Thế kỷ sau
mục/trang
Đến
trang
Trang trước
Trang sau
Lùi 5 trang
Tiến 5 trang
Lùi 3 trang
Tiến 3 trang
Bật
Tên phiếu
Loại
Số tiền
Tỷ lệ
Mã phiếu
Đã sao chép
Số lần còn lại
Không giới hạn
Thời hạn
Cảnh báo
Bạn chắc chắn muốn xóa mục này?
Quản lý phiếu giảm giá
 Thêm phiếu giảm giá
Chỉnh sửa phiếu giảm giá
Tạo phiếu giảm giá
Tên
Vui lòng nhập tên phiếu giảm giá
Mã phiếu tùy chỉnh
Mã phiếu tùy chỉnh (để trống sẽ tạo ngẫu nhiên)
Thông tin ưu đãi
Giảm theo số tiền
Giảm theo tỷ lệ
Vui lòng nhập giá trị
Thời hạn phiếu giảm giá
Số lần dùng tối đa
Giới hạn số lần dùng tối đa; hết lượt sẽ không dùng được (để trống là không giới hạn)
Số lần mỗi người dùng có thể dùng
Giới hạn số lần mỗi người dùng có thể dùng (để trống là không giới hạn)
Chỉ định gói
Chỉ gói được chọn mới dùng ưu đãi (để trống là không giới hạn)
Chỉ định chu kỳ
Chỉ chu kỳ được chọn mới dùng ưu đãi (để trống là không giới hạn)
Số lượng tạo
Nhập số lượng để tạo hàng loạt
Tạo người dùng
Tạo
Email
Tài khoản (để trống nếu tạo hàng loạt)
Tên miền
Mật khẩu
Để trống thì mật khẩu giống email
Thời gian hết hạn
Chọn ngày hết hạn; để trống là không hết hạn
Gói dịch vụ
Vui lòng chọn gói cho người dùng
Nếu tạo hàng loạt, hãy nhập số lượng
Thấp
Trung bình
Cao
Chủ đề
Mức độ phiếu
Trạng thái phiếu
Đã trả lời
Chờ trả lời
Đã đóng
Trả lời cuối
Xem
Quản lý phiếu
Đã bật
Nhập email để tìm
Đăng nhập Trung tâm quản trị
Đăng nhập
Quên mật khẩu
Chạy lệnh sau trong thư mục trang web để lấy lại mật khẩu
php artisan reset:password email-quản-trị-viên
Đã hiểu
Ngày
Tải lên
Tải xuống
Nhật ký lưu lượng
Nhắc nhở
Bạn chắc chắn muốn cấm?
Bạn chắc chắn muốn xóa?
Đặt lại thông tin bảo mật
Xác nhận đặt lại
thông tin bảo mật?
Xóa người dùng
Xác nhận xóa
thông tin người dùng?
Trực tuyến lần cuối
Chưa từng trực tuyến
Cấm
Bình thường
Nhóm quyền
Đã dùng (G)
Lưu lượng (G)
Số thiết bị
Hiệu lực dài hạn
Số dư
Hoa hồng
Thời gian tham gia
 Chỉnh sửa
 Phân bổ đơn hàng
 Sao chép URL đăng ký
 Đặt lại UUID và URL đăng ký
 Đơn hàng của họ
`.slice(1, -1).split('\n');

    var part3 = String.raw`
 Lời mời của họ
 Nhật ký lưu lượng của họ
 Xóa người dùng
Thao tác
Mẹo: có thể lọc trước, sau đó thực hiện thao tác trên những người dùng đã lọc.
Gần đúng
ID người dùng
Không có gói
Lưu lượng
Trạng thái tài khoản
Email người mời
ID người mời
Ghi chú
Quản trị viên
Có
Không
 Bộ lọc
 Xuất CSV
 Gửi email
 Cấm hàng loạt
 Xóa hàng loạt
Đang gửi
Chỉnh sửa phương thức thanh toán
Thêm phương thức thanh toán
Lưu
Thêm
Tên hiển thị
Dùng để hiển thị ở giao diện người dùng
URL biểu tượng (tùy chọn)
Dùng để hiển thị ở giao diện người dùng (https://x.com/icon.svg)
Tên miền thông báo tùy chỉnh (tùy chọn)
Thông báo của cổng thanh toán sẽ gửi tới tên miền này (https://x.com)
Phí theo phần trăm (tùy chọn)
Cộng phí vào số tiền đơn hàng
Phí cố định (tùy chọn)
Tệp giao diện
Giao diện thanh toán
Địa chỉ thông báo
Cổng thanh toán sẽ gửi dữ liệu tới địa chỉ này; hãy cho phép địa chỉ qua tường lửa.
Cấu hình thanh toán
 Thêm phương thức thanh toán
Giá trị không được trống
Bộ lọc
Nội dung tìm kiếm không được trống
Điều kiện
Tên trường
Nội dung cần tìm
Vui lòng chọn giá trị
Giá trị
 Thêm điều kiện
Đặt lại
Tìm kiếm
Đang xuất
Đã thêm vào hàng đợi thực thi
Đặt lại thành công
Xóa thành công
Chỉnh sửa gói
Tạo gói
Tên gói
Vui lòng nhập tên gói
Mô tả gói
Vui lòng nhập mô tả gói, hỗ trợ HTML
Cài đặt giá bán
Để trống số tiền thì sẽ không bán
Hàng tháng
Hàng quý
Nửa năm
Hàng năm
Hai năm
Ba năm
Một lần
Gói đặt lại
Lưu lượng gói
Vui lòng nhập lưu lượng gói
Giới hạn số thiết bị
Để trống là không giới hạn
Cách đặt lại lưu lượng
Theo cài đặt hệ thống
Số người dùng tối đa
Giới hạn tốc độ
Khi chọn, thay đổi lưu lượng, giới hạn tốc độ và nhóm quyền sẽ áp dụng cho người dùng thuộc gói này
Bắt buộc cập nhật cho người dùng
Sắp xếp
Trạng thái bán
Gia hạn
Khi gói ngừng bán, người đã mua có được gia hạn không
Thống kê
Nửa năm
 Xóa
Quản lý gói
 Thêm gói
Lưu thành công
Chỉnh sửa bài kiến thức
Thêm bài kiến thức
Vui lòng nhập tiêu đề bài viết
Danh mục
Nhập danh mục; bài viết sẽ tự động được gom nhóm
Ngôn ngữ
Vui lòng chọn ngôn ngữ bài viết
Nội dung
ID bài viết
Thời gian cập nhật
Quản lý kho kiến thức
Thêm
Phân bổ đơn hàng
Email người dùng
Vui lòng nhập email người dùng
Vui lòng chọn gói
Vui lòng chọn chu kỳ
Số tiền thanh toán
Vui lòng nhập số tiền cần thanh toán
Gửi email
Người nhận
Lọc người dùng
Tất cả người dùng
Vui lòng nhập chủ đề email
Nội dung gửi
Vui lòng nhập nội dung email
Vui lòng chọn thời gian
Vui lòng chọn ngày
Ngày bắt đầu
Ngày kết thúc
Xác nhận
Lọc
Chọn tất cả trang này
Đảo lựa chọn trang này
Mở rộng hàng
Đóng hàng
Đã hiểu
Vui lòng nhập nội dung tìm kiếm
mục
Đang tải tệp lên
Xóa tệp
Lỗi tải lên
Xem trước tệp
Tải tệp xuống
Không có dữ liệu
Biểu tượng
Sao chép
Mở rộng
Quay lại
Bạn chắc chắn muốn xóa toàn bộ nội dung?
Xóa tất cả
In đậm
In nghiêng
Gạch chân
Gạch ngang
Danh sách không thứ tự
Danh sách có thứ tự
Trích dẫn
Xuống dòng
Mã nội tuyến
Khối mã
Bảng
Hình ảnh
Liên kết
Hoàn tác
Làm lại
Toàn màn hình
Thoát toàn màn hình
Chỉ hiển thị trình soạn thảo
Chỉ hiển thị bản xem trước
Hiển thị trình soạn thảo và bản xem trước
Ký tự thực sự được nhập khi nhấn Tab
Ký tự tab
Dấu cách
`.slice(1, -1).split('\n');

    var part4 = String.raw`
# Mã đơn hàng
127.0.0.1 (khớp đơn)\n10.0.0.0/8 (khớp dải)\ngeoip:cn (khớp danh sách định sẵn)
Máy chủ DNS
Địa chỉ máy chủ DNS
Danh sách máy chủ DNS
Yêu cầu DNS
Nhà cung cấp phân giải DNS
ECH Config (cấu hình máy khách)
ECH Key (khóa riêng máy chủ)
ECH Server Name (tên miền ngụy trang/SNI ngoài)
Ngụy trang HTTP
Yêu cầu HTTP
Phiên bản HYSTERIA
REALITY là bắt buộc và phải khớp với backend
Địa chỉ đích REALITY; mặc định dùng SNI
Cổng đích REALITY; mặc định 443
Dấu vân tay TLS mặc định là Chrome
Thuật toán điều khiển luồng XTLS
Cấu hình outbound Xray
example.com (khớp từ khóa)\ndomain:example.com (khớp tên miền con)\ngeosite:netflix (danh sách tên miền định sẵn)
Thiết lập webhook thành công
✓ Cloudflare quản lý ECH; khóa được quản lý tự động, máy khách lấy cấu hình từ DNS và máy chủ không cần cấu hình
Nhập 0 cho gói một lần
Lệnh cài đặt một chạm
Hoa hồng đã chi tháng trước
Doanh thu tháng trước
Băng thông tải lên
Băng thông tải xuống
Để trống nếu không dùng
Không hỗ trợ
Tỷ lệ giảm giá riêng
Cài đặt giao diện
Cấu hình giao diện
Định dạng: CF_DNS_API_TOKEN=xxxxxxx; phân tách nhiều mục bằng dấu phẩy
Định dạng: cloudflare
Số người
Doanh thu hôm nay
Xếp hạng lưu lượng người dùng hôm nay
Xếp hạng lưu lượng nút hôm nay
Bảng điều khiển
Số tiền ưu đãi
Giao thức truyền tải
Sai định dạng cấu hình giao thức truyền tải
Thanh toán bằng số dư
Trạng thái hoa hồng
Trạng thái hoa hồng
Số tiền hoa hồng
Lưu lượng sử dụng sẽ được nhân với hệ số này
Chứng chỉ tự ký yêu cầu bật Cho phép không an toàn để người dùng có thể kết nối
Lưu thứ tự
Tiêu đề XFF tin cậy (để lấy IP thực)
Hệ số
Cho phép không an toàn
Cho phép không an toàn
Đổi gói đăng ký
Tạo nhóm
Tạo định tuyến
Hành động
Phương thức mã hóa
Khớp
Giá trị khớp
Số lượng khớp
Cấu hình giao thức chi tiết
Bộ lọc giao thức
Mã thẻ quà tặng
Tham khảo
Mã hóa gửi thư:
Máy chủ gửi thư:
Tên người dùng gửi thư:
Cổng gửi thư:
Đang phát hành
Gửi thất bại
Gửi thành công
Thay đổi
Chu kỳ
Mã giao dịch callback
Người dùng trực tuyến
Địa chỉ
Địa chỉ hoặc IP mặc định là 0.0.0.0
Tên miền
Tên miền trong danh sách này sẽ được máy chủ này phân giải trước, mỗi dòng một tên miền
Bộ lọc tên miền
Xem tham khảo
Tăng lưu lượng gói
Tăng thời hạn đăng ký
Tăng số dư tài khoản
 Sao chép
 ngày
ngày
Lý do thất bại:
Gói
Nếu triển khai frontend và backend V2board riêng, cấu hình giao diện sẽ không có hiệu lực. Tìm hiểu thêm
Nhập nếu muốn đổi mật khẩu
Bảo mật
Đăng ký theo thời gian thực
Số tiền phát thực tế
Bật 0-RTT trên máy khách
Đã phát hành
Đã hủy
Đã hoàn tất
Đã bù trừ
Đã thanh toán
Đã dùng tải lên
Đã dùng tải xuống
Đã từ chối
Tiêu đề thường dùng: X-Forwarded-For CF-Connecting-IP X-Real-IP
Bật
Đang kích hoạt
Giao diện hiện tại
Dịch vụ hàng đợi hiện không ổn định và có thể làm gián đoạn hoạt động.
Dùng để xác minh chứng chỉ khi địa chỉ nút không khớp với chứng chỉ
Chờ thanh toán
Chờ xác nhận
Chờ phản hồi
Hoàn tiền lặp lại
Bắt buộc
Đánh dấu là
Sau khi đánh dấu [Đã thanh toán], hệ thống sẽ kích hoạt và hoàn tất đơn hàng
Sau khi đánh dấu [Hợp lệ], hệ thống sẽ phát cho người dùng và hoàn tất đơn hàng
Số tiền bù trừ
Kéo để sắp xếp
Thuật toán điều khiển tắc nghẽn
Phân giải bằng máy chủ DNS được chỉ định
Dùng máy chủ outbound được chỉ định (đích IP)
Dùng máy chủ outbound được chỉ định (đích tên miền)
Chỉ số
Hoa hồng giới thiệu
Tỷ lệ hoàn tiền giới thiệu
Loại hoàn tiền giới thiệu
Tìm kiếm
Được hỗ trợ
Địa chỉ nhận thư:
Giá trị
Chế độ chuyển tiếp gói tin
Tạo thẻ quà tặng
Mua mới
Thời hạn
 Không có người dùng hoặc máy chủ báo cáo bất thường
Không hợp lệ
Mặc định khi không có quy tắc
Không có chứng chỉ (tắt TLS)
Xếp hạng lưu lượng người dùng hôm qua
Xếp hạng lưu lượng nút hôm qua
Nhân viên
Hỗ trợ TLS
Quản trị viên
Hiển thị/ẩn
Thời gian tối đa cho phép
Có
Hợp lệ
Máy chủ
Chỉ báo tên máy chủ (SNI)
Nhóm máy chủ
Băng thông gửi của máy chủ; để trống hoặc nhập 0 để dùng BBR
Băng thông nhận của máy chủ; để trống hoặc nhập 0 để dùng BBR
Chưa thanh toán
 Chưa chạy
Doanh thu tháng này
Người dùng mới tháng này
 phiếu hỗ trợ đang chờ xử lý
 quy tắc
Xem người do người dùng này mời
Gói lưu lượng
Gói đặt lại lưu lượng
Mật khẩu ngụy trang obfsParam
Mật khẩu ngụy trang obfs_password
Phương thức ngụy trang obfs
Thêm thẻ quà tặng
 Thêm đơn hàng
 Thêm định tuyến
Tùy theo tần suất báo cáo của máy chủ
Kích hoạt giao diện
Người dùng
Để trống để dùng giá trị mặc định 100-111-1111.75-0-111.50-0-3333
Để trống để tự động tạo trong /etc/v2node/
Để trống để tự động tạo
Để trống để tự động tạo; hãy thay thế nếu cần mã hóa hậu lượng tử
Đăng xuất
Địa chỉ lắng nghe
Thời hạn thẻ quà tặng
Quản lý thẻ quà tặng
Loại thẻ quà tặng
Chặn truy cập (đích IP)
Chặn truy cập (giao thức)
Chặn truy cập (đích tên miền)
Chặn truy cập (đích cổng)
Tắt SNI
Xử lý ngay
Cổng
 khoản hoa hồng đang chờ xác nhận
Tiếng Trung giản thể
Cài đặt hệ thống
Tên nhóm
Gia hạn
Chỉnh sửa cấu hình TLS
Chỉnh sửa cấu hình mã hóa
Chỉnh sửa cấu hình giao thức
Chỉnh sửa phương án đệm
Chỉnh sửa cấu hình bảo mật
Chỉnh sửa thứ tự
Chỉnh sửa thẻ quà tặng
Chỉnh sửa nhóm
Chỉnh sửa định tuyến
Chỉnh sửa cấu hình
SNI tùy chỉnh
Mã thẻ quà tặng tùy chỉnh
Mã thẻ quà tặng tùy chỉnh (để trống để tạo ngẫu nhiên)
Outbound mặc định tùy chỉnh
Tự ký
Nút
ID nút
Giao thức nút
Thứ tự nút chưa được lưu. Rời trang?
Quản lý nút
Chi tiết đơn hàng
Mã đơn hàng
Chu kỳ đơn hàng
Trạng thái đơn hàng
Trạng thái đơn hàng
Quản lý đơn hàng
Cài đặt
Người dùng này sẽ luôn được hưởng chiết khấu này khi mua bất kỳ gói nào
Đường dẫn tệp khóa công khai của chứng chỉ (Cert File Path)
Chế độ chứng chỉ (Cert Mode)
Đường dẫn tệp khóa riêng của chứng chỉ (Key File Path)
Ghi chú tại đây..
Yêu cầu thất bại
Nhập địa chỉ máy chủ DNS
Nhập tỷ lệ giảm giá riêng
Nhập ghi chú
Nhập tỷ lệ hoàn tiền giới thiệu (để trống để theo cài đặt trang web)
Nhập lưu lượng
Nhập địa chỉ máy chủ DNS dùng để phân giải
Nhập tên thẻ quà tặng
Nhập tên nhóm
Nhập địa chỉ kết nối
Nhập email người mời
Nhập địa chỉ email
Chọn hành động
Tài chính
Trạng thái tài khoản
Bộ định tuyến
Quản lý định tuyến
Nhập từ khóa bất kỳ để tìm kiếm
 Hoạt động bình thường
Địa chỉ kết nối
Số tiền hoàn lại
Chọn chế độ ECH
Chọn thuật toán điều khiển luồng XTLS
Chọn giao thức truyền tải
Chọn phương thức mã hóa
Người mời
Cấu hình
Đặt lại lưu lượng gói
Không NAT dùng cùng cổng kết nối
Hoàn tiền cho lần mua đầu
`.slice(1, -1).split('\n').map(function (value) { return value.replace(/\\n/g, '\n'); });

    var english = Object.create(null);
    var exactEnglish = {
        'All': 'Tất cả', 'Cancel': 'Hủy', 'Copy': 'Sao chép', 'Cut': 'Cắt',
        'Delete': 'Xóa', 'Editor': 'Trình soạn thảo', 'Find': 'Tìm', 'Find all': 'Tìm tất cả',
        'Find next': 'Tìm tiếp', 'Find previous': 'Tìm trước', 'No Data': 'Không có dữ liệu',
        'OK': 'Xác nhận', 'Paste': 'Dán', 'Please select': 'Vui lòng chọn',
        'Preview': 'Xem trước', 'Redo': 'Làm lại', 'Replace': 'Thay thế',
        'Select all': 'Chọn tất cả', 'Select date': 'Chọn ngày', 'Select time': 'Chọn giờ',
        'Undo': 'Hoàn tác', 'Loading...': 'Đang tải...', 'New': 'Mới', 'Enabled': 'Đã bật',
        'Disabled': 'Đã tắt', 'Email': 'Email', 'Password': 'Mật khẩu', 'Username': 'Tên người dùng',
        'Server': 'Máy chủ', 'Port': 'Cổng', 'Protocol': 'Giao thức', 'Status': 'Trạng thái',
        'Action': 'Thao tác', 'Search': 'Tìm kiếm', 'Save': 'Lưu', 'Submit': 'Gửi',
        'Reset': 'Đặt lại', 'Close': 'Đóng', 'Edit': 'Chỉnh sửa', 'Create': 'Tạo',
        'Update': 'Cập nhật', 'Download': 'Tải xuống', 'Upload': 'Tải lên', 'Language': 'Ngôn ngữ'
    };
    Object.keys(exactEnglish).forEach(function (key) { english[key] = exactEnglish[key]; });

    window.V2BoardAdminI18nRegisterChinese('vi-VN', part1.concat(part2, part3, part4), english);
})(window);
