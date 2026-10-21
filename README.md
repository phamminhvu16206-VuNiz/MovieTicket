# MovieTicket

MovieTicket là ứng dụng đặt vé xem phim trực tuyến, xây dựng bằng Laravel 13 và PHP 8.3. Ứng dụng hỗ trợ khách hàng khám phá phim, chọn ghế, thanh toán và quản lý vé; quản trị viên quản lý nội dung, lịch chiếu và yêu cầu hoàn tiền.

## Chức năng

### Khách hàng

- Đăng ký, đăng nhập, đăng xuất, quên mật khẩu và đặt lại mật khẩu.
- Xem, cập nhật hồ sơ cá nhân và đổi mật khẩu.
- Duyệt phim đang chiếu và sắp chiếu; tìm kiếm theo tên phim, thể loại, diễn viên hoặc đạo diễn.
- Lọc phim theo thể loại, xem thông tin chi tiết, trailer, đánh giá và lịch chiếu.
- Phân trang riêng cho danh sách phim đang chiếu và sắp chiếu, mỗi trang tối đa 4 phim.
- Dùng tính năng gợi ý “Tối nay xem gì?” với bộ lọc tâm trạng, thời lượng, ngân sách vé, thời gian xem và lựa chọn ngẫu nhiên.
- Thêm hoặc bỏ phim trong danh sách “Muốn xem”.
- Nhận lời nhắc trong ứng dụng khi phim muốn xem sắp chiếu, có suất mới hoặc lịch chiếu sắp kết thúc.
- Chọn suất chiếu và tối đa 10 ghế; ghế được giữ trong 3 phút để hoàn tất thanh toán.
- Thanh toán tại quầy bằng tiền mặt hoặc bằng SePay QR khi tích hợp được cấu hình.
- Chọn combo bắp nước và áp dụng voucher hợp lệ trong quá trình thanh toán.
- Theo dõi trạng thái thanh toán, xem lịch sử đặt vé và vé điện tử có mã QR.
- Nhận email vé sau khi thanh toán thành công; nhận email khi phim muốn xem có suất chiếu mới và trước suất chiếu đã đặt 24 giờ, 3 giờ.
- Hủy vé đã thanh toán hoặc gửi yêu cầu hoàn tiền khi còn hơn 24 giờ trước giờ chiếu. Yêu cầu hoàn tiền gồm lý do và thông tin ngân hàng; trạng thái được theo dõi trong lịch sử vé.
- Đánh giá phim đã đặt và đã xem.

### Quản trị viên

- Xem dashboard gồm thống kê phim, vé, doanh thu, đánh giá, combo/voucher và biểu đồ doanh thu, vé theo phim.
- Thêm, sửa, xóa phim và quản lý suất chiếu, giá vé, trạng thái lịch chiếu.
- Ngăn tạo suất chiếu trùng thời gian trong cùng phòng.
- Xem danh sách người dùng và khóa/mở khóa tài khoản khách hàng.
- Xem đặt vé và cập nhật trạng thái đơn.
- Quản lý yêu cầu hoàn tiền: xem thông tin do khách gửi, chuyển khoản thủ công và xác nhận đã hoàn tiền.

## Trạng thái đơn và hoàn tiền

- Đơn chưa thanh toán chỉ giữ ghế tối đa 3 phút. Khi hết hạn hoặc khách hủy, đơn chưa thanh toán và các dữ liệu liên quan sẽ được dọn.
- Đơn đã thanh toán được giữ trong lịch sử kể cả khi khách hủy vé hoặc yêu cầu hoàn tiền.
- Khách chỉ được hủy hoặc yêu cầu hoàn tiền khi còn hơn 24 giờ trước giờ chiếu.
- Sau khi khách gửi yêu cầu hoàn tiền, vé chuyển sang chờ xác nhận. Admin chuyển khoản thủ công rồi xác nhận; khi đó vé hiển thị đã hủy và hoàn tiền.

## Tích hợp email

Email được gửi qua queue. Cấu hình SMTP trong `.env` để gửi tới hộp thư thật; cấu hình mặc định `MAIL_MAILER=log` chỉ ghi nội dung email vào log ứng dụng.

