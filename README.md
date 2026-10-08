# MovieTicket v2

Hệ thống đặt vé xem phim Laravel 13 + SQLite/MySQL.

## Chức năng v2
- Đăng ký/đăng nhập/đăng xuất, quên mật khẩu, hồ sơ.
- Tìm kiếm và lọc phim.
- Chọn ghế và kiểm tra ghế trùng.
- Thanh toán giả lập: QR / thẻ / tại quầy.
- Vé điện tử có mã QR và mã booking.
- Combo bắp nước: Solo, Couple, Family.
- Voucher: MOVIE10, WELCOME30K.
- Đánh giá phim 1-5 sao sau khi đã mua vé và suất chiếu đã qua.
- Lịch sử vé, hủy vé khi đủ điều kiện.
- Admin quản lý phim, suất chiếu, người dùng, booking.
- Dashboard doanh thu, giao dịch, combo, voucher, đánh giá và biểu đồ.
- 10 phim demo, 2 rạp, 4 phòng, 200 ghế, 18 suất chiếu.

## Tài khoản demo
Admin: admin@gmail.com / 12345678
Khách hàng: customer@gmail.com / 12345678

## Voucher demo
- MOVIE10: giảm 10%, đơn tối thiểu 100.000đ, tối đa 30.000đ.
- WELCOME30K: giảm 30.000đ, đơn tối thiểu 150.000đ.

## Chạy project
```bash
composer install
npm install
npm run build
php artisan serve
```
Mở http://127.0.0.1:8000

Nếu dùng MySQL/XAMPP, tham khảo `.env.mysql.example`. Bản ZIP đã có sẵn SQLite `database/database.sqlite`.
