@extends('layouts.app')

@section('title', 'Lịch Sử Đặt Vé - MovieTicket')

@section('content')
    <div class="container my-5 text-white">
        <h3 class="fw-bold text-warning mb-4"><i class="bi bi-clock-history me-2"></i>LỊCH SỬ ĐẶT VÉ CỦA BẠN</h3>

        @forelse($bookings as $booking)
            <div class="card bg-dark text-white border-secondary mb-4 shadow">
                <div class="card-header bg-secondary bg-opacity-25 d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Mã Đơn Hàng:</strong> <span
                            class="badge bg-warning text-dark fs-6">{{ $booking->booking_code }}</span>
                        <span class="ms-3 text-muted">Ngày đặt: {{ $booking->created_at->format('H:i - d/m/Y') }}</span>
                    </div>
                    <div>
                        <span class="badge {{ $booking->status === 'cancelled' ? 'bg-danger' : ($booking->status === 'completed' ? 'bg-primary' : 'bg-success') }} fs-6">{{ ucfirst($booking->status) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <!-- Khung QR Code thu nhỏ -->
                        <div class="col-md-3 text-center mb-3 mb-md-0 border-end border-secondary">
                            <div class="p-2 bg-white rounded d-inline-block">
                                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(110)->generate($booking->booking_code) !!}
                            </div>
                            <div class="small text-muted mt-1">Mã QR Vé Điện Tử</div>
                        </div>

                        <!-- Thông tin vé -->
                        <div class="col-md-6">
                            <h5 class="fw-bold text-warning">{{ $booking->showtime->movie->title ?? 'N/A' }}</h5>
                            <p class="mb-1"><i class="bi bi-geo-alt me-1"></i>Rạp:
                                {{ $booking->showtime->room->cinema->name ?? 'MovieTicket' }} -
                                {{ $booking->showtime->room->name ?? '' }}
                            </p>
                            <p class="mb-1"><i class="bi bi-calendar-event me-1"></i>Suất chiếu: <strong
                                    class="text-info">{{ date('H:i - d/m/Y', strtotime($booking->showtime->start_time)) }}</strong>
                            </p>
                            <p class="mb-0"><i class="bi bi-person-badge me-1"></i>Ghế:
                                <span class="fw-bold text-danger">
                                    {{ $booking->bookingSeats->map(fn($bs) => $bs->seat->seat_number)->join(', ') }}
                                </span>
                            </p>
                        </div>

                        <!-- Giá tiền & Chi tiết -->
                        <div class="col-md-3 text-md-end text-start border-start border-secondary pt-3 pt-md-0">
                            <div class="text-muted small">Tổng tiền:</div>
                            <div class="fs-4 fw-bold text-success mb-2">{{ number_format($booking->total_amount) }} VNĐ</div>
                            @if($booking->status === 'pending')<a href="{{ route('payment.checkout', $booking->id) }}" class="btn btn-warning btn-sm me-1"><i class="bi bi-credit-card me-1"></i>Thanh toán</a>@endif<a href="{{ route('booking.success', $booking->id) }}" class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-qr-code me-1"></i> Xem Chi Tiết Vé
                            </a>
                            @if(in_array($booking->status,['paid','pending']) && $booking->showtime->start_time->isFuture())<form method="POST" action="{{ route('booking.cancel',$booking->id) }}" class="d-inline">@csrf<button class="btn btn-outline-danger btn-sm" onclick="return confirm('Bạn chắc chắn muốn hủy vé?')">Hủy vé</button></form>@endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-secondary text-center py-5">
                <i class="bi bi-ticket-detailed display-4 d-block mb-3"></i>
                <h5>Bạn chưa có đơn đặt vé nào!</h5>
                <a href="{{ route('home') }}" class="btn btn-warning mt-3 fw-bold">ĐẶT VÉ NGAY</a>
            </div>
        @endforelse

        <div class="d-flex justify-content-center mt-4">
            {{ $bookings->links() }}
        </div>
    </div>
@endsection