Ví dụ cấu hình Gmail:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME="your-address@gmail.com"
MAIL_PASSWORD="your-google-app-password"
MAIL_FROM_ADDRESS="your-address@gmail.com"
MAIL_FROM_NAME="MovieTicket"
QUEUE_CONNECTION=database
```

Với Gmail, hãy bật xác minh 2 bước và tạo App Password. Không dùng mật khẩu tài khoản Gmail thường. Không chia sẻ App Password hoặc commit `.env` vào Git.

Để xử lý email đang chờ, chạy worker:

```powershell
php artisan queue:work database --tries=3
```

## Tích hợp thanh toán SePay QR (tùy chọn)

SePay chỉ xuất hiện khi bật tích hợp và cấu hình đầy đủ. Thêm thông tin vào `.env`:

```dotenv
SEPAY_ENABLED=true
SEPAY_API_KEY=your-sepay-webhook-api-key
SEPAY_BANK_ACCOUNT=your-bank-account
SEPAY_BANK_NAME=VCB
SEPAY_ACCOUNT_NAME="YOUR ACCOUNT NAME"
```

Chạy `php artisan config:clear` sau khi cập nhật cấu hình. Trong SePay, cấu hình webhook trỏ đến `https://your-public-domain/sepay/webhook` và dùng cùng API key ở header `Authorization: Apikey <key>`. Webhook phải truy cập công khai qua HTTPS; `localhost` và `127.0.0.1` không nhận được webhook từ SePay. Hệ thống chỉ xác nhận thanh toán khi webhook hợp lệ có đúng mã đơn và số tiền.

## Lịch tự động

Laravel Scheduler thực hiện các việc sau:

- Mỗi phút dọn đơn chờ thanh toán hết hạn.
- Hằng ngày lúc 09:00 gửi lời nhắc trong ứng dụng về phim sắp khởi chiếu và lịch chiếu sắp kết thúc.
- Mỗi 15 phút kiểm tra và gửi lời nhắc trước suất chiếu 24 giờ, 3 giờ.

Trên Windows/Laragon, chạy scheduler trong một terminal riêng:

```powershell
php artisan schedule:work
```

Trên máy chủ Linux, cấu hình cron chạy `php artisan schedule:run` mỗi phút.

## Yêu cầu

- PHP 8.3 trở lên và các PHP extensions Laravel yêu cầu (bao gồm GD để tạo mã QR).
- Composer 2.x.
- Node.js 20.19+ hoặc 22.12+.
- MySQL chạy qua Laragon hoặc một dịch vụ MySQL tương thích.

## Cơ sở dữ liệu và dữ liệu mẫu

Tạo database `movieticket_db`, sau đó chạy:

```powershell
php artisan migrate --seed
```

Seeder tạo tài khoản demo, phim, rạp, phòng, ghế, suất chiếu, combo và voucher. Có thể chạy riêng seeder bổ sung phim bằng:

```powershell
php artisan db:seed --class=AdditionalMoviesSeeder
```

Tài khoản demo:

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@gmail.com` | `12345678` |
| Khách hàng | `customer@gmail.com` | `12345678` |

Chỉ dùng tài khoản demo cho môi trường phát triển; đổi mật khẩu trước khi triển khai. Có thể dùng `MovieTicket_database.sql` thay cho migrate để nhập database có sẵn, nhưng không import SQL rồi migrate trên cùng database vì sẽ trùng bảng.

## Kiểm thử

Chạy toàn bộ test bằng:

```powershell
php artisan test
```

## Hướng dẫn chạy project trên Windows/Laragon

### Cài đặt lần đầu

1. Mở Laragon và bấm **Start All** để khởi động MySQL.
2. Mở Terminal của Laragon, chuyển đến thư mục dự án (thư mục chứa file `artisan`), ví dụ:

   ```powershell
   cd C:\Users\aaa\Desktop\LT_PHP\laragon\www\MovieTicket
   ```

3. Tạo database tên `movieticket_db` bằng phpMyAdmin hoặc công cụ quản lý MySQL.
4. Cài dependencies và tạo file cấu hình môi trường:

   ```powershell
   composer install
   npm install
   Copy-Item .env.mysql.example .env
   ```

5. Mở `.env` và kiểm tra `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Đặt SMTP nếu cần nhận email thật và không chia sẻ thông tin bí mật.
6. Tạo application key, tạo schema và dữ liệu mẫu, rồi build giao diện:

   ```powershell
   php artisan key:generate
   php artisan config:clear
   php artisan migrate --seed
   npm run build
   ```

### Khởi chạy

Mở terminal tại thư mục dự án cho mỗi tiến trình cần chạy:

1. Khởi động website:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8000
   ```

   Mở `http://127.0.0.1:8000`.

2. Để gửi email vé và email nhắc lịch, cấu hình SMTP trong `.env`, sau đó chạy queue worker:

   ```powershell
   php artisan queue:work database --tries=3
   ```

3. Để tự động chạy các lời nhắc và dọn đơn hết hạn, chạy scheduler:

   ```powershell
   php artisan schedule:work
   ```

4. Khi đang phát triển giao diện, có thể dùng `npm run dev` trong một terminal thay cho việc build lại sau mỗi thay đổi. Khi chỉ chạy ứng dụng đã build, bước `npm run build` ở phần cài đặt là đủ.

Queue worker và scheduler phải tiếp tục chạy trong terminal. Nếu thay đổi `.env`, chạy lại `php artisan config:clear`; nếu worker đã khởi động trước khi đổi cấu hình, khởi động lại worker để nó nạp cấu hình mới.
