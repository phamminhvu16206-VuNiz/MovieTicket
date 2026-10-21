<?php

namespace App\Http\Controllers;

use App\Mail\BookingInvoiceMail;
use App\Models\Booking;
use App\Models\BookingCombo;
use App\Models\BookingVoucher;
use App\Models\Combo;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function show($bookingId)
    {
        if ($this->checkAndCancelIfExpired($bookingId)) {
            return redirect()->route('home')->with('error', 'Đơn đặt vé đã hết thời gian giữ chỗ và bị hủy.');
        }

        $booking = Booking::with([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
            'bookingCombos.combo',
            'bookingVouchers.voucher',
            'payment',
        ])->where('user_id', Auth::id())->findOrFail($bookingId);

        if (in_array($booking->status, ['paid', 'completed'], true)) {
            return redirect()->route('booking.success', $booking->id);
        }

        if ($booking->status !== 'pending') {
            return redirect()->route('profile.bookings')->with('error', 'Đơn này không thể tiếp tục thanh toán.');
        }

        $combos = Combo::where('status', true)->orderBy('price')->get();
        $vouchers = Voucher::where('status', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->orderBy('id')
            ->get();

        $seatTotal = (float) $booking->bookingSeats->sum('price');
        $comboTotal = (float) $booking->bookingCombos->sum(
            fn ($item) => (float) $item->unit_price * (int) $item->quantity
        );
        $subtotal = $seatTotal + $comboTotal;

        $voucher = null;
        $discount = 0;
        $voucherId = session('payment_voucher_'.$booking->id);

        if ($voucherId) {
            $voucher = Voucher::find($voucherId);
            if ($voucher && $voucher->isValidFor($subtotal)) {
                $discount = $voucher->calculateDiscount($subtotal);
            } else {
                session()->forget('payment_voucher_'.$booking->id);
                $voucher = null;
            }
        }

        $total = max(0, $subtotal - $discount);

        // QR information is generated locally; no bank credential is exposed in the view.
        $bankAccount = config('services.sepay.bank_account');
        $bankName = config('services.sepay.bank_name');
        $accountName = config('services.sepay.account_name');
        $sepayEnabled = (bool) config('services.sepay.enabled', false)
            && $bankAccount
            && $bankName
            && config('services.sepay.api_key');

        $sepayQrUrl = null;
        if ($sepayEnabled && $booking->payment?->method === 'sepay_qr' && $booking->status === 'pending' && $total > 0) {
            $query = http_build_query([
                'acc' => $bankAccount,
                'bank' => $bankName,
                'amount' => (int) round($total),
                'des' => $booking->booking_code,
            ]);
            $sepayQrUrl = 'https://qr.sepay.vn/img?'.$query;
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

        if (! $voucher || ! $voucher->isValidFor($subtotal)) {
            return back()->with('error', 'Voucher không hợp lệ, hết hạn hoặc chưa đủ điều kiện.');
        }

        session(['payment_voucher_'.$booking->id => $voucher->id]);

        return back()->with('success', 'Đã áp dụng voucher '.$voucher->code.'.');
    }

    public function process(Request $request, $bookingId)
    {
        if ($this->checkAndCancelIfExpired($bookingId)) {
            return redirect()->route('home')->with('error', 'Giao dịch thất bại! Đã quá 3 phút giữ chỗ.');
        }

        $data = $request->validate([
            'method' => 'required|in:sepay_qr,cash',
            'combo_qty' => 'nullable|array',
            'combo_qty.*' => 'nullable|integer|min:0|max:20',
        ]);

        $booking = Booking::with(['showtime', 'bookingSeats'])
            ->where('user_id', Auth::id())
            ->findOrFail($bookingId);

        if (in_array($booking->status, ['paid', 'completed'], true)) {
            return redirect()->route('booking.success', $booking->id);
        }

        if ($booking->status !== 'pending') {
            return redirect()->route('profile.bookings')->with('error', 'Đơn này không còn chờ thanh toán.');
        }

        $method = $data['method'];
        if ($method === 'sepay_qr' && (
            ! config('services.sepay.enabled')
            || ! config('services.sepay.bank_account')
            || ! config('services.sepay.bank_name')
            || ! config('services.sepay.api_key')
        )) {
            return back()->with('error', 'Thanh toán SePay chưa được cấu hình đầy đủ.');
        }

        $payment = null;

        DB::transaction(function () use ($data, $booking, $method, &$payment) {
            $seatTotal = (float) $booking->bookingSeats->sum('price');
            $comboTotal = 0;

            $booking->bookingCombos()->delete();

            $quantities = $data['combo_qty'] ?? [];
            if ($quantities) {
                $ids = array_map('intval', array_keys($quantities));
                $combos = Combo::whereIn('id', $ids)->where('status', true)->get()->keyBy('id');

                foreach ($quantities as $id => $quantity) {
                    $quantity = (int) $quantity;
                    if ($quantity < 1 || ! isset($combos[(int) $id])) {
                        continue;
                    }

                    $combo = $combos[(int) $id];
                    $price = (float) $combo->price;
                    $comboTotal += $price * $quantity;

                    BookingCombo::create([
                        'booking_id' => $booking->id,
                        'combo_id' => $combo->id,
                        'quantity' => $quantity,
                        'unit_price' => $price,
                    ]);
                }
            }

            $subtotal = $seatTotal + $comboTotal;
            $voucherId = session('payment_voucher_'.$booking->id);
            $discount = 0;
            $voucher = null;
            $booking->bookingVouchers()->delete();

            if ($voucherId) {
                $voucher = Voucher::find($voucherId);
                if ($voucher && $voucher->isValidFor($subtotal)) {
                    $discount = $voucher->calculateDiscount($subtotal);
                } else {
                    $voucher = null;
                }
            }

            if ($voucher) {
                BookingVoucher::updateOrCreate(
                    ['booking_id' => $booking->id, 'voucher_id' => $voucher->id],
                    ['discount_amount' => $discount]
                );
            }

            $total = max(0, $subtotal - $discount);
            $booking->update([
                'total_amount' => $total,
                'expires_at' => $method === 'sepay_qr' ? $booking->expires_at : null,
            ]);

            $payment = Payment::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'payment_code' => 'PAY'.strtoupper(Str::random(10)),
                    'method' => $method,
                    'amount' => $total,
                    'status' => $method === 'sepay_qr' ? 'pending' : 'success',
                    'paid_at' => $method === 'sepay_qr' ? null : now(),
                    'transaction_ref' => null,
                ]
            );

            // Only real webhook confirmation is allowed to mark a SePay payment as paid.
            if ($method !== 'sepay_qr') {
                $payment->update([
                    'transaction_ref' => 'SIM-'.strtoupper(Str::random(10)),
                    'status' => 'success',
                    'paid_at' => now(),
                ]);
                if ($voucher) {
                    $voucher->increment('used_count');
                }
                $booking->update(['status' => 'paid']);
            }
        });

        if ($method === 'sepay_qr') {
            return redirect()
                ->route('payment.checkout', $booking->id)
                ->with('success', 'Đã tạo yêu cầu thanh toán. Hãy quét QR và chờ hệ thống xác nhận.');
        }

        $paidBooking = $booking->fresh([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
            'user',
            'payment',
        ]);
        Mail::to($paidBooking->user->email)->queue(new BookingInvoiceMail($paidBooking));

        session()->forget('payment_voucher_'.$booking->id);

        return redirect()
            ->route('booking.success', $booking->id)
            ->with('success', 'Thanh toán mô phỏng thành công! Vé đã được xác nhận.');
    }

    public function checkStatus($bookingId)
    {
        $this->checkAndCancelIfExpired($bookingId);

        $booking = Booking::with('payment')
            ->where('user_id', Auth::id())
            ->findOrFail($bookingId);

        return response()->json([
            'booking_status' => $booking->status,
            'payment_status' => $booking->payment?->status,
            'payment_method' => $booking->payment?->method,
            'transaction_ref' => $booking->payment?->transaction_ref,
            'success' => $booking->status === 'paid' && $booking->payment?->status === 'success',
        ]);
    }

    public function cancel($bookingId)
    {
        $booking = Booking::where('user_id', Auth::id())
            ->findOrFail($bookingId);

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Đơn này không còn ở trạng thái chờ thanh toán.');
        }

        $deleted = DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->first();

            if (! $booking || $booking->status !== 'pending') {
                return false;
            }

            $booking->delete();

            return true;
        });

        if (! $deleted) {
            return back()->with('error', 'Đơn này không còn ở trạng thái chờ thanh toán.');
        }

        session()->forget('payment_voucher_'.$booking->id);

        return redirect()->route('profile.bookings')->with('success', 'Đã hủy đơn thanh toán.');
    }

    private function checkAndCancelIfExpired($bookingId): bool
    {
        $deleted = DB::transaction(function () use ($bookingId) {
            $booking = Booking::where('user_id', Auth::id())
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->first();

            if (! $booking || $booking->status !== 'pending') {
                return false;
            }

            $expired = $booking->expires_at
                ? now()->greaterThanOrEqualTo($booking->expires_at)
                : $booking->created_at->lte(now()->subMinutes(3));

            if (! $expired) {
                return false;
            }

            $booking->delete();

            return true;
        });

        if (! $deleted) {
            return false;
        }

        session()->forget('payment_voucher_'.$bookingId);

        return true;
    }

    private function subtotal($booking): float
    {
        return (float) $booking->bookingSeats->sum('price')
            + (float) $booking->bookingCombos->sum(
                fn ($combo) => (float) $combo->unit_price * (int) $combo->quantity
            );
    }
}
