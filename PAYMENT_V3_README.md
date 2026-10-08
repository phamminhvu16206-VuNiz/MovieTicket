# MovieTicket Payment V3

## Luồng thanh toán chính

1. Người dùng chọn ghế -> booking được tạo với `pending`.
2. Trang `/payment/{bookingId}` hiển thị checkout V3.
3. Chọn **Chuyển khoản QR / SePay** và bấm tạo QR.
4. `Payment` được tạo với `status = pending`.
5. QR dùng số tiền cuối cùng và `booking_code` làm nội dung chuyển khoản.
6. SePay gọi `POST /sepay/webhook`.
7. Webhook kiểm tra API key, tìm booking bằng mã `MVTXXXXXXXX`, kiểm tra số tiền.
8. Nếu hợp lệ: `payments.status = success`, `paid_at` được ghi, `bookings.status = paid`.
9. Frontend polling `/payment/{bookingId}/status` và tự chuyển sang trang vé.

## Cấu hình .env

Thêm:

```env
SEPAY_ENABLED=false
SEPAY_API_KEY=
SEPAY_BANK_ACCOUNT=
SEPAY_BANK_NAME=VCB
SEPAY_ACCOUNT_NAME=
```

Đổi `SEPAY_ENABLED=true` khi đã cấu hình SePay.

## Lưu ý webhook

URL webhook:

```text
POST /sepay/webhook
```

SePay cần gửi header:

```text
Authorization: Apikey YOUR_SEPAY_API_KEY
```

Nội dung chuyển khoản cần chứa mã booking, ví dụ:

```text
MVTAB12CD34
```

Mã booking thực tế được sinh tự động theo format `MVT` + 8 ký tự.

## Những gì đã loại bỏ

- Webhook SePay trùng trong `BookingController`.
- Logic tìm `BOOKING{id}` không đồng nhất.
- Logic tìm mã `MT-XXXXXXXX` không khớp với booking hiện tại.
- Polling route cũ bị lệch thứ tự tham số.
- Payment QR tự động đánh dấu `paid` trước khi webhook xác nhận.

## Payment mô phỏng

`qr`, `card`, `cash` vẫn được giữ để demo đồ án. Chỉ `sepay_qr` yêu cầu webhook thật.
