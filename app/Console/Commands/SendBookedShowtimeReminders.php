<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\MovieWatchlistReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('app:send-booked-showtime-reminders')]
#[Description('Send email reminders before paid movie showtimes')]
class SendBookedShowtimeReminders extends Command
{
    public function handle(): int
    {
        $sentCount = 0;

        foreach ([24 => '24h', 3 => '3h'] as $hours => $reminderInterval) {
            $windowStart = now()->addHours($hours)->subMinutes(15);
            $windowEnd = now()->addHours($hours);

            Booking::query()
                ->where('status', 'paid')
                ->whereHas('showtime', fn ($query) => $query
                    ->where('status', 'active')
                    ->whereBetween('start_time', [$windowStart, $windowEnd]))
                ->with([
                    'user',
                    'showtime.movie',
                    'showtime.room.cinema',
                    'bookingSeats.seat',
                ])
                ->chunkById(100, function (Collection $bookings) use (&$sentCount, $reminderInterval): void {
                    foreach ($bookings as $booking) {
                        $movie = $booking->showtime->movie;
                        $showtime = $booking->showtime;
                        $hoursUntilShowtime = $reminderInterval === '24h' ? 24 : 3;

                        $sentCount += (int) MovieWatchlistReminder::sendOnce(
                            $booking->user,
                            $movie,
                            "booked_showtime_{$reminderInterval}",
                            "booking:{$booking->id}:showtime:{$showtime->id}:{$reminderInterval}",
                            "Phim bạn đã đặt sẽ chiếu sau {$hoursUntilShowtime} giờ",
                            "Suất chiếu phim {$movie->title} của bạn sẽ bắt đầu lúc {$showtime->start_time->format('H:i d/m/Y')}.",
                            $booking
                        );
                    }
                });
        }

        $this->info("Đã gửi {$sentCount} lời nhắc vé trong ứng dụng và email.");

        return self::SUCCESS;
    }
}
