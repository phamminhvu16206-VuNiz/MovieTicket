<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingCombo;
use App\Models\BookingVoucher;
use App\Models\Combo;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function show($bookingId)
    {
        // Tự động giải phóng các đơn hết hạn trước khi kiểm tra
        $this->checkAndCancelIfExpired($bookingId);

        $booking = Booking::with([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
            'bookingCombos.combo',
            'bookingVouchers.voucher',
            'payment',
        ])->where('user_id', Auth::id())->findOrFail($bookingId);

        // Nếu đơn đã bị hủy (do quá 3 phút hoặc bấm hủy), từ chối truy cập
        if ($booking->status === 'cancelled') {
            return redirect()->route('home')->with('error', 'Đơn đặt vé đã hết thời gian giữ chỗ (3 phút) và bị hủy.');
        }

        $combos = Combo::where('status', true)->orderBy('price')->get();
        $vouchers = Voucher::where('status', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->orderBy('id')
            ->get();

        $seatTotal = (float) $booking->bookingSeats->sum('price');
        $comboTotal = (float) $booking->bookingCombos->sum(
            fn($item) => (float) $item->unit_price * (int) $item->quantity
        );
        $subtotal = $seatTotal + $comboTotal;

        $voucher = null;
        $discount = 0;
        $voucherId = session('payment_voucher_' . $booking->id);

        if ($voucherId) {
            $voucher = Voucher::find($voucherId);
            if ($voucher && $voucher->isValidFor($subtotal)) {
                $discount = $voucher->calculateDiscount($subtotal);
            } else {
                session()->forget('payment_voucher_' . $booking->id);
                $voucher = null;
            }
        }

        $total = max(0, $subtotal - $discount);

        $sepayEnabled = (bool) config('services.sepay.enabled', true);
        $bankAccount  = config('services.sepay.bank_account');
        $bankName     = config('services.sepay.bank_name');
        $accountName  = config('services.sepay.account_name');

        // Chỉ tạo QR nếu phương thức là sepay_qr VÀ đơn vẫn chưa hết hạn
        $sepayQrUrl = null;
        if ($booking->payment && $booking->payment->method === 'sepay_qr' && $bankAccount && $bankName && $total > 0 && $booking->status === 'pending') {
            $query = http_build_query([
                'acc'    => $bankAccount,
                'bank'   => $bankName,
                'amount' => (int) round($total),
                'des'    => $booking->booking_code,
            ]);
            $sepayQrUrl = 'https://qr.sepay.vn/img?' . $query;
        }

        return view('payments.checkout', compact(
            'booking',
            'combos',
            'vouchers',
            'seatTotal',
            'comboTotal',
            'subtotal',
            'voucher',
            'discount',
            'total',
            'sepayEnabled',
            'bankAccount',
            'bankName',
            'accountName',
            'sepayQrUrl'
        ));
    }

    public function process(Request $request, $bookingId)
    {
        // Kiểm tra hết hạn 3 phút trước khi xử lý
        if ($this->checkAndCancelIfExpired($bookingId)) {
            return redirect()->route('home')->with('error', 'Giao dịch thất bại! Đã quá 3 phút giữ chỗ.');
        }

        $data = $request->validate([
            'method' => 'required|in:sepay_qr,qr,card,cash',
            'combo_qty' => 'nullable|array',
            'combo_qty.*' => 'nullable|integer|min:0|max:20',
        ]);

        $booking = Booking::with(['showtime', 'bookingSeats'])
            ->where('user_id', Auth::id())
            ->findOrFail($bookingId);

        if ($booking->status === 'paid') {
            return redirect()->route('booking.success', $booking->id);
        }

        $method = $data['method'];

        DB::transaction(function () use ($data, $booking, $method) {
            $seatTotal = (float) $booking->bookingSeats->sum('price');
            $comboTotal = 0;

            $booking->bookingCombos()->delete();

            $quantities = $data['combo_qty'] ?? [];
            if ($quantities) {
                $ids = array_map('intval', array_keys($quantities));
                $combos = Combo::whereIn('id', $ids)->where('status', true)->get()->keyBy('id');

                foreach ($quantities as $id => $quantity) {
                    $quantity = (int) $quantity;
                    if ($quantity < 1 || !isset($combos[(int) $id])) {
                        continue;
                    }

                    $combo = $combos[(int) $id];
                    $price = (float) $combo->price;
                    $comboTotal += $price * $quantity;

                    BookingCombo::create([
                        'booking_id' => $booking->id,
                        'combo_id'   => $combo->id,
                        'quantity'   => $quantity,
                        'unit_price' => $price,
                    ]);
                }
            }

            $subtotal = $seatTotal + $comboTotal;
            $voucherId = session('payment_voucher_' . $booking->id);
            $discount = 0;
            $voucher = null;

            if ($voucherId) {
                $voucher = Voucher::find($voucherId);
                if ($voucher && $voucher->isValidFor($subtotal)) {
                    $discount = $voucher->calculateDiscount($subtotal);
                }
            }

            if ($voucher) {
                BookingVoucher::updateOrCreate(
                    ['booking_id' => $booking->id, 'voucher_id' => $voucher->id],
                    ['discount_amount' => $discount]
                );
            }

            $total = max(0, $subtotal - $discount);

            // Nếu không phải sepay_qr (Thanh toán xong ngay), xóa thời gian đếm ngược
            $bookingUpdateData = ['total_amount' => $total];
            if ($method !== 'sepay_qr') {
                $bookingUpdateData['status'] = 'paid';
                $bookingUpdateData['expires_at'] = null; // CHÍNH THỨC GIỮ CHỖ VĨNH VIỄN
            }

            $booking->update($bookingUpdateData);

            Payment::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'payment_code'    => 'PAY' . strtoupper(Str::random(10)),
                    'method'          => $method,
                    'amount'          => $total,
                    'status'          => $method === 'sepay_qr' ? 'pending' : 'success',
                    'paid_at'          => $method === 'sepay_qr' ? null : now(),
                    'transaction_ref' => $method === 'sepay_qr' ? null : 'SIM-' . strtoupper(Str::random(10)),
                ]
            );

            if ($method !== 'sepay_qr' && $voucher) {
                $voucher->increment('used_count');
            }
        });

        if ($method === 'sepay_qr') {
            return redirect()
                ->route('payment.checkout', $booking->id)
                ->with('success', 'Mã QR thanh toán đã khởi tạo! Vui lòng quét mã bên dưới.');
        }

        session()->forget('payment_voucher_' . $booking->id);

        return redirect()
            ->route('booking.success', $booking->id)
            ->with('success', 'Thanh toán thành công! Vé của bạn đã được giữ chính thức.');
    }

    public function checkStatus($bookingId)
    {
        // Tự kiểm tra xem quá 3 phút chưa
        $this->checkAndCancelIfExpired($bookingId);

        $booking = Booking::with('payment')
            ->where('user_id', Auth::id())
            ->findOrFail($bookingId);

        return response()->json([
            'booking_status'  => $booking->status,
            'payment_status'  => $booking->payment?->status,
            'payment_method'  => $booking->payment?->method,
            'transaction_ref' => $booking->payment?->transaction_ref,
            'is_expired'      => $booking->status === 'cancelled',
            'success'         => $booking->status === 'paid' && $booking->payment?->status === 'success',
        ]);
    }

    /**
     * Hàm hỗ trợ kiểm tra và hủy đơn nếu đã quá 3 phút mà chưa thanh toán xong
     */
    private function checkAndCancelIfExpired($bookingId): bool
    {
        $booking = Booking::find($bookingId);

        if ($booking && $booking->status === 'pending' && $booking->expires_at && now()->greaterThan($booking->expires_at)) {
            DB::transaction(function () use ($booking) {
                $booking->update([
                    'status' => 'cancelled',
                    'expires_at' => null
                ]);

                if ($booking->payment) {
                    $booking->payment->update(['status' => 'failed']);
                }
            });

            session()->forget('payment_voucher_' . $booking->id);
            return true; // Đã bị hủy do hết hạn
        }

        return false;
    }

    public function applyVoucher(Request $request, $bookingId)
    {
        if ($this->checkAndCancelIfExpired($bookingId)) {
            return redirect()->route('home')->with('error', 'Đơn đặt vé đã hết hạn.');
        }

        $request->validate(['code' => 'required|string|max:50']);

        $booking = Booking::with(['bookingSeats', 'bookingCombos'])
            ->where('user_id', Auth::id())
            ->findOrFail($bookingId);

        abort_if($booking->status !== 'pending', 404);

        $voucher = Voucher::whereRaw('UPPER(code) = ?', [strtoupper(trim($request->code))])->first();
        $subtotal = $this->subtotal($booking);

        if (!$voucher || !$voucher->isValidFor($subtotal)) {
            return back()->with('error', 'Voucher không hợp lệ, hết hạn hoặc chưa đủ điều kiện.');
        }

        session(['payment_voucher_' . $booking->id => $voucher->id]);

        return back()->with('success', 'Đã áp dụng voucher ' . $voucher->code . '.');
    }

    public function cancel($bookingId)
    {
        $booking = Booking::with('payment')
            ->where('user_id', Auth::id())
            ->findOrFail($bookingId);

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Đơn này không còn ở trạng thái chờ thanh toán.');
        }

        DB::transaction(function () use ($booking) {
            if ($booking->payment) {
                $booking->payment->update(['status' => 'failed']);
            }
            $booking->update([
                'status' => 'cancelled',
                'expires_at' => null
            ]);
        });

        session()->forget('payment_voucher_' . $booking->id);

        return redirect()->route('profile.bookings')->with('success', 'Đã hủy đơn thanh toán.');
    }

    private function subtotal($booking): float
    {
        return (float) $booking->bookingSeats->sum('price')
            + (float) $booking->bookingCombos->sum(
                fn($combo) => (float) $combo->unit_price * (int) $combo->quantity
            );
    }
}
