<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Notifications\MovieWatchlistReminder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MovieWatchlistController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $movies = $user->watchlistMovies()
            ->with(['showtimes' => fn ($query) => $query
                ->where('status', 'active')
                ->where('start_time', '>=', now())
                ->orderBy('start_time')])
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);
        $reminders = $user->notifications()
            ->where('type', MovieWatchlistReminder::class)
            ->latest()
            ->limit(10)
            ->get();

        return view('movies.watchlist', compact('movies', 'reminders'));
    }

    public function store(Request $request, Movie $movie): RedirectResponse
    {
        if (! in_array($movie->status, ['showing', 'upcoming'], true)) {
            return back()->with('error', 'Phim này hiện không thể thêm vào danh sách muốn xem.');
        }

        $user = $request->user();
        $alreadySaved = $user->watchlistMovies()->whereKey($movie->getKey())->exists();
        $user->watchlistMovies()->syncWithoutDetaching([$movie->getKey()]);

        $nextShowtime = $movie->showtimes()
            ->where('status', 'active')
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->first();

        if ($nextShowtime !== null) {
            MovieWatchlistReminder::sendOnce(
                $user,
                $movie,
                'showtime_available',
                "movie:{$movie->id}:showtime:{$nextShowtime->id}",
                'Phim đã có lịch chiếu',
                "Phim {$movie->title} đã có suất chiếu mới. Bạn có thể xem lịch và đặt vé ngay."
            );
        }

        return redirect()->route('watchlist.index')->with(
            'success',
            $alreadySaved ? 'Phim đã có trong danh sách muốn xem.' : 'Đã thêm phim vào danh sách muốn xem.'
        );
    }

    public function destroy(Request $request, Movie $movie): RedirectResponse
    {
        $request->user()->watchlistMovies()->detach($movie->getKey());

        return redirect()->route('watchlist.index')->with('success', 'Đã xóa phim khỏi danh sách muốn xem.');
    }

    public function markReminderAsRead(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()->notifications()
            ->whereKey($notificationId)
            ->where('type', MovieWatchlistReminder::class)
            ->firstOrFail();

        $notification->markAsRead();

        return back()->with('success', 'Đã đánh dấu lời nhắc là đã xem.');
    }
}
