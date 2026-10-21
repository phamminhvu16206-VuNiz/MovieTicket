<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function index()
    {
        $refundRequests = DB::table('refund_requests')
            ->join('bookings', 'refund_requests.booking_id', '=', 'bookings.id')
            ->join('users', 'refund_requests.user_id', '=', 'users.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->join('movies', 'showtimes.movie_id', '=', 'movies.id')
            ->select(
                'refund_requests.*',
                'bookings.booking_code',
                'showtimes.start_time',
                'movies.title as movie_title',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->orderBy('refund_requests.created_at', 'desc')
            ->paginate(15);

        return view('admin.refunds.index', compact('refundRequests'));
    }

    /**
     * Admin xác nhận đã chuyển khoản -> Cập nhật trạng thái vé thành refunded
     */
    public function approve($id)
    {
        $approved = DB::transaction(function () use ($id) {
            $refundRequest = DB::table('refund_requests')
                ->where('id', $id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $refundRequest) {
                return false;
            }

            $booking = Booking::whereKey($refundRequest->booking_id)
                ->where('status', 'refund_pending')
                ->lockForUpdate()
                ->first();

            if (! $booking) {
                return false;
            }

            $paymentUpdated = DB::table('payments')
                ->where('booking_id', $booking->id)
                ->where('status', 'refund_pending')
                ->update(['status' => 'refunded', 'updated_at' => now()]);

            if ($paymentUpdated !== 1) {
                return false;
            }

            DB::table('refund_requests')
                ->where('id', $refundRequest->id)
                ->update(['status' => 'completed', 'updated_at' => now()]);

            $booking->update(['status' => 'refunded']);

            return true;
        });

        if (! $approved) {
            return back()->with('error', 'Yêu cầu hoàn tiền không tồn tại hoặc đã được xử lý.');
        }

        return back()->with('success', 'Đã xác nhận chuyển khoản hoàn tiền. Vé được cập nhật thành đã hủy, hoàn tiền.');
    }
}
