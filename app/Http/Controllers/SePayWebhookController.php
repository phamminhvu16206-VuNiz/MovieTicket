<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Mail\TicketEmailed;
use Illuminate\Support\Facades\Mail;

class SepayWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // 1. Phản hồi nhanh nếu không có dữ liệu
        $content = strtoupper(trim((string) $request->input('content', '')));
        $transferAmount = (float) $request->input('transferAmount', 0);
        $referenceCode = $request->input('referenceCode');

        if (empty($content)) {
            return response()->json(['success' => false, 'message' => 'Empty content'], 200);
        }

        Log::info('SePay Webhook Received:', $request->all());

        // 2. Tìm nhanh các đơn đang pending
        $pendingBookings = Booking::where('status', 'pending')->get();
        $matchedBooking = null;

        foreach ($pendingBookings as $booking) {
            $code = strtoupper($booking->booking_code);
            $shortCode = substr($code, 0, 8); // Bóc tách chuỗi để tránh bị cắt bớt từ ngân hàng

            if (str_contains($content, $code) || str_contains($content, $shortCode)) {
                $matchedBooking = $booking;
                break;
            }
        }

        if (!$matchedBooking) {
            Log::warning("SePay Webhook: Không khớp đơn nào cho nội dung: {$content}");
            return response()->json(['success' => false, 'message' => 'Booking not found'], 200);
        }

        // 3. Cập nhật DB & Gửi Email vé điện tử
        try {
            DB::transaction(function () use ($matchedBooking, $transferAmount, $referenceCode) {
                // Đổi trạng thái -> paid & xóa hạn 3 phút (GIỮ CHỖ VĨNH VIỄN)
                $matchedBooking->update([
                    'status'     => 'paid',
                    'expires_at' => null,
                ]);

                Payment::updateOrCreate(
                    ['booking_id' => $matchedBooking->id],
                    [
                        'payment_code'    => 'PAY' . strtoupper(Str::random(10)),
                        'method'          => 'sepay_qr',
                        'amount'          => $transferAmount ?: $matchedBooking->total_amount,
                        'status'          => 'success',
                        'paid_at'          => now(),
                        'transaction_ref' => $referenceCode ?? 'SEPAY-' . now()->timestamp,
                    ]
                );

                // Gửi email vé điện tử đến email của người đặt vé
                if ($matchedBooking->user && $matchedBooking->user->email) {
                    try {
                        Mail::to($matchedBooking->user->email)->send(new TicketEmailed($matchedBooking));
                    } catch (\Exception $e) {
                        Log::error("Send Ticket Mail Error: " . $e->getMessage());
                    }
                }
            });
        } catch (\Exception $e) {
            Log::error("SePay Webhook DB Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Database update error'], 500);
        }

        // 4. Trả về JSON 200 cho SePay sau khi đã hoàn tất xử lý
        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully'
        ], 200);
    }
}
