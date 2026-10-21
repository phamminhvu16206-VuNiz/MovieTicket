@extends('layouts.app')

@section('title', 'Thanh toán V3 - MovieTicket')

@section('content')
<style>
    .payment-v3 {
        --pay-ink: #172033;
        --pay-muted: #64748b;
        --pay-border: #e7ebf2;
        --pay-surface: #fff;
        color: var(--pay-ink);
    }
    .payment-hero {
        background: linear-gradient(115deg,#fff 0%,#fff7ed 60%,#f3f0ff 100%);
        border: 1px solid #fed7aa;
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 12px 35px rgba(23,32,51,.07);
    }
    .payment-hero h1 { color: var(--pay-ink); }
    .payment-hero p { color: var(--pay-muted) !important; }
    .payment-card {
        color: var(--pay-ink);
        background: var(--pay-surface);
        border: 1px solid var(--pay-border);
        border-radius: 20px;
        box-shadow: 0 12px 35px rgba(23,32,51,.07);
    }
    .step {
        display:flex; align-items:center; gap:10px; color:var(--pay-muted); font-size:.9rem;
    }
    .step.active { color:var(--pay-ink); }
    .step-dot {
        width:34px;height:34px;border-radius:50%;display:grid;place-items:center;
        background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;font-weight:700;
    }
    .step.active .step-dot { background:#f97316;color:#fff;border-color:#f97316; }
    .movie-cover {
        width:74px;height:100px;object-fit:cover;border-radius:12px;background:#0b1220;
    }
    .payment-cover-placeholder { display:grid;place-items:center;background:#fff7ed; }
    .combo-row {
        border:1px solid var(--pay-border); background:#fff; border-radius:16px;
        transition:.2s ease;
    }
    .combo-row:focus-within { border-color:#fb923c; box-shadow:0 0 0 3px rgba(249,115,22,.1); }
    .qty-control { width:88px; }
    .method-card {
        position:relative; display:block; height:100%; cursor:pointer;
        border:1px solid var(--pay-border); background:#fff; border-radius:16px;
        padding:18px; transition:.2s ease;
    }
    .method-card:hover { border-color:#fdba74; background:#fffaf5; }
    .method-card.selected { border-color:#f97316; background:#fff7ed; box-shadow:0 0 0 2px rgba(249,115,22,.12); }
    .method-card input { position:absolute; opacity:0; }
    .method-card:focus-within { outline:3px solid rgba(249,115,22,.22);outline-offset:2px; }
    .method-icon {
        width:44px;height:44px;border-radius:13px;display:grid;place-items:center;
        background:#fff7ed;color:#ea580c;font-size:1.25rem;
    }
    .qr-box {
        background:#fff; border-radius:22px; padding:18px; display:inline-block;
        border:1px solid var(--pay-border);box-shadow:0 12px 30px rgba(23,32,51,.1);
    }
    .sepay-modal-backdrop {
        position:fixed;inset:0;z-index:1080;display:grid;place-items:center;
        padding:max(8px,env(safe-area-inset-top)) max(8px,env(safe-area-inset-right)) max(8px,env(safe-area-inset-bottom)) max(8px,env(safe-area-inset-left));
        background:rgba(15,23,42,.68);backdrop-filter:blur(7px);
        opacity:0;visibility:hidden;transition:opacity .2s ease,visibility .2s ease;
    }
    .sepay-modal-backdrop.is-open { opacity:1;visibility:visible; }
    .sepay-modal {
        position:relative;display:flex;flex-direction:column;align-items:center;gap:clamp(.35rem,1.1vh,.75rem);
        width:min(100%,560px);max-height:calc(100vh - 16px);max-height:calc(100dvh - 16px);
        overflow-x:hidden;overflow-y:auto;scrollbar-width:none;-ms-overflow-style:none;
        padding:clamp(.75rem,2.2vh,1.5rem) clamp(.75rem,3vw,2rem);
        border:1px solid #e2e8f0;border-radius:24px;
        background:#fff;color:#172033;text-align:center;
        box-shadow:0 28px 90px rgba(15,23,42,.3);
        transform:translateY(12px) scale(.98);transition:transform .2s ease;
    }
    .sepay-modal::-webkit-scrollbar { display:none; }
    .sepay-modal-backdrop.is-open .sepay-modal { transform:translateY(0) scale(1); }
    .sepay-modal-close {
        position:absolute;top:.85rem;right:.85rem;display:grid;place-items:center;
        width:38px;height:38px;border:0;border-radius:50%;background:#f1f5f9;
        color:#475569;font-size:1.25rem;
    }
    .sepay-modal-close:hover { background:#e2e8f0;color:#172033; }
    .sepay-modal-kicker { color:#ea580c;font-size:clamp(.62rem,1.5vh,.75rem);font-weight:800;letter-spacing:.1em;text-transform:uppercase; }
    .sepay-modal h2 { margin:0 2.5rem;color:#172033;font-size:clamp(1.05rem,3vh,1.45rem);font-weight:800;line-height:1.2; }
    .sepay-modal-description { max-width:38rem;margin:0;color:#64748b;font-size:clamp(.8rem,1.8vh,.95rem);line-height:1.4; }
    .sepay-modal .qr-box { margin:0;padding:clamp(6px,1vh,12px);border-radius:16px; }
    .sepay-modal .qr-box img { display:block;width:min(280px,68vw,26dvh);height:auto; }
    .sepay-modal-info {
        display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem;
        width:min(100%,38rem);margin:0 auto;text-align:left;
    }
    .sepay-modal-info-item { min-width:0;padding:clamp(.45rem,1.2vh,.75rem);border:1px solid #e7ebf2;border-radius:12px;background:#f8fafc;overflow-wrap:anywhere; }
    .sepay-modal-info-item small { display:block;margin-bottom:.2rem;color:#64748b; }
    .sepay-modal-info-item strong { color:#172033; }
    .sepay-modal-waiting { display:flex;justify-content:center;align-items:center;gap:.55rem;margin:0;color:#c2410c;font-size:clamp(.75rem,1.7vh,.9rem);font-weight:650; }
    .sepay-modal-actions { display:flex;justify-content:center;gap:.65rem;flex-wrap:wrap; }
    .sepay-reopen-button { display:none; }
    .sepay-reopen-button.is-visible { display:inline-flex; }
    body.sepay-modal-open { overflow:hidden; }
    .status-pulse { animation:pulsePay 1.6s infinite; }
    @keyframes pulsePay { 50% { opacity:.55; } }
    .summary-row { border-bottom:1px dashed var(--pay-border); padding:11px 0; }
    .summary-row:last-child { border-bottom:0; }
    .secure-note { color:#166534; background:#f0fdf4; border:1px solid #bbf7d0; }
    .payment-v3 .bg-dark { background:#f8fafc !important;color:var(--pay-ink) !important; }
    .payment-v3 .text-white,.payment-v3 .text-light { color:var(--pay-ink) !important; }
    .payment-v3 .text-secondary { color:var(--pay-muted) !important; }
    .payment-v3 .text-warning { color:#ea580c !important; }
    .payment-v3 .text-info { color:#7c3aed !important; }
    .payment-v3 .payment-card h2,.payment-v3 .payment-card h3 { color:var(--pay-ink); }
    .payment-v3 .payment-card .alert-dark { color:#475569;background:#f8fafc;border-color:#e2e8f0; }
    .payment-v3 .payment-card .alert-dark i { color:#ea580c; }
    .payment-v3 .form-control { color:var(--pay-ink);background:#fff;border-color:#cbd5e1; }
    .payment-v3 .form-control:focus { border-color:#fb923c;box-shadow:0 0 0 .2rem rgba(249,115,22,.15); }
    .payment-v3 .text-success { color:#15803d !important; }
    .payment-v3 .text-danger { color:#dc2626 !important; }
    @media(max-width:575.98px) {
        .payment-hero { padding:20px; }
        .payment-hero .step { gap:6px;font-size:.78rem; }
        .payment-hero .step-dot { width:30px;height:30px; }
        .payment-card { padding:1.1rem !important; }
    }
    @media(max-width:420px) {
        .sepay-modal-info { gap:.4rem; }
        .sepay-modal-info-item { padding:.45rem; }
        .sepay-modal-actions { width:100%; }
        .sepay-modal-actions>* { flex:1 1 100%; }
        .sepay-modal-actions .btn { width:100%; }
    }
    @media(max-height:620px) {
        .sepay-modal { gap:.35rem;padding:.55rem .75rem; }
        .sepay-modal h2 { font-size:1.05rem; }
        .sepay-modal-description { font-size:.75rem; }
        .sepay-modal .qr-box img { width:min(180px,42vw,19dvh); }
        .sepay-modal-info { gap:.35rem; }
        .sepay-modal-info-item { padding:.35rem .5rem; }
        .sepay-modal-info-item small { margin-bottom:0; }
        .sepay-modal-waiting { font-size:.75rem; }
        .sepay-modal-actions .btn { padding:.35rem .65rem;font-size:.8rem; }
    }
    @media(max-height:420px) {
        .sepay-modal { gap:.25rem;padding:.4rem .65rem; }
        .sepay-modal-kicker { font-size:.58rem; }
        .sepay-modal .qr-box img { width:min(110px,30vw,15dvh); }
        .sepay-modal-info-item { font-size:.75rem;line-height:1.2; }
        .sepay-modal-info-item small { font-size:.65rem; }
        .sepay-modal-actions .btn { min-height:32px; }
    }
    @media(prefers-reduced-motion:reduce) {
        .sepay-modal-backdrop,.sepay-modal { transition:none; }
    }
</style>

<div class="container py-3 py-lg-5 payment-v3">
    <div class="payment-hero mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="text-warning small fw-bold text-uppercase mb-2">MovieTicket Payment V3</div>
                <h1 class="h2 fw-bold mb-2">Hoàn tất đặt vé của bạn</h1>
                <p class="text-secondary mb-0">Thanh toán an toàn, theo dõi trạng thái tự động và nhận vé ngay khi giao dịch được xác nhận.</p>
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
            Đơn vé này đã được thanh toán. <a class="alert-link" href="{{ route('booking.success', $booking->id) }}">Xem vé điện tử</a>.
        </div>
    @endif

    <div class="row g-4 align-items-start">
        <div class="col-lg-8">
            <div class="payment-card p-4 mb-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    @if($booking->showtime->movie->poster)
                        <img class="movie-cover" src="{{ asset('images/posters/'.$booking->showtime->movie->poster) }}" alt="">
                    @else
                        <div class="movie-cover payment-cover-placeholder"><i class="bi bi-film fs-2 text-warning"></i></div>
                    @endif
                    <div class="flex-grow-1">
                        <div class="text-warning small fw-bold">MÃ ĐƠN {{ $booking->booking_code }}</div>
                        <h2 class="h4 fw-bold mb-1">{{ $booking->showtime->movie->title }}</h2>
                        <div class="text-secondary small">
                            <i class="bi bi-calendar3 me-1"></i>{{ $booking->showtime->start_time->format('H:i - d/m/Y') }}
                            <span class="mx-2">•</span>
                            <i class="bi bi-geo-alt me-1"></i>{{ $booking->showtime->room->cinema->name ?? 'MovieTicket' }}
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
                            <strong id="bookingStatusLabel" class="{{ $booking->status === 'paid' ? 'text-success' : 'text-warning' }}">
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
                                <input
                                    class="form-control bg-dark text-white border-secondary qty-control combo-qty"
                                    type="number"
                                    min="0"
                                    max="20"
                                    name="combo_qty[{{ $combo->id }}]"
                                    value="{{ $selectedQty }}"
                                    data-price="{{ (float)$combo->price }}"
                                    aria-label="Số lượng {{ $combo->name }}"
                                >
                            </div>
                        @empty
                            <div class="text-secondary">Hiện chưa có combo nào.</div>
                        @endforelse
                    </div>

                    <h3 class="h5 fw-bold mt-4 mb-3"><i class="bi bi-ticket-perforated text-warning me-2"></i>Mã giảm giá</h3>
                    <div class="input-group">
                        <input name="code" class="form-control bg-dark text-white border-secondary" placeholder="Nhập mã voucher">
                        <button type="submit" formaction="{{ route('payment.voucher', $booking->id) }}" formmethod="POST" class="btn btn-outline-warning px-4">
                            Áp dụng
                        </button>
                    </div>
                    @if($voucher)
                        <div class="small text-success mt-2">
                            <i class="bi bi-check-circle me-1"></i>Đang áp dụng <strong>{{ $voucher->code }}</strong> — giảm {{ number_format($discount) }} VNĐ
                        </div>
                    @endif

                    <h3 class="h5 fw-bold mt-4 mb-3"><i class="bi bi-wallet2 text-warning me-2"></i>Phương thức thanh toán</h3>

                    <div class="row g-3">
                        @if($sepayEnabled)
                            <div class="col-md-6">
                                <label class="method-card {{ $booking->payment?->method !== 'cash' ? 'selected' : '' }}" data-method-card>
                                    <input type="radio" name="method" value="sepay_qr" {{ $booking->payment?->method !== 'cash' ? 'checked' : '' }}>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="method-icon text-success"><i class="bi bi-qr-code"></i></div>
                                        <div>
                                            <div class="fw-bold">Chuyển khoản QR SePay</div>
                                            <div class="small text-secondary">SePay • tự động xác nhận</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="method-card {{ !$sepayEnabled || $booking->payment?->method === 'cash' ? 'selected' : '' }}" data-method-card>
                                <input type="radio" name="method" value="cash" {{ !$sepayEnabled || $booking->payment?->method === 'cash' ? 'checked' : '' }}>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="method-icon text-light"><i class="bi bi-cash-stack"></i></div>
                                    <div>
                                        <div class="fw-bold">Thanh toán tại quầy</div>
                                        <div class="small text-secondary">Mô phỏng cho bài tập</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    @unless($sepayEnabled)
                        <div class="alert alert-warning mt-3 mb-0 small">
                            <i class="bi bi-info-circle me-1"></i>
                            SePay chưa được cấu hình. Để bật chuyển khoản QR tự động, hãy cấu hình tài khoản ngân hàng và API key SePay trong file môi trường; webhook cần URL công khai qua HTTPS.
                        </div>
                    @endunless

                    @if($booking->status === 'pending')
                        <button id="payButton" class="btn btn-warning btn-lg w-100 fw-bold mt-4 py-3" type="submit">
                            <i class="bi bi-shield-check me-2"></i>
                            <span id="payButtonText">{{ $sepayEnabled && $booking->payment?->method !== 'cash' ? 'TẠO QR & THANH TOÁN' : 'XÁC NHẬN THANH TOÁN TẠI QUẦY' }}</span>
                        </button>
                    @endif
                </form>

                <div class="secure-note rounded-3 p-3 mt-3 small">
                    <i class="bi bi-shield-lock-fill me-2"></i>
                    Thông tin thanh toán được xử lý theo trạng thái đơn. Với SePay, vé chỉ chuyển sang <strong>PAID</strong> sau khi webhook xác nhận số tiền thực nhận.
                </div>
            </div>

            @if($booking->payment && $booking->payment->method === 'sepay_qr' && $booking->payment->status === 'pending')
                <div class="payment-card p-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-bold">Đang chờ xác nhận thanh toán</div>
                        <div class="small text-secondary">Giữ trang này mở để nhận trạng thái từ SePay.</div>
                    </div>
                    <button type="button" class="btn btn-outline-warning sepay-reopen-button is-visible" data-sepay-open>
                        <i class="bi bi-qr-code me-1"></i> Hiện mã QR
                    </button>
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
                        <div class="small text-secondary">Đã bao gồm giảm giá</div>
                    </div>
                    <strong id="summaryTotal" class="text-warning fs-3">{{ number_format($total) }} VNĐ</strong>
                </div>

                @if($booking->payment)
                    <div class="border-top border-secondary mt-4 pt-3 small">
                        <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Payment code</span><strong>{{ $booking->payment->payment_code }}</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-secondary">Trạng thái</span><strong id="paymentStatusText">{{ strtoupper($booking->payment->status) }}</strong></div>
                    </div>
                @endif

                <div class="alert alert-dark border-secondary small mt-4 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Không đóng trang khi đang chờ SePay xác nhận.
                </div>
            </div>
        </div>
    </div>
</div>

@if($booking->payment && $booking->payment->method === 'sepay_qr' && $booking->payment->status === 'pending')
    <div id="sepayModalBackdrop" class="sepay-modal-backdrop is-open" data-sepay-backdrop>
        <section id="sepayModal" class="sepay-modal" role="dialog" aria-modal="true" aria-labelledby="sepayModalTitle" aria-describedby="sepayModalDescription" tabindex="-1">
            <button type="button" class="sepay-modal-close" aria-label="Đóng mã QR" data-sepay-close>
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
            <div class="sepay-modal-kicker">Thanh toán an toàn với SePay</div>
            <h2 id="sepayModalTitle">Quét mã QR để thanh toán</h2>
            <p id="sepayModalDescription" class="sepay-modal-description">
                Mở ứng dụng ngân hàng và quét mã. Nội dung chuyển khoản cần có mã đơn
                <strong>{{ $booking->booking_code }}</strong>.
            </p>

            @if($sepayQrUrl)
                <div class="qr-box">
                    <img src="{{ $sepayQrUrl }}" width="280" height="280" alt="Mã QR thanh toán SePay">
                </div>
            @else
                <div class="alert alert-warning text-start mt-3">SePay chưa được cấu hình tài khoản ngân hàng.</div>
            @endif

            <div class="sepay-modal-info">
                <div class="sepay-modal-info-item"><small>Ngân hàng</small><strong>{{ $bankName ?: 'Chưa cấu hình' }}</strong></div>
                <div class="sepay-modal-info-item"><small>Số tài khoản</small><strong>{{ $bankAccount ?: 'Chưa cấu hình' }}</strong></div>
                <div class="sepay-modal-info-item"><small>Chủ tài khoản</small><strong>{{ $accountName ?: 'Chưa cấu hình' }}</strong></div>
                <div class="sepay-modal-info-item"><small>Số tiền</small><strong class="text-warning">{{ number_format($total) }} VNĐ</strong></div>
            </div>

            <div class="sepay-modal-waiting status-pulse" aria-live="polite">
                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                <span>Đang chờ SePay xác nhận giao dịch...</span>
            </div>

            <div class="sepay-modal-actions">
                <button type="button" class="btn btn-outline-secondary" data-sepay-close>Đóng, tiếp tục chờ</button>
                <form method="POST" action="{{ route('payment.cancel', $booking->id) }}" onsubmit="return confirm('Hủy yêu cầu thanh toán này?')">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">Hủy thanh toán</button>
                </form>
            </div>
        </section>
    </div>
@endif

<script>
(() => {
    const baseSeat = {{ (float)$seatTotal }};
    const voucherDiscount = {{ (float)$discount }};
    const statusUrl = @json(route('payment.status', $booking->id));
    const successUrl = @json(route('booking.success', $booking->id));
    const pendingSepay = @json($booking->payment && $booking->payment->method === 'sepay_qr' && $booking->payment->status === 'pending');
    const sepayBackdrop = document.querySelector('[data-sepay-backdrop]');
    const sepayModal = document.getElementById('sepayModal');
    const openSepayModal = () => {
        if (!sepayBackdrop || !sepayModal) return;
        sepayBackdrop.classList.add('is-open');
        document.body.classList.add('sepay-modal-open');
        sepayModal.focus();
    };
    const closeSepayModal = () => {
        if (!sepayBackdrop || !sepayModal) return;
        sepayBackdrop.classList.remove('is-open');
        document.body.classList.remove('sepay-modal-open');
        document.querySelector('[data-sepay-open]')?.focus();
    };

    document.querySelectorAll('[data-sepay-close]').forEach(button => {
        button.addEventListener('click', closeSepayModal);
    });
    document.querySelectorAll('[data-sepay-open]').forEach(button => {
        button.addEventListener('click', openSepayModal);
    });
    if (sepayBackdrop) {
        sepayBackdrop.addEventListener('click', event => {
            if (event.target === sepayBackdrop) closeSepayModal();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && sepayBackdrop.classList.contains('is-open')) closeSepayModal();
        });
        document.body.classList.add('sepay-modal-open');
    }

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
            document.querySelectorAll('[data-method-card]').forEach(item => item.classList.remove('selected'));
            card.classList.add('selected');

            const method = card.querySelector('input[name="method"]');
            if (method) method.checked = true;

            const buttonText = document.getElementById('payButtonText');
            if (buttonText) {
                buttonText.textContent = method?.value === 'sepay_qr'
                    ? 'TẠO QR & THANH TOÁN'
                    : 'XÁC NHẬN THANH TOÁN TẠI QUẦY';
            }
        });
    });

    const form = document.getElementById('paymentForm');
    if (form) {
        form.addEventListener('submit', () => {
            const button = document.getElementById('payButton');
            if (!button) return;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
        });
    }

    if (pendingSepay) {
        const timer = setInterval(async () => {
            try {
                const response = await fetch(statusUrl, {
                    headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
                });
                if (!response.ok) return;
                const data = await response.json();

                const paymentText = document.getElementById('paymentStatusText');
                const bookingText = document.getElementById('bookingStatusLabel');

                if (paymentText) paymentText.textContent = String(data.payment_status || '').toUpperCase();
                if (bookingText) bookingText.textContent = String(data.booking_status || '').toUpperCase();

                if (data.success) {
                    clearInterval(timer);
                    window.location.href = successUrl;
                }
            } catch (e) {
                // Keep polling quietly; temporary network errors are harmless.
            }
        }, 3000);
    }
})();
</script>
@endsection
