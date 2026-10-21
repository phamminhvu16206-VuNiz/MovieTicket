<?php

namespace App\Observers;

use App\Models\Movie;
use App\Models\Showtime;
use App\Notifications\MovieWatchlistReminder;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class MovieWatchlistObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Showtime $showtime): void
    {
        if ($showtime->status !== 'active' || ! $showtime->start_time->isFuture()) {
            return;
        }

        $movie = Movie::with('watchlistUsers')->find($showtime->movie_id);

        if ($movie === null) {
            return;
        }

        foreach ($movie->watchlistUsers as $user) {
            MovieWatchlistReminder::sendOnce(
                $user,
                $movie,
                'new_showtime',
                "movie:{$movie->id}:showtime:{$showtime->id}",
                'Có suất chiếu mới',
                "Phim {$movie->title} vừa có thêm suất chiếu. Hãy xem lịch để chọn giờ phù hợp."
            );
        }
    }
}
