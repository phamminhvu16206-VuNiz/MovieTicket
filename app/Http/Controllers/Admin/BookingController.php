<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['user', 'showtime.movie', 'bookingSeats.seat'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.bookings.index', compact('bookings'));
    }

    /**
     * Cập nhật trạng thái đơn vé từ dropdown Admin (AJAX)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,refund_pending,refunded,cancelled'
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update(['status' => $request->status]);

        if ($booking->payment) {
            $booking->payment->update(['status' => $request->status]);
        }

        // Nếu chuyển sang Đã hủy hoặc Đã hoàn tiền -> giải phóng ghế
        if (in_array($request->status, ['cancelled', 'refund_pending', 'refunded'])) {
            \DB::table('booking_seats')->where('booking_id', $booking->id)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái vé thành công!'
        ]);
    }
}
