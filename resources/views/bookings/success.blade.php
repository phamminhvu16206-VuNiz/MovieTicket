@extends('layouts.app')

@section('title', 'Vé Điện Tử - MovieTicket')

@section('content')
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card p-4 shadow-lg border-success border-2 bg-dark text-white">
                    <!-- Thông báo -->
                    <div class="text-center mb-3">
                        <div class="text-success display-1 mb-2">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <h2 class="text-success fw-bold mb-1">ĐẶT VÉ THÀNH CÔNG!</h2>
                        <p class="text-muted">Cảm ơn bạn đã đặt vé tại MovieTicket. Vui lòng xuất trình mã QR bên dưới khi
                            vào rạp.</p>
                    </div>

                    <!-- Khung Mã QR Vé Điện Tử -->
                    <div class="card bg-white text-dark p-4 rounded text-center my-3 shadow-sm align-items-center">
                        <h5 class="fw-bold text-uppercase text-secondary mb-3">MÃ VÉ ĐIỆN TỬ (E-TICKET)</h5>

                        <!-- Hiển thị Mã QR -->
                        <div class="p-3 bg-light rounded border d-inline-block">
                            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)->generate('MOVIETICKET|'.$booking->booking_code.'|'.$booking->showtime->movie->title) !!}
                        </div>

                        <p class="mt-3 mb-0 fw-bold fs-5 text-primary">
                            Mã vé: <span class="badge bg-warning text-dark fs-6">{{ $booking->booking_code }}</span>
                        </p>
                        <small class="text-muted mt-1"><i class="bi bi-qr-code-scan me-1"></i>Quét mã này tại quầy soát vé
                            để nhận vé xem phim</small>
                    </div>

                    <!-- Chi Tiết Đơn Hàng -->
                    <div class="p-3 rounded text-start my-3 border border-secondary bg-secondary bg-opacity-25">
                        <h5 class="text-warning fw-bold mb-3 border-bottom border-secondary pb-2">THÔNG TIN CHI TIẾT</h5>
                        <p class="mb-2"><strong>Tên phim:</strong> <span
                                class="text-light">{{ $booking->showtime->movie->title }}</span></p>
                        <p class="mb-2"><strong>Rạp / Phòng:</strong> <span
                                class="text-light">{{ $booking->showtime->room->cinema->name ?? 'MovieTicket' }}
                                ({{ $booking->showtime->room->name }})</span></p>
                        <p class="mb-2"><strong>Suất chiếu:</strong> <span
                                class="text-warning fw-bold">{{ date('H:i - d/m/Y', strtotime($booking->showtime->start_time)) }}</span>
                        </p>
                        <p class="mb-2"><strong>Ghế đã đặt:</strong>
                            <span class="text-warning fw-bold">
                                {{ $booking->bookingSeats->map(fn($bs) => $bs->seat->seat_number)->join(', ') }}
                            </span>
                        </p>
                        <p class="mb-2"><strong>Thanh toán:</strong> <span class="badge bg-success">{{ $booking->payment->method === 'sepay_qr' ? 'SePay QR' : ($booking->payment->method ?? 'Đã xác nhận') }}</span></p>
                        <p class="mb-2"><strong>Mã giao dịch:</strong> <span class="text-light">{{ $booking->payment->transaction_ref ?? "Đã xác nhận" }}</span></p>
                        <p class="mb-0"><strong>Tổng thanh toán:</strong> <span
                                class="text-success fw-bold fs-5">{{ number_format($booking->total_amount) }} VNĐ</span></p>
                    </div>

                    <!-- Nút thao tác -->
                    <div class="text-center mt-3">
                        <a href="{{ route('profile.bookings') }}" class="btn btn-primary fw-bold px-4 py-2 me-2">
                            <i class="bi bi-ticket-perforated-fill me-1"></i> XEM LỊCH SỬ ĐẶT VÉ
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline-light fw-bold px-4 py-2">
                            VỀ TRANG CHỦ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection