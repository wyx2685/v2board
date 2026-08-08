# MosVPN Development Roadmap

## Nguyên tắc nguồn

ZicBoardV3-public được dùng để nghiên cứu hành vi sản phẩm và các tình huống lỗi.
Phần bổ sung của ZicBoard chịu giấy phép thương mại, vì vậy MosVPN không
cherry-pick, sao chép bundle giao diện, mã protected core, định dạng ZicVPN hay
cơ chế license của họ. Mọi tính năng dưới đây phải được thiết kế và kiểm thử độc
lập trên nền V2Board đang có.

Nguồn tham khảo:

- https://github.com/kutycma/ZicBoardV3-public
- https://github.com/kutycma/ZicBoardV3-public/blob/master/LICENSE

## Nền tảng đã hoàn thành

- Tiếng Việt mặc định; hỗ trợ Việt, Nga, Anh và Trung cho user, admin, API,
  email, Telegram, CLI và profile subscription.
- Gói one-time thay thế quota cũ, không cộng nhầm lưu lượng chưa dùng.
- Bộ lọc quản trị chuẩn hóa toán tử cũ, escape wildcard SQL và lọc email bằng
  subquery.
- Hoa hồng chỉ trả cho đơn hoàn tất, khóa dòng chống trả lặp, vô hiệu khi hủy và
  scheduler không chạy chồng.
- Quyền Staff chỉ truy cập khách trực tiếp do Staff giới thiệu; không được sửa
  admin/Staff khác, tài chính hoặc gói dịch vụ; API không trả token hay thông tin
  mật khẩu.
- Phiên đăng nhập luôn kiểm tra trạng thái role/banned mới nhất; thu hồi session
  có hiệu lực ngay.
- Callback thanh toán ràng buộc đúng payment record, chống dùng lại callback,
  chuyển trạng thái nguyên tử, so khớp số tiền/tiền tệ khi gateway cung cấp và
  bật xác minh TLS.
- CORS dùng allowlist qua `CORS_ALLOWED_ORIGINS`, đồng thời luôn cho phép origin
  cùng site.

## P0 — trước khi đưa production

1. Thay `update.sh` bằng pipeline fail-fast:
   - bắt buộc worktree sạch và `git pull --ff-only`;
   - track `composer.lock`, dùng `composer install`, không tải Composer latest;
   - backup, health check và rollback ứng dụng;
   - không `reset --hard` từ remote, không `chown` toàn repo.
2. Thay `database/update.sql` bằng migration có version, idempotent và báo lỗi
   thật; hỗ trợ `--dry-run` và preflight.
3. Viết fixture callback cho từng gateway; lưu số tiền/tiền tệ dự kiến của giao
   dịch Stripe tại thời điểm tạo payment để webhook cũng đối chiếu được.
4. Thêm rate limit cho đăng nhập, webhook, gửi mail và thao tác Staff.
5. Thêm audit log gồm actor, target, action, kết quả và diff đã che token, mật
   khẩu, khóa node và cấu hình gateway.

## P1 — nền tảng ứng dụng MosVPN

1. Giữ `/api/v1` để tương thích; tạo API riêng `/api/mosvpn/v1`.
2. Tách subscription thành nguồn dữ liệu chuẩn:
   - một user có nhiều subscription;
   - `primary_subscription_id` rõ ràng;
   - order, traffic và device tham chiếu `subscription_id`;
   - migration có đối soát và rollback.
3. Quản lý vòng đời thiết bị:
   - đăng ký, đặt tên, hoạt động gần nhất, traffic, thu hồi và cấm;
   - token ngẫu nhiên lưu dạng hash cho từng thiết bị;
   - request có nonce/timestamp và chữ ký, không tin HWID hay User-Agent.
4. Profile riêng cho MosVPN:
   - envelope có `version`, `key_id`, `issued_at`, `expires_at`;
   - chữ ký server và mã hóa bằng khóa thiết bị;
   - hỗ trợ xoay khóa và thu hồi;
   - không dùng lại định dạng hoặc khóa dẫn xuất của ZicVPN.

## P2 — vận hành node

- Theo dõi online theo node và load IP, chuẩn hóa IPv4/IPv6, coi dữ liệu quá 5
  phút là stale.
- Cảnh báo node/load IP có throttle và chống gửi Telegram lặp.
- Dashboard traffic, online, doanh thu và hoa hồng theo chuỗi thời gian.
- Chuẩn hóa cấu hình TLS/SNI và proxy group cho Clash, Mihomo, Stash, Sing-box;
  mỗi client có fixture/contract test riêng.
- ETag cho node config và health/doctor command không phụ thuộc core thương mại.

## P3 — nghiệp vụ mở rộng

- RBAC theo capability cho Support, Staff, Manager và Admin.
- Billing/VAT tùy chọn: tên, mã số thuế, điện thoại, địa chỉ.
- White-label/domain mapping nếu MosVPN triển khai đại lý.
- Công cụ migrate có preflight, dry-run, báo orphan và summary sau migrate.

## Tiêu chuẩn hoàn thành

Mỗi nhóm tính năng phải có test quyền truy cập, test dữ liệu biên, test replay
hoặc idempotency nếu có side effect, lint PHP và kiểm tra cú pháp PHP 7.3. Không
đưa compiled bundle hoặc mã thương mại từ ZicBoard vào repository MosVPN.
