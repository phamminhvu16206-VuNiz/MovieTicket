<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingActionController extends Controller
{
    /**
     * Hủy vé đã thanh toán (KHÔNG HOÀN TIỀN)
     */
    public function cancelPaidBooking(Request $request, $id)
    {
        $cancelled = DB::transaction(function () use ($id) {
            $booking = Booking::with('showtime')
                ->where('user_id', Auth::id())
                ->where('status', 'paid')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($booking->showtime->start_time->lte(now()->addHours(24))) {
                return false;
            }

            $booking->update(['status' => 'cancelled']);

            return true;
        });

        if (! $cancelled) {
            return back()->with('error', 'Không thể hủy vé trong vòng 24 giờ trước giờ chiếu.');
        }

        return back()->with('success', 'Đã hủy vé. Thông tin đơn và thanh toán vẫn được lưu trong lịch sử.');
    }

    /**
     * Hoàn vé trả lại tiền (Chỉ áp dụng trước giờ chiếu 24h)
     */
    public function refundBooking(Request $request, $id)
    {
        $data = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:150',
            'reason' => 'required|string|max:500',
        ], [
            'bank_name.required' => 'Vui lòng chọn ngân hàng nhận tiền.',
            'account_number.required' => 'Vui lòng nhập số tài khoản.',
            'account_name.required' => 'Vui lòng nhập tên chủ tài khoản.',
        ]);

        $refundRequested = DB::transaction(function () use ($id, $data) {
            $booking = Booking::with('showtime')
                ->where('user_id', Auth::id())
                ->where('status', 'paid')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($booking->showtime->start_time->lte(now()->addHours(24))) {
                return false;
            }

            $booking->update([
                'status' => 'refund_pending',
            ]);

            if ($booking->payment) {
                $booking->payment->update([
                    'status' => 'refund_pending',
                ]);
            }

            DB::table('refund_requests')->insert([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'amount' => $booking->total_amount,
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_name' => strtoupper(trim($data['account_name'])),
                'reason' => trim($data['reason']),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });

        if (! $refundRequested) {
            return back()->with('error', 'Không thể hủy/hoàn vé trong vòng 24 giờ trước giờ chiếu.');
        }

        return back()->with('success', 'Yêu cầu hoàn tiền đã được gửi. Đơn đặt vé vẫn được lưu trong lịch sử và ghế đã được giải phóng.');
    }
}
