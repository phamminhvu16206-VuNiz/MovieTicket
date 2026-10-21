@extends('layouts.app')

@section('title', 'Vé Điện Tử - MovieTicket')

@section('content')
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="card booking-success-card">
                    <div class="booking-success-heading">
                        <div class="booking-success-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <h2>ĐẶT VÉ THÀNH CÔNG!</h2>
                        <p>Cảm ơn bạn đã đặt vé tại MovieTicket. Vui lòng xuất trình mã QR bên dưới khi vào rạp.</p>
                    </div>

                    <div class="booking-qr-panel">
                        <h5>MÃ VÉ ĐIỆN TỬ (E-TICKET)</h5>

                        @php
                            $qrTicketText = 'MOVIETICKET|' . $booking->booking_code . '|' . \Illuminate\Support\Str::ascii($booking->showtime->movie->title);
                        @endphp

                        <div class="booking-qr-code">
                            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)->generate($qrTicketText) !!}
                        </div>

                        <p class="booking-ticket-code">
                            Mã vé: <span>{{ $booking->booking_code }}</span>
                        </p>
                        <small><i class="bi bi-qr-code-scan me-1"></i>Quét mã này tại quầy soát vé để nhận vé xem phim</small>
                    </div>

                    <div class="booking-ticket-details">
                        <h5>THÔNG TIN CHI TIẾT</h5>
                        <p><strong>Tên phim:</strong> <span>{{ $booking->showtime->movie->title }}</span></p>
                        <p><strong>Rạp / Phòng:</strong> <span>{{ $booking->showtime->room->cinema->name ?? 'MovieTicket' }} ({{ $booking->showtime->room->name }})</span></p>
                        <p><strong>Suất chiếu:</strong> <span class="booking-ticket-highlight">{{ date('H:i - d/m/Y', strtotime($booking->showtime->start_time)) }}</span></p>
                        <p><strong>Ghế đã đặt:</strong> <span class="booking-ticket-highlight">{{ $booking->bookingSeats->map(fn($bs) => $bs->seat->seat_number)->join(', ') }}</span></p>
                        <p><strong>Thanh toán:</strong> <span class="booking-payment-badge">{{ $booking->payment?->method === 'sepay_qr' ? 'SePay QR' : ($booking->payment?->method ?? 'Đã xác nhận') }}</span></p>
                        <p><strong>Mã giao dịch:</strong> <span>{{ $booking->payment?->transaction_ref ?? 'Đã xác nhận' }}</span></p>
                        <p class="booking-ticket-total"><strong>Tổng thanh toán:</strong> <span>{{ number_format($booking->total_amount) }} VNĐ</span></p>
                    </div>

                    <div class="booking-success-actions">
                        <a href="{{ route('profile.bookings') }}" class="btn booking-action-primary">
                            <i class="bi bi-ticket-perforated-fill me-1"></i> XEM LỊCH SỬ ĐẶT VÉ
                        </a>
                        <a href="{{ route('home') }}" class="btn booking-action-home">
                            <i class="bi bi-house-door me-1"></i> VỀ TRANG CHỦ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection