<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'status' => 'required|in:pending,paid,completed,refund_pending,refunded,cancelled',
        ]);

        $booking = Booking::findOrFail($id);
        $status = $request->string('status')->toString();

        $paymentStatus = match ($status) {
            'paid' => 'success',
            'cancelled' => in_array($booking->payment?->status, ['success', 'refund_pending', 'refunded'], true)
                ? $booking->payment->status
                : 'failed',
            default => $status,
        };

        DB::transaction(function () use ($booking, $status, $paymentStatus) {
            $booking->update(['status' => $status]);

            if ($booking->payment) {
                $booking->payment->update(['status' => $paymentStatus]);
            }
        });

        if (! $request->expectsJson()) {
            return back()->with('success', 'Cập nhật trạng thái vé thành công!');
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái vé thành công!',
        ]);
    }
}
