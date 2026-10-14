@extends('layouts.app')

@section('title', 'Lịch sử đặt vé - MovieTicket')

@section('content')
    <div class="container py-4">
        <h2 class="h4 fw-bold text-white mb-4">
            <i class="bi bi-ticket-perforated text-warning me-2"></i>Lịch sử đặt vé của bạn
        </h2>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>Vui lòng kiểm tra lại thông tin nhập trong biểu mẫu hoàn tiền.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-3">
            @forelse($bookings as $booking)
                @php
                    $showtimeStart = \Carbon\Carbon::parse($booking->showtime->start_time);
                    $canRefund = \Carbon\Carbon::now()->diffInHours($showtimeStart, false) >= 24;

                    // Lấy danh sách số ghế (Nếu ghế đã bị xóa khi giải phóng thì trả về thông báo phù hợp)
                    $seatNumbers = $booking->bookingSeats && $booking->bookingSeats->count() > 0
                        ? $booking->bookingSeats->map(fn($s) => $s->seat->seat_number)->join(', ')
                        : 'Đã giải phóng ghế';
                @endphp

                <div class="col-12">
                    <div class="card bg-dark text-white border-secondary p-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <div class="text-warning small fw-bold">MÃ ĐƠN: {{ $booking->booking_code }}</div>
                                <h3 class="h5 fw-bold mb-1">{{ $booking->showtime->movie->title }}</h3>
                                <div class="text-secondary small">
                                    <i class="bi bi-clock me-1"></i>Suất chiếu:
                                    <strong>{{ $showtimeStart->format('H:i - d/m/Y') }}</strong>
                                    <span class="mx-2">•</span>
                                    <i class="bi bi-geo-alt me-1"></i>Phòng {{ $booking->showtime->room->name }}
                                </div>
                                <div class="text-secondary small mt-1">
                                    Ghế: <strong class="text-warning">{{ $seatNumbers }}</strong>
                                </div>
                            </div>

                            <div class="text-md-end">
                                {{-- HIỂN THỊ TRẠNG THÁI VÉ LINH HOẠT --}}
                                <div class="mb-2">
                                    @if($booking->status === 'paid')
                                        <span class="badge bg-success fs-6">ĐÃ THANH TOÁN</span>
                                    @elseif($booking->status === 'refund_pending')
                                        <span class="badge bg-warning text-dark fs-6">
                                            <i class="bi bi-clock-history me-1"></i>CHỜ HOÀN TIỀN
                                        </span>
                                    @elseif($booking->status === 'refunded')
                                        <span class="badge bg-info text-dark fs-6">
                                            <i class="bi bi-check-circle-fill me-1"></i>ĐÃ HOÀN TIỀN
                                        </span>
                                    @else
                                        <span class="badge bg-danger fs-6">ĐÃ HỦY</span>
                                    @endif
                                </div>

                                <div class="fw-bold text-warning fs-5 mb-2">{{ number_format($booking->total_amount) }} VNĐ
                                </div>

                                {{-- Chỉ hiển thị các nút thao tác khi vé đang ở trạng thái ĐÃ THANH TOÁN (PAID) --}}
                                @if($booking->status === 'paid')
                                    <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                                        {{-- Nút Mở Modal Hoàn vé (Điều kiện > 24h trước giờ chiếu) --}}
                                        @if($canRefund)
                                            <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#refundModal{{ $booking->id }}">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Hoàn vé (Hoàn tiền)
                                            </button>
                                        @else
                                            <button class="btn btn-outline-secondary btn-sm" disabled
                                                title="Đã quá hạn hoàn tiền (Chỉ áp dụng trước giờ chiếu ít nhất 24h)">
                                                <i class="bi bi-slash-circle me-1"></i>Hết hạn hoàn tiền
                                            </button>
                                        @endif

                                        {{-- Nút Hủy vé (Hiện cảnh báo không được hoàn tiền) --}}
                                        <form method="POST" action="{{ route('booking.cancel_paid', $booking->id) }}"
                                            onsubmit="return confirm('CẢNH BÁO HỦY VÉ!\n- Nếu bạn hủy vé này, bạn sẽ KHÔNG ĐƯỢC HOÀN TIỀN.\n- Ghế đã chọn sẽ bị hủy.\n\nBạn có chắc chắn muốn hủy?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-x-circle me-1"></i>Hủy vé (Không hoàn tiền)
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL NHẬP THÔNG TIN TÀI KHOẢN HOÀN TIỀN --}}
                @if($booking->status === 'paid' && $canRefund)
                    <div class="modal fade" id="refundModal{{ $booking->id }}" tabindex="-1"
                        aria-labelledby="refundModalLabel{{ $booking->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-white border-secondary">
                                <div class="modal-header border-secondary">
                                    <h5 class="modal-title text-warning fs-6 fw-bold" id="refundModalLabel{{ $booking->id }}">
                                        <i class="bi bi-bank me-2"></i>Yêu cầu hoàn vé & Nhận lại tiền
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <form action="{{ route('booking.refund', $booking->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="p-2 mb-3 bg-secondary bg-opacity-25 rounded border border-secondary text-start">
                                            <div class="small text-secondary">Số tiền sẽ nhận lại:</div>
                                            <div class="fs-5 fw-bold text-warning">{{ number_format($booking->total_amount) }} VNĐ
                                            </div>
                                        </div>

                                        <div class="mb-3 text-start">
                                            <label class="form-label small text-secondary">Ngân hàng / Ví nhận tiền <span
                                                    class="text-danger">*</span></label>
                                            <select name="bank_name"
                                                class="form-select bg-secondary bg-opacity-25 text-white border-secondary" required>
                                                <option value="" class="bg-dark">-- Chọn ngân hàng --</option>
                                                <option value="MBBank" class="bg-dark">MBBank (Ngân hàng Quân Đội)</option>
                                                <option value="Vietcombank" class="bg-dark">Vietcombank</option>
                                                <option value="Techcombank" class="bg-dark">Techcombank</option>
                                                <option value="VietinBank" class="bg-dark">VietinBank</option>
                                                <option value="BIDV" class="bg-dark">BIDV</option>
                                                <option value="VPBank" class="bg-dark">VPBank</option>
                                                <option value="TPBank" class="bg-dark">TPBank</option>
                                                <option value="ACB" class="bg-dark">ACB</option>
                                                <option value="Sacombank" class="bg-dark">Sacombank</option>
                                                <option value="MoMo" class="bg-dark">Ví MoMo</option>
                                            </select>
                                        </div>

                                        <div class="mb-3 text-start">
                                            <label class="form-label small text-secondary">Số tài khoản / Số MoMo <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="account_number"
                                                class="form-control bg-secondary bg-opacity-25 text-white border-secondary"
                                                placeholder="Ví dụ: 0339936910" required>
                                        </div>

                                        <div class="mb-3 text-start">
                                            <label class="form-label small text-secondary">Tên chủ tài khoản (Viết hoa không dấu)
                                                <span class="text-danger">*</span></label>
                                            <input type="text" name="account_name"
                                                class="form-control bg-secondary bg-opacity-25 text-white border-secondary"
                                                placeholder="Ví dụ: PHAM MINH VU" style="text-transform: uppercase;" required>
                                        </div>

                                        <div class="mb-3 text-start">
                                            <label class="form-label small text-secondary">Lý do hoàn vé (Không bắt buộc)</label>
                                            <textarea name="reason"
                                                class="form-control bg-secondary bg-opacity-25 text-white border-secondary" rows="2"
                                                placeholder="Nhập lý do hoàn vé..."></textarea>
                                        </div>
                                    </div>

                                    <div class="modal-footer border-secondary">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                                        <button type="submit" class="btn btn-warning btn-sm fw-bold">
                                            <i class="bi bi-send me-1"></i>Xác nhận gửi yêu cầu
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="col-12 text-center text-secondary py-5">
                    <i class="bi bi-ticket-detailed fs-1 d-block mb-2"></i>
                    Bạn chưa có lịch sử đặt vé nào.
                </div>
            @endforelse
        </div>
    </div>
@endsection