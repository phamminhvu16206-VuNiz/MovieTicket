# MovieTicket V3 - Payment Merge

Đã hợp nhất Payment V3 vào project MovieTicket_v2.

## Đã cập nhật
- SePay QR là luồng thanh toán thật chính.
- Webhook SePay duy nhất tại `/sepay/webhook`.
- Booking code thống nhất `MVTXXXXXXXX`.
- Payment tạo `pending`, chỉ webhook mới chuyển sang `success/paid`.
- Polling trạng thái mỗi 3 giây.
- Checkout UI mới, responsive.
- Giữ `qr`, `card`, `cash` làm payment mô phỏng.
- Voucher + combo được tính lại trong transaction.
- Payment status và transaction reference hiển thị trên UI.
- Xóa các logic webhook/route SePay trùng và không tương thích.
- `.env` chứa credential đã được loại khỏi bản ZIP; dùng `.env.example`.

## Cấu hình
Copy `.env.example` thành `.env`, chạy `php artisan key:generate`, sau đó cấu hình:

SEPAY_ENABLED=true
SEPAY_API_KEY=...
SEPAY_BANK_ACCOUNT=...
SEPAY_BANK_NAME=VCB
SEPAY_ACCOUNT_NAME=...

Webhook SePay:
`POST /sepay/webhook`
