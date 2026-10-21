<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $genre = trim((string) $request->input('genre', ''));

        $base = Movie::query()->when($q, fn ($query) => $query->where(function ($q2) use ($q) {
            $q2->where('title', 'like', "%{$q}%")
                ->orWhere('genre', 'like', "%{$q}%")
                ->orWhere('actors', 'like', "%{$q}%")
                ->orWhere('director', 'like', "%{$q}%");
        }))->when($genre, fn ($query) => $query->where('genre', 'like', "%{$genre}%"));

        $paginationFilters = $request->only(['q', 'genre']);
        $showingMovies = (clone $base)
            ->where('status', 'showing')
            ->orderBy('release_date', 'desc')
            ->paginate(4, ['*'], 'showing_page')
            ->appends($paginationFilters)
            ->fragment('now-showing');
        $upcomingMovies = (clone $base)
            ->where('status', 'upcoming')
            ->orderBy('release_date', 'asc')
            ->paginate(4, ['*'], 'upcoming_page')
            ->appends($paginationFilters)
            ->fragment('upcoming');
        $genres = Movie::query()->pluck('genre')->flatMap(fn ($value) => collect(explode(',', $value)))->map(fn ($g) => trim($g))->filter()->unique()->sort()->values();
        $watchlistMovieIds = $request->user()?->watchlistMovies()->pluck('movies.id')->all() ?? [];

        return view('home', compact('showingMovies', 'upcomingMovies', 'genres', 'q', 'genre', 'watchlistMovieIds'));
    }

    public function showMovieDetail($id)
    {
        $movie = Movie::with(['reviews.user', 'showtimes' => fn ($q) => $q->where('status', 'active')->where('start_time', '>=', now())->orderBy('start_time'), 'showtimes.room.cinema'])->findOrFail($id);
        $isWatchlisted = auth()->user()?->watchlistMovies()->where('movies.id', $movie->id)->exists() ?? false;

        return view('movies.detail', compact('movie', 'isWatchlisted'));
    }
}
