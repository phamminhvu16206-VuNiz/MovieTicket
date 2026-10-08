@extends('layouts.app')

@section('title', 'Thanh toán V3 - MovieTicket')

@section('content')
    <style>
        .payment-v3 {
            --pay-bg: #111827;
            --pay-card: #172033;
            --pay-border: rgba(255, 255, 255, .09);
            --pay-muted: #94a3b8;
        }

        .payment-hero {
            background:
                radial-gradient(circle at 15% 20%, rgba(245, 158, 11, .16), transparent 32%),
                radial-gradient(circle at 85% 10%, rgba(59, 130, 246, .16), transparent 28%),
                linear-gradient(135deg, #0b1120, #111827 55%, #0f172a);
            border: 1px solid var(--pay-border);
            border-radius: 24px;
            padding: 28px;
        }

        .payment-card {
            background: rgba(23, 32, 51, .92);
            border: 1px solid var(--pay-border);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .22);
        }

        .step {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--pay-muted);
            font-size: .9rem;
        }

        .step.active {
            color: #fff;
        }

        .step-dot {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #263246;
            border: 1px solid rgba(255, 255, 255, .1);
            font-weight: 700;
        }

        .step.active .step-dot {
            background: #f59e0b;
            color: #111827;
            border-color: #f59e0b;
        }

        .movie-cover {
            width: 74px;
            height: 100px;
            object-fit: cover;
            border-radius: 12px;
            background: #0b1220;
        }

        .combo-row {
            border: 1px solid var(--pay-border);
            background: #111827;
            border-radius: 16px;
            transition: .2s ease;
        }

        .combo-row:focus-within {
            border-color: rgba(245, 158, 11, .65);
            transform: translateY(-1px);
        }

        .qty-control {
            width: 88px;
        }

        .method-card {
            position: relative;
            display: block;
            height: 100%;
            cursor: pointer;
            border: 1px solid var(--pay-border);
            background: #111827;
            border-radius: 16px;
            padding: 18px;
            transition: .2s ease;
        }

        .method-card:hover {
            border-color: rgba(245, 158, 11, .55);
        }

        .method-card.selected {
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, .13);
        }

        .method-card input {
            position: absolute;
            opacity: 0;
        }

        .method-icon {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            background: #243044;
            font-size: 1.25rem;
        }

        .qr-box {
            background: #fff;
            border-radius: 22px;
            padding: 18px;
            display: inline-block;
            box-shadow: 0 20px 45px rgba(0, 0, 0, .3);
        }

        .status-pulse {
            animation: pulsePay 1.6s infinite;
        }

        @keyframes pulsePay {
            50% {
                opacity: .55;
            }
        }

        .summary-row {
            border-bottom: 1px dashed rgba(255, 255, 255, .09);
            padding: 11px 0;
        }

        .summary-row:last-child {
            border-bottom: 0;
        }

        .secure-note {
            color: #a7f3d0;
            background: rgba(16, 185, 129, .08);
            border: 1px solid rgba(16, 185, 129, .18);
        }
    </style>

    <div class="container py-3 py-lg-5 payment-v3">
        <div class="payment-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="text-warning small fw-bold text-uppercase mb-2">MovieTicket Payment V3</div>
                    <h1 class="h2 fw-bold mb-2">Hoàn tất đặt vé của bạn</h1>
                    <p class="text-secondary mb-0">Thanh toán an toàn, theo dõi trạng thái tự động và nhận vé ngay khi giao
                        dịch được xác nhận.</p>
                </div>
                <div class="d-flex gap-3">
                    <div class="step active"><span class="step-dot">1</span><span>Đơn vé</span></div>
                    <div class="step active"><span class="step-dot">2</span><span>Thanh toán</span></div>
                    <div class="step"><span class="step-dot">3</span><span>Nhận vé</span></div>
                </div>
            </div>
        </div>

        @if($booking->status === 'paid')
            <div class="alert alert-success payment-card border-0 mb-4">
                <i class="bi bi-check-circle-fill me-2"></i>
                Đơn vé này đã được thanh toán. <a class="alert-link" href="{{ route('booking.success', $booking->id) }}">Xem vé
                    điện tử</a>.
            </div>
        @endif

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <div class="payment-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        @if($booking->showtime->movie->poster)
                            <img class="movie-cover" src="{{ asset('images/posters/' . $booking->showtime->movie->poster) }}"
                                alt="">
                        @else
                            <div class="movie-cover d-grid place-items-center"><i class="bi bi-film fs-2 text-warning"></i>
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <div class="text-warning small fw-bold">MÃ ĐƠN {{ $booking->booking_code }}</div>
                            <h2 class="h4 fw-bold mb-1">{{ $booking->showtime->movie->title }}</h2>
                            <div class="text-secondary small">
                                <i
                                    class="bi bi-calendar3 me-1"></i>{{ $booking->showtime->start_time->format('H:i - d/m/Y') }}
                                <span class="mx-2">•</span>
                                <i
                                    class="bi bi-geo-alt me-1"></i>{{ $booking->showtime->room->cinema->name ?? 'MovieTicket' }}
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-md-4">
                            <div class="bg-dark rounded-3 p-3 h-100">
                                <div class="text-secondary small">Phòng</div>
                                <strong>{{ $booking->showtime->room->name }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-dark rounded-3 p-3 h-100">
                                <div class="text-secondary small">Ghế</div>
                                <strong>{{ $booking->bookingSeats->map(fn($s) => $s->seat->seat_number)->join(', ') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-dark rounded-3 p-3 h-100">
                                <div class="text-secondary small">Trạng thái</div>
                                <strong id="bookingStatusLabel"
                                    class="{{ $booking->status === 'paid' ? 'text-success' : 'text-warning' }}">
                                    {{ strtoupper($booking->status) }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    <h3 class="h5 fw-bold mb-3"><i class="bi bi-cup-straw text-warning me-2"></i>Combo bắp nước</h3>

                    <form id="paymentForm" method="POST" action="{{ route('payment.process', $booking->id) }}">
                        @csrf

                        <div class="d-flex flex-column gap-2">
                            @forelse($combos as $combo)
                                @php
                                    $selectedQty = (int) optional($booking->bookingCombos->firstWhere('combo_id', $combo->id))->quantity;
                                @endphp
                                <div class="combo-row p-3 d-flex align-items-center gap-3">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $combo->name }}</div>
                                        @if($combo->description)
                                            <div class="small text-secondary">{{ $combo->description }}</div>
                                        @endif
                                        <div class="text-warning fw-semibold mt-1">{{ number_format($combo->price) }} VNĐ</div>
                                    </div>
                                    <input class="form-control bg-dark text-white border-secondary qty-control combo-qty"
                                        type="number" min="0" max="20" name="combo_qty[{{ $combo->id }}]"
                                        value="{{ $selectedQty }}" data-price="{{ (float) $combo->price }}"
                                        aria-label="Số lượng {{ $combo->name }}">
                                </div>
                            @empty
                                <div class="text-secondary">Hiện chưa có combo nào.</div>
                            @endforelse
                        </div>

                        <h3 class="h5 fw-bold mt-4 mb-3"><i class="bi bi-ticket-perforated text-warning me-2"></i>Mã giảm
                            giá</h3>
                        <div class="input-group">
                            <input name="code" class="form-control bg-dark text-white border-secondary"
                                placeholder="Nhập mã voucher">
                            <button type="submit" formaction="{{ route('payment.voucher', $booking->id) }}"
                                formmethod="POST" class="btn btn-outline-warning px-4">
                                Áp dụng
                            </button>
                        </div>
                        @if($voucher)
                            <div class="small text-success mt-2">
                                <i class="bi bi-check-circle me-1"></i>Đang áp dụng <strong>{{ $voucher->code }}</strong> — giảm
                                {{ number_format($discount) }} VNĐ
                            </div>
                        @endif

                        <h3 class="h5 fw-bold mt-4 mb-3"><i class="bi bi-wallet2 text-warning me-2"></i>Phương thức thanh
                            toán</h3>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="method-card selected" data-method-card>
                                    <input type="radio" name="method" value="sepay_qr" checked>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="method-icon text-success"><i class="bi bi-qr-code"></i></div>
                                        <div>
                                            <div class="fw-bold">Chuyển khoản QR SePay</div>
                                            <div class="small text-secondary">Tự động xác nhận giao dịch</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="method-card" data-method-card>
                                    <input type="radio" name="method" value="qr">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="method-icon text-info"><i class="bi bi-phone"></i></div>
                                        <div>
                                            <div class="fw-bold">QR mô phỏng</div>
                                            <div class="small text-secondary">Dùng thử nghiệm/demo</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        @if($booking->status === 'pending')
                            <button id="payButton" class="btn btn-warning btn-lg w-100 fw-bold mt-4 py-3" type="submit">
                                <i class="bi bi-shield-check me-2"></i>
                                <span id="payButtonText">TẠO QR & THANH TOÁN</span>
                            </button>
                        @endif
                    </form>

                    <div class="secure-note rounded-3 p-3 mt-3 small">
                        <i class="bi bi-shield-lock-fill me-2"></i>
                        Thanh toán an toàn. Hệ thống sẽ tự động cập nhật vé sau khi nhận thông báo chuyển khoản thành công
                        từ ngân hàng.
                    </div>
                </div>

                {{-- CHỈ HIỂN THỊ KHUNG QR KHI NGƯỜI DÙNG BẤM TẠO QR ($sepayQrUrl KHÁC NULL) --}}
                @if($sepayQrUrl)
                    <div id="sepayPanel" class="payment-card p-4 text-center mb-4">
                        <div class="text-warning small fw-bold text-uppercase">MÃ QR THANH TOÁN SEPAY</div>
                        <h3 class="h4 fw-bold mt-1">Quét mã QR bằng ứng dụng Ngân hàng</h3>
                        <p class="text-secondary mb-4">Vui lòng giữ nguyên nội dung chuyển khoản: <strong
                                class="text-white">{{ $booking->booking_code }}</strong></p>

                        <div class="qr-box mb-3">
                            <img src="{{ $sepayQrUrl }}" width="280" height="280" alt="QR thanh toán SePay">
                        </div>

                        <div class="row g-2 text-start mx-auto" style="max-width:560px">
                            <div class="col-6">
                                <div class="bg-dark rounded-3 p-3"><small class="text-secondary d-block">Ngân
                                        hàng</small><strong>{{ $bankName ?: 'MBBank' }}</strong></div>
                            </div>
                            <div class="col-6">
                                <div class="bg-dark rounded-3 p-3"><small class="text-secondary d-block">Số tài
                                        khoản</small><strong>{{ $bankAccount ?: 'N/A' }}</strong></div>
                            </div>
                            <div class="col-6">
                                <div class="bg-dark rounded-3 p-3"><small class="text-secondary d-block">Chủ tài
                                        khoản</small><strong>{{ $accountName ?: 'MovieTicket' }}</strong></div>
                            </div>
                            <div class="col-6">
                                <div class="bg-dark rounded-3 p-3"><small class="text-secondary d-block">Số tiền</small><strong
                                        class="text-warning">{{ number_format($total) }} VNĐ</strong></div>
                            </div>
                        </div>

                        @if($booking->status === 'pending')
                            <div class="mt-4 d-flex justify-content-center align-items-center gap-2 text-warning status-pulse">
                                <span class="spinner-border spinner-border-sm"></span>
                                <span>Đang lắng nghe kết quả thanh toán từ ngân hàng...</span>
                            </div>

                            <form method="POST" action="{{ route('payment.cancel', $booking->id) }}" class="mt-3">
                                @csrf
                                <button class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Hủy yêu cầu thanh toán này?')">
                                    Hủy giao dịch
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="payment-card p-4 sticky-lg-top" style="top:20px">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 fw-bold mb-0">Tóm tắt đơn hàng</h3>
                        <span class="badge rounded-pill bg-secondary">V3</span>
                    </div>

                    <div class="summary-row d-flex justify-content-between gap-3">
                        <span class="text-secondary">Tiền vé</span>
                        <strong>{{ number_format($seatTotal) }} VNĐ</strong>
                    </div>
                    <div class="summary-row d-flex justify-content-between gap-3">
                        <span class="text-secondary">Combo</span>
                        <strong id="summaryCombo">{{ number_format($comboTotal) }} VNĐ</strong>
                    </div>
                    <div class="summary-row d-flex justify-content-between gap-3">
                        <span class="text-secondary">Tạm tính</span>
                        <strong id="summarySubtotal">{{ number_format($subtotal) }} VNĐ</strong>
                    </div>
                    <div class="summary-row d-flex justify-content-between gap-3 text-success">
                        <span>Giảm giá</span>
                        <strong id="summaryDiscount">-{{ number_format($discount) }} VNĐ</strong>
                    </div>
                    <div class="pt-3 mt-1 d-flex justify-content-between align-items-end gap-3">
                        <div>
                            <div class="text-secondary small">TỔNG THANH TOÁN</div>
                        </div>
                        <strong id="summaryTotal" class="text-warning fs-3">{{ number_format($total) }} VNĐ</strong>
                    </div>

                    @if($booking->payment)
                        <div class="border-top border-secondary mt-4 pt-3 small">
                            <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Mã giao
                                    dịch</span><strong>{{ $booking->payment->payment_code }}</strong></div>
                            <div class="d-flex justify-content-between"><span class="text-secondary">Trạng thái</span><strong
                                    id="paymentStatusText">{{ strtoupper($booking->payment->status) }}</strong></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const baseSeat = {{ (float) $seatTotal }};
            const voucherDiscount = {{ (float) $discount }};
            const statusUrl = @json(route('payment.status', $booking->id));
            const successUrl = @json(route('booking.success', $booking->id));
            const isPending = @json($booking->status === 'pending');
            const isQrVisible = @json(!is_null($sepayQrUrl));

            const money = value => new Intl.NumberFormat('vi-VN').format(Math.max(0, value)) + ' VNĐ';

            function calcTotal() {
                let combo = 0;
                document.querySelectorAll('.combo-qty').forEach(input => {
                    combo += Math.max(0, parseInt(input.value || 0, 10)) * parseFloat(input.dataset.price || 0);
                });

                const subtotal = baseSeat + combo;
                const discount = Math.min(voucherDiscount, subtotal);
                const total = Math.max(0, subtotal - discount);

                document.getElementById('summaryCombo').textContent = money(combo);
                document.getElementById('summarySubtotal').textContent = money(subtotal);
                document.getElementById('summaryDiscount').textContent = '-' + money(discount);
                document.getElementById('summaryTotal').textContent = money(total);
            }

            document.querySelectorAll('.combo-qty').forEach(input => input.addEventListener('input', calcTotal));
            calcTotal();

            document.querySelectorAll('[data-method-card]').forEach(card => {
                card.addEventListener('click', () => {
                    document.querySelectorAll('[data-method-card]').forEach(x => x.classList.remove('selected'));
                    card.classList.add('selected');
                    const radio = card.querySelector('input');
                    if (radio) radio.checked = true;
                    const text = document.getElementById('payButtonText');
                    if (text) text.textContent = radio.value === 'sepay_qr' ? 'TẠO QR & THANH TOÁN' : 'XÁC NHẬN THANH TOÁN';
                });
            });

            // Chỉ thực hiện Polling kiểm tra trạng thái tự động KHI khung QR đã được khởi tạo
            if (isPending && isQrVisible) {
                const timer = setInterval(async () => {
                    try {
                        const response = await fetch(statusUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!response.ok) return;
                        const data = await response.json();

                        if (data.success) {
                            clearInterval(timer);
                            window.location.href = successUrl;
                        }
                    } catch (e) {
                        // Tiếp tục lắng nghe
                    }
                }, 3000);
            }
        })();
    </script>
@endsection