<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SePayWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        if (!config('services.sepay.enabled', false)) {
            return response()->json(['status' => 'disabled'], 503);
        }

        $expectedKey = (string) config('services.sepay.api_key');
        $authorization = (string) $request->header('Authorization');

        if ($expectedKey !== '' && !hash_equals('Apikey '.$expectedKey, $authorization)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $content = (string) ($request->input('content') ?? $request->input('description') ?? '');
        $transferAmount = (float) (
            $request->input('transferAmount')
            ?? $request->input('amount')
            ?? $request->input('transfer_amount')
            ?? 0
        );
        $reference = (string) (
            $request->input('referenceCode')
            ?? $request->input('reference')
            ?? $request->input('id')
            ?? ''
        );

        Log::info('SePay webhook received', [
            'content' => $content,
            'amount' => $transferAmount,
            'reference' => $reference,
        ]);

        if (!preg_match('/\b(MVT[A-Z0-9]{8})\b/i', $content, $matches)) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'Không tìm thấy mã booking trong nội dung chuyển khoản.',
            ]);
        }

        $bookingCode = strtoupper($matches[1]);

        $booking = Booking::with(['payment', 'bookingVouchers'])
            ->where('booking_code', $bookingCode)
            ->where('status', 'pending')
            ->first();

        if (!$booking) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'Booking không tồn tại hoặc đã được xử lý.',
            ]);
        }

        $expectedAmount = (float) $booking->total_amount;

        if ($transferAmount < $expectedAmount) {
            Log::warning('SePay transfer amount is insufficient', [
                'booking' => $bookingCode,
                'expected' => $expectedAmount,
                'received' => $transferAmount,
            ]);

            return response()->json([
                'status' => 'ignored',
                'message' => 'Số tiền chuyển khoản chưa đủ.',
            ]);
        }

        DB::transaction(function () use ($booking, $transferAmount, $reference) {
            $payment = $booking->payment ?: new Payment(['booking_id' => $booking->id]);

            $payment->fill([
                'payment_code' => $payment->payment_code ?: 'PAY'.strtoupper(str()->random(10)),
                'method' => 'sepay_qr',
                'amount' => $booking->total_amount,
                'status' => 'success',
                'paid_at' => now(),
                'transaction_ref' => $reference ?: 'SEPAY-'.now()->format('YmdHis'),
            ]);
            $payment->save();

            $booking->update(['status' => 'paid']);

            foreach ($booking->bookingVouchers as $bookingVoucher) {
                if ($bookingVoucher->voucher_id) {
                    Voucher::whereKey($bookingVoucher->voucher_id)->increment('used_count');
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Đã xác nhận thanh toán.',
            'booking_code' => $bookingCode,
        ]);
    }
}
