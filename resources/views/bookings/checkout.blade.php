@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <!-- Tiêu đề trang -->
            <div class="col-12 text-center mb-4">
                <h2 class="fw-bold text-uppercase">Thanh Toán Đặt Vé</h2>
                <p class="text-muted">Vui lòng quét mã QR bên dưới để hoàn tất thanh toán giữ chỗ</p>
            </div>

            <!-- Thông tin đơn hàng (Cột bên trái) -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="bi bi-ticket-perforated me-2"></i>Thông Tin Đặt Vé
                            #{{ $booking->booking_code ?? $booking->id }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <h4 class="text-primary fw-bold mb-3">{{ $booking->showtime->movie->title }}</h4>

                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span class="text-muted">Rạp chiếu:</span>
                                <span
                                    class="fw-semibold">{{ $booking->showtime->room->cinema->name ?? 'MovieTicket Cinema' }}</span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span class="text-muted">Phòng chiếu:</span>
                                <span class="fw-semibold">{{ $booking->showtime->room->name }}</span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span class="text-muted">Suất chiếu:</span>
                                <span class="fw-semibold text-danger">
                                    {{ \Carbon\Carbon::parse($booking->showtime->start_time)->format('H:i - d/m/Y') }}
                                </span>
                            </li>
                            <li class="list-group-item px-0">
                                <span class="text-muted d-block mb-2">Ghế đã chọn:</span>
                                <div>
                                    @forelse($booking->bookingSeats as $bSeat)
                                        <span class="badge bg-primary fs-6 me-1 mb-1">{{ $bSeat->seat->seat_number }}</span>
                                    @empty
                                        @foreach($booking->seats as $bSeat)
                                            <span class="badge bg-primary fs-6 me-1 mb-1">{{ $bSeat->seat->seat_number }}</span>
                                        @endforeach
                                    @endforelse
                                </div>
                            </li>
                        </ul>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="fs-5 fw-bold">Tổng thanh toán:</span>
                            <span class="fs-4 fw-bold text-danger">
                                {{ number_format($booking->total_price ?? $booking->total_amount, 0, ',', '.') }} VNĐ
                            </span>
                        </div>

                        <!-- Countdown giữ vé -->
                        <div class="alert alert-warning mt-4 mb-0 d-flex align-items-center" role="alert">
                            <i class="bi bi-clock-history fs-4 me-2"></i>
                            <div>
                                <small class="d-block">Thời gian giữ ghế còn lại:</small>
                                <span id="countdown" class="fw-bold fs-5 text-dark">10:00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Khung quét mã VietQR SePAY (Cột bên phải) -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 text-center p-4 h-100">
                    <h5 class="fw-bold mb-3 text-uppercase text-secondary">
                        <i class="bi bi-qr-code-scan me-2"></i>Quét Mã VietQR Qua SePAY
                    </h5>

                    @php
                        // Cấu hình thông tin tài khoản ngân hàng nhận tiền
                        $bank = "MBBank"; // Mã viết tắt ngân hàng (MBBank, Vietcombank, ACB, TPBank...)
                        $acc = "0339936910"; // Số tài khoản ngân hàng của bạn
                        $accountName = "PHAM MINH VU"; // Tên chủ tài khoản
                        $amount = (int) ($booking->total_price ?? $booking->total_amount ?? 0);
                        $des = $booking->booking_code ?? ("BOOKING" . $booking->id); // Nội dung chuyển khoản

                        // Link tạo mã QR tự động chuẩn SePAY / VietQR
                        $qrUrl = "https://qr.sepay.vn/img?bank={$bank}&acc={$acc}&template=compact&amount={$amount}&des={$des}";
                    @endphp

                    <!-- Ảnh QR SePAY -->
                    <div class="mb-3 d-flex justify-content-center">
                        <div class="position-relative p-2 bg-light rounded border shadow-sm" style="max-width: 270px;">
                            <img src="{{ $qrUrl }}" alt="Mã VietQR SePAY" class="img-fluid rounded">
                        </div>
                    </div>

                    <!-- Bảng chi tiết chuyển khoản -->
                    <div class="alert alert-info py-3 px-3 text-start small mb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted d-block">Ngân hàng:</span>
                                <strong class="text-dark">{{ $bank }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Chủ tài khoản:</span>
                                <strong class="text-dark">{{ $accountName }}</strong>
                            </div>
                            <div class="col-12">
                                <hr class="my-1">
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Số tài khoản:</span>
                                <strong class="text-danger fs-6">{{ $acc }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Số tiền:</span>
                                <strong class="text-danger fs-6">{{ number_format($amount, 0, ',', '.') }} VNĐ</strong>
                            </div>
                            <div class="col-12">
                                <hr class="my-1">
                            </div>
                            <div class="col-12">
                                <span class="text-muted d-block">Nội dung chuyển khoản:</span>
                                <div class="d-flex align-items-center mt-1">
                                    <span class="badge bg-warning text-dark fs-6 me-2" id="copy-content">{{ $des }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2"
                                        onclick="copyContent()">
                                        <i class="bi bi-clipboard"></i> Sao chép
                                    </button>
                                </div>
                                <small class="text-muted fst-italic mt-1 d-block">* Vui lòng giữ nguyên nội dung này để hệ
                                    thống duyệt tự động.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Lời nhắn chờ duyệt -->
                    <div class="d-flex align-items-center justify-content-center text-primary fs-6 mt-auto">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        <span>Hệ thống đang chờ giao dịch chuyển khoản...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts xử lý tự động -->
    <script>
        // 1. Sao chép nội dung chuyển khoản
        function copyContent() {
            const text = document.getElementById('copy-content').innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('Đã sao chép nội dung chuyển khoản: ' + text);
            });
        }

        // 2. Polling kiểm tra trạng thái thanh toán từ Webhook SePAY (3 giây/lần)
        const bookingId = "{{ $booking->id }}";
        const checkStatusInterval = setInterval(function () {
            fetch(`/booking/${bookingId}/check-status`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'paid') {
                        clearInterval(checkStatusInterval);
                        window.location.href = `/booking/${bookingId}/success`;
                    }
                })
                .catch(error => console.error('Lỗi kiểm tra trạng thái:', error));
        }, 3000);

        // 3. Đếm ngược thời gian thanh toán (10 phút)
        let timeLeft = 600; // 10 phút tính bằng giây
        const countdownEl = document.getElementById('countdown');

        const timer = setInterval(function () {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;

            seconds = seconds < 10 ? '0' + seconds : seconds;
            minutes = minutes < 10 ? '0' + minutes : minutes;

            countdownEl.innerHTML = `${minutes}:${seconds}`;

            if (timeLeft <= 0) {
                clearInterval(timer);
                clearInterval(checkStatusInterval);
                alert('Hết thời gian thanh toán! Đơn hàng của bạn đã bị hủy.');
                window.location.href = "{{ route('home') }}";
            }
            timeLeft--;
        }, 1000);
    </script>
@endsection