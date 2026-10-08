<?php

namespace App\Http\Controllers;

use App\Mail\BookingInvoiceMail;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Seat;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function selectSeat($showtimeId)
    {
        $showtime = Showtime::with(['movie', 'room.cinema', 'room.seats'])->findOrFail($showtimeId);
        abort_unless($showtime->status === 'active' && $showtime->start_time->isFuture(), 404);

        $bookedSeatIds = BookingSeat::whereHas('booking', fn($q) => $q->where('showtime_id', $showtimeId)->whereIn('status', ['paid', 'completed', 'pending']))
            ->pluck('seat_id')
            ->toArray();

        $seatsByRow = $showtime->room->seats->sortBy(['row', 'column'])->groupBy('row');

        return view('bookings.select_seat', compact('showtime', 'seatsByRow', 'bookedSeatIds'));
    }

    public function processBooking(Request $request, $showtimeId)
    {
        $request->validate([
            'seat_ids'   => 'required|array|min:1|max:10',
            'seat_ids.*' => 'integer|distinct'
        ]);

        $showtime = Showtime::with('room')->findOrFail($showtimeId);
        abort_unless($showtime->status === 'active' && $showtime->start_time->isFuture(), 404);

        $seatIds = array_map('intval', $request->seat_ids);

        DB::beginTransaction();
        try {
            $valid = Seat::where('room_id', $showtime->room_id)->whereIn('id', $seatIds)->count() === count($seatIds);
            if (!$valid) {
                throw new \RuntimeException('Ghế không thuộc phòng chiếu này.');
            }

            $alreadyBooked = BookingSeat::whereHas('booking', fn($q) => $q->where('showtime_id', $showtimeId)->whereIn('status', ['paid', 'completed', 'pending']))
                ->whereIn('seat_id', $seatIds)
                ->exists();

            if ($alreadyBooked) {
                throw new \RuntimeException('Một số ghế vừa được người khác đặt.');
            }

            $booking = Booking::create([
                'user_id'      => Auth::id(),
                'showtime_id'  => $showtime->id,
                'booking_code' => 'MVT' . strtoupper(Str::random(8)),
                'total_amount' => count($seatIds) * $showtime->price,
                'status'       => 'pending'
            ]);

            foreach ($seatIds as $seatId) {
                BookingSeat::create([
                    'booking_id' => $booking->id,
                    'seat_id'    => $seatId,
                    'price'      => $showtime->price
                ]);
            }

            DB::commit();

            try {
                if (false && config('mail.mailer') && config('mail.host')) {
                    Mail::to(Auth::user()->email)->send(new BookingInvoiceMail($booking->load(['showtime.movie', 'showtime.room.cinema', 'bookingSeats.seat', 'user', 'payment'])));
                }
            } catch (\Throwable $mailError) {
                // Ignore mail errors on local/testing
            }

            // Chuyển hướng chính xác sang trang hiển thị thông tin & QR thanh toán V3
            return redirect()->route('payment.checkout', ['bookingId' => $booking->id])
                ->with('success', 'Đã giữ ghế. Vui lòng hoàn tất thanh toán để xác nhận vé.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage() ?: 'Không thể đặt vé, vui lòng thử lại.');
        }
    }

    public function showSuccess($bookingId)
    {
        $booking = Booking::with(['showtime.movie', 'showtime.room.cinema', 'bookingSeats.seat', 'user', 'payment'])
            ->where('user_id', Auth::id())
            ->findOrFail($bookingId);

        return view('bookings.success', compact('booking'));
    }

    public function history()
    {
        $bookings = Booking::with(['showtime.movie', 'showtime.room.cinema', 'bookingSeats.seat'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(6);

        return view('profile.bookings', compact('bookings'));
    }

    public function cancel(Request $request, $bookingId)
    {
        $booking = Booking::with('showtime')
            ->where('user_id', Auth::id())
            ->findOrFail($bookingId);

        if (!in_array($booking->status, ['paid', 'pending']) || !$booking->showtime->start_time->isFuture()) {
            return back()->with('error', 'Vé này không thể hủy ở thời điểm hiện tại.');
        }

        $booking->update(['status' => 'cancelled']);
        return back()->with('success', 'Đã hủy vé thành công.');
    }
}
