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
        $bookedSeatIds = BookingSeat::whereHas('booking', fn ($q) => $q->where('showtime_id', $showtimeId)->holdingSeats())->pluck('seat_id')->toArray();
        $seatsByRow = $showtime->room->seats->sortBy(['row', 'column'])->groupBy('row');

        return view('bookings.select_seat', compact('showtime', 'seatsByRow', 'bookedSeatIds'));
    }

    public function processBooking(Request $request, $showtimeId)
    {
        $request->validate(['seat_ids' => 'required|array|min:1|max:10', 'seat_ids.*' => 'integer|distinct']);
        $showtime = Showtime::with('room')->findOrFail($showtimeId);
        abort_unless($showtime->status === 'active' && $showtime->start_time->isFuture(), 404);
        $seatIds = array_map('intval', $request->seat_ids);
        DB::beginTransaction();
        try {
            Booking::expiredPending()->where('showtime_id', $showtimeId)->delete();
            $valid = Seat::where('room_id', $showtime->room_id)->whereIn('id', $seatIds)->count() === count($seatIds);
            if (! $valid) {
                throw new \RuntimeException('Ghế không thuộc phòng chiếu này.');
            }
            $alreadyBooked = BookingSeat::whereHas('booking', fn ($q) => $q->where('showtime_id', $showtimeId)->holdingSeats())->whereIn('seat_id', $seatIds)->exists();
            if ($alreadyBooked) {
                throw new \RuntimeException('Một số ghế vừa được người khác đặt.');
            }
            $booking = Booking::create(['user_id' => Auth::id(), 'showtime_id' => $showtime->id, 'booking_code' => 'MVT'.strtoupper(Str::random(8)), 'total_amount' => count($seatIds) * $showtime->price, 'status' => 'pending', 'expires_at' => now()->addMinutes(3)]);
            foreach ($seatIds as $seatId) {
                BookingSeat::create(['booking_id' => $booking->id, 'seat_id' => $seatId, 'price' => $showtime->price]);
            }
            DB::commit();
            try {
                if (false && config('mail.mailer') && config('mail.host')) {
                    Mail::to(Auth::user()->email)->send(new BookingInvoiceMail($booking->load(['showtime.movie', 'showtime.room.cinema', 'bookingSeats.seat', 'user', 'payment'])));
                }
            } catch (\Throwable $mailError) {
            }

            return redirect()->route('payment.checkout', $booking->id)->with('success', 'Đã giữ ghế. Vui lòng hoàn tất thanh toán để xác nhận vé.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage() ?: 'Không thể đặt vé, vui lòng thử lại.');
        }
    }

    public function showSuccess($bookingId)
    {
        $booking = Booking::with(['showtime.movie', 'showtime.room.cinema', 'bookingSeats.seat', 'user', 'payment'])->where('user_id', Auth::id())->whereIn('status', ['paid', 'completed'])->findOrFail($bookingId);

        return view('bookings.success', compact('booking'));
    }

    public function history()
    {
        $bookings = Booking::with(['showtime.movie', 'showtime.room.cinema', 'bookingSeats.seat', 'payment'])
            ->where('user_id', Auth::id())
            ->where(function ($query) {
                $query->whereIn('status', ['paid', 'completed', 'refund_pending', 'refunded'])
                    ->orWhere(function ($query) {
                        $query->where('status', 'pending')
                            ->whereHas('payment', fn ($paymentQuery) => $paymentQuery->where('status', 'success'));
                    })
                    ->orWhere(function ($query) {
                        $query->where('status', 'cancelled')
                            ->whereHas('payment', fn ($paymentQuery) => $paymentQuery->where('status', 'success'));
                    });
            })
            ->latest()
            ->paginate(6);

        return view('profile.bookings', compact('bookings'));
    }

    public function cancel(Request $request, $bookingId)
    {
        $booking = Booking::with('showtime')->where('user_id', Auth::id())->findOrFail($bookingId);
        if ($booking->status !== 'pending' || ! $booking->showtime->start_time->isFuture()) {
            return back()->with('error', 'Vé này không thể hủy ở thời điểm hiện tại.');
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

        return redirect()->route('profile.bookings')->with('success', 'Đã hủy đơn thanh toán. Ghế đã được giải phóng.');
    }
}
