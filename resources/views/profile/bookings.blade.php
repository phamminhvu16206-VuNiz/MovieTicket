@extends('layouts.app')

@section('title', 'Lịch Sử Đặt Vé - MovieTicket')

@section('content')
    <div class="container my-5 text-white">
        <h3 class="fw-bold text-warning mb-4"><i class="bi bi-clock-history me-2"></i>LỊCH SỬ ĐẶT VÉ CỦA BẠN</h3>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @forelse($bookings as $booking)
            <div class="card bg-dark text-white border-secondary mb-4 shadow">
                <div class="card-header bg-secondary bg-opacity-25 d-flex justify-content-between align-items-center">
                    @php
                        $displayStatus = $booking->status === 'pending' && $booking->payment?->status === 'success'
                            ? 'paid'
                            : $booking->status;
                    @endphp
                    <div>
                        <strong>Mã Đơn Hàng:</strong> <span
                            class="badge bg-warning text-dark fs-6">{{ $booking->booking_code }}</span>
                        <span class="ms-3 text-muted">Ngày đặt: {{ $booking->created_at->format('H:i - d/m/Y') }}</span>
                    </div>
                    <div>
                        @php
                            $statusLabel = match ($displayStatus) {
                                'paid' => 'Đã thanh toán',
                                'completed' => 'Hoàn tất',
                                'refund_pending' => 'Chờ xác nhận hoàn tiền',
                                'refunded' => 'Đã hủy vé, hoàn tiền',
                                'cancelled' => 'Đã hủy vé',
                                default => ucfirst($booking->status),
                            };
                            $statusClass = match ($displayStatus) {
                                'refund_pending' => 'bg-warning text-dark',
                                'refunded', 'cancelled' => 'bg-danger',
                                'completed' => 'bg-primary',
                                default => 'bg-success',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }} fs-6">{{ $statusLabel }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <!-- Khung QR Code thu nhỏ -->
                        <div class="col-md-3 text-center mb-3 mb-md-0 border-end border-secondary">
                            @if(in_array($displayStatus, ['paid', 'completed']))
                                <div class="p-2 bg-white rounded d-inline-block">
                                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(110)->generate($booking->booking_code) !!}
                                </div>
                                <div class="small text-muted mt-1">Mã QR Vé Điện Tử</div>
                            @else
                                <div class="text-muted py-4">{{ $displayStatus === 'cancelled' ? 'Vé đã hủy' : 'Không còn hiệu lực' }}</div>
                            @endif
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
                            @if($displayStatus === 'pending')
                                <a href="{{ route('payment.checkout', $booking->id) }}" class="btn btn-warning btn-sm me-1">
                                    <i class="bi bi-credit-card me-1"></i>Thanh toán
                                </a>
                                <form method="POST" action="{{ route('booking.cancel', $booking->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Bạn chắc chắn muốn hủy đơn chưa thanh toán?')">Hủy đơn</button>
                                </form>
                            @endif

                            @if(in_array($displayStatus, ['paid', 'completed']))
                                <a href="{{ route('booking.success', $booking->id) }}" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-qr-code me-1"></i> Xem Chi Tiết Vé
                                </a>
                            @endif

                            @if($displayStatus === 'refund_pending')
                                <div class="small text-warning mt-2">Yêu cầu hoàn tiền đã gửi, đang chờ Admin xác nhận.</div>
                            @elseif($displayStatus === 'refunded')
                                <div class="small text-success mt-2">Admin đã xác nhận hoàn tiền.</div>
                            @endif

                            @if($displayStatus === 'paid' && $booking->showtime->start_time->gt(now()->addHours(24)))
                                <form method="POST" action="{{ route('booking.cancel_paid', $booking->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Hủy vé không hoàn tiền? Đơn và thanh toán vẫn được lưu trong lịch sử.')">Hủy vé</button>
                                </form>
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#refundModal{{ $booking->id }}">
                                    Yêu cầu hoàn tiền
                                </button>

                                <div class="modal fade" id="refundModal{{ $booking->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content text-dark">
                                            <form method="POST" action="{{ route('booking.refund', $booking->id) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Yêu cầu hoàn tiền</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <p class="small text-muted">Chỉ được hủy/hoàn tiền khi còn hơn 24 giờ trước giờ chiếu.</p>
                                                    <div class="mb-3">
                                                        <label for="bank_name_{{ $booking->id }}" class="form-label">Ngân hàng</label>
                                                        <input id="bank_name_{{ $booking->id }}" name="bank_name" class="form-control" maxlength="100" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="account_number_{{ $booking->id }}" class="form-label">Số tài khoản</label>
                                                        <input id="account_number_{{ $booking->id }}" name="account_number" class="form-control" maxlength="50" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="account_name_{{ $booking->id }}" class="form-label">Tên chủ tài khoản</label>
                                                        <input id="account_name_{{ $booking->id }}" name="account_name" class="form-control" maxlength="150" required>
                                                    </div>
                                                    <div>
                                                        <label for="reason_{{ $booking->id }}" class="form-label">Lý do hoàn vé</label>
                                                        <textarea id="reason_{{ $booking->id }}" name="reason" class="form-control" maxlength="500" rows="2" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                    <button type="submit" class="btn btn-primary" onclick="return confirm('Gửi yêu cầu hủy vé và hoàn tiền?')">Gửi yêu cầu</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
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
            {{ $bookings->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection