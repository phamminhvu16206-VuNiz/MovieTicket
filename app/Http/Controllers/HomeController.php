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

        $showingMovies = (clone $base)->where('status', 'showing')->orderBy('release_date', 'desc')->get();
        $upcomingMovies = (clone $base)->where('status', 'upcoming')->orderBy('release_date', 'asc')->get();
        $genres = Movie::query()->pluck('genre')->flatMap(fn ($value) => collect(explode(',', $value)))->map(fn ($g) => trim($g))->filter()->unique()->sort()->values();

        return view('home', compact('showingMovies', 'upcomingMovies', 'genres', 'q', 'genre'));
    }

    public function showMovieDetail($id)
    {
        $movie = Movie::with(['reviews.user','showtimes' => fn ($q) => $q->where('status', 'active')->where('start_time', '>=', now())->orderBy('start_time'), 'showtimes.room.cinema'])->findOrFail($id);
        return view('movies.detail', compact('movie'));
    }
}
