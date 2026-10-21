<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Notifications\MovieWatchlistReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('app:send-movie-watchlist-reminders')]
#[Description('Send in-app reminders for movies on customer watchlists')]
class SendMovieWatchlistReminders extends Command
{
    public function handle(): int
    {
        $sentCount = 0;
        $releaseWindowStart = today();
        $releaseWindowEnd = today()->addDays(7);

        Movie::query()
            ->where('status', 'upcoming')
            ->whereBetween('release_date', [$releaseWindowStart, $releaseWindowEnd])
            ->with('watchlistUsers')
            ->chunkById(100, function (Collection $movies) use (&$sentCount): void {
                foreach ($movies as $movie) {
                    foreach ($movie->watchlistUsers as $user) {
                        $sentCount += (int) MovieWatchlistReminder::sendOnce(
                            $user,
                            $movie,
                            'release_soon',
                            "movie:{$movie->id}:release:{$movie->release_date->toDateString()}",
                            'Phim sắp khởi chiếu',
                            "Phim {$movie->title} sẽ khởi chiếu ngày {$movie->release_date->format('d/m/Y')}."
                        );
                    }
                }
            });

        $reminderWindowStart = now();
        $reminderWindowEnd = now()->addDays(3);

        Movie::query()
            ->where('status', 'showing')
            ->whereHas('showtimes', fn ($query) => $query
                ->where('status', 'active')
                ->whereBetween('start_time', [$reminderWindowStart, $reminderWindowEnd]))
            ->with([
                'watchlistUsers',
                'showtimes' => fn ($query) => $query
                    ->where('status', 'active')
                    ->where('start_time', '>=', $reminderWindowStart)
                    ->orderByDesc('start_time'),
            ])
            ->chunkById(100, function (Collection $movies) use (&$sentCount, $reminderWindowEnd): void {
                foreach ($movies as $movie) {
                    $lastUpcomingShowtime = $movie->showtimes->first();

                    if ($lastUpcomingShowtime === null || $lastUpcomingShowtime->start_time->gt($reminderWindowEnd)) {
                        continue;
                    }

                    foreach ($movie->watchlistUsers as $user) {
                        $sentCount += (int) MovieWatchlistReminder::sendOnce(
                            $user,
                            $movie,
                            'schedule_ending',
                            "movie:{$movie->id}:ending:showtime:{$lastUpcomingShowtime->id}",
                            'Lịch chiếu sắp kết thúc',
                            "Các suất chiếu đã lên lịch cho phim {$movie->title} sắp kết thúc. Đặt vé trước khi lịch chiếu đóng."
                        );
                    }
                }
            });

        $this->info("Đã gửi {$sentCount} lời nhắc trong ứng dụng.");

        return self::SUCCESS;
    }
}
