<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingActionController extends Controller
{
    /**
     * Hủy vé đã thanh toán (KHÔNG HOÀN TIỀN)
     */
    public function cancelPaidBooking(Request $request, $id)
    {
        $booking = Booking::where('user_id', Auth::id())
            ->where('status', 'paid')
            ->findOrFail($id);

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'cancelled',
            ]);

            if ($booking->payment) {
                $booking->payment->update([
                    'status' => 'failed'
                ]);
            }

            // Giải phóng ghế đặt
            DB::table('booking_seats')->where('booking_id', $booking->id)->delete();
        });

        return back()->with('success', 'Đã hủy vé thành công. Vé này không được hoàn tiền theo quy định.');
    }

    /**
     * Hoàn vé trả lại tiền (Chỉ áp dụng trước giờ chiếu 24h)
     */
    public function refundBooking(Request $request, $id)
    {
        $data = $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:150',
            'reason'         => 'nullable|string|max:500',
        ], [
            'bank_name.required'      => 'Vui lòng chọn ngân hàng nhận tiền.',
            'account_number.required' => 'Vui lòng nhập số tài khoản.',
            'account_name.required'   => 'Vui lòng nhập tên chủ tài khoản.',
        ]);

        $booking = Booking::with('showtime')
            ->where('user_id', Auth::id())
            ->where('status', 'paid')
            ->findOrFail($id);

        $showtimeStart = Carbon::parse($booking->showtime->start_time);
        if (Carbon::now()->diffInHours($showtimeStart, false) < 24) {
            return back()->with('error', 'Không thể hoàn vé! Chỉ cho phép hoàn tiền trước suất chiếu ít nhất 24 giờ.');
        }

        DB::transaction(function () use ($booking, $data) {
            // 1. Chuyển trạng thái sang refund_pending (Chờ hoàn tiền)
            $booking->update([
                'status' => 'refund_pending',
            ]);

            if ($booking->payment) {
                $booking->payment->update([
                    'status' => 'refund_pending'
                ]);
            }

            // 2. GIẢI PHÓNG GHẾ NGAY LẬP TỨC để người khác có thể đặt
            DB::table('booking_seats')->where('booking_id', $booking->id)->delete();

            // 3. Tạo bản ghi trong bảng refund_requests
            DB::table('refund_requests')->insert([
                'booking_id'     => $booking->id,
                'user_id'        => Auth::id(),
                'amount'         => $booking->total_amount,
                'bank_name'      => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_name'   => strtoupper(trim($data['account_name'])),
                'reason'         => $data['reason'] ?? null,
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });

        return back()->with('success', 'Yêu cầu hoàn vé đã được gửi! Ghế của bạn đã được giải phóng và số tiền sẽ được hoàn về tài khoản sau khi Admin duyệt.');
    }
}
