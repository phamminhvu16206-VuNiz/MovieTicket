<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovieRecommendationController extends Controller
{
    private const MOODS = [
        'fun' => [
            'label' => 'Muốn vui vẻ',
            'icon' => 'bi-emoji-laughing',
            'genres' => ['hài', 'hoạt hình', 'comedy', 'animation'],
        ],
        'thrilling' => [
            'label' => 'Muốn hồi hộp',
            'icon' => 'bi-lightning-charge',
            'genres' => ['hành động', 'phiêu lưu', 'kinh dị', 'giật gân', 'action', 'adventure', 'thriller', 'horror'],
        ],
        'romantic' => [
            'label' => 'Muốn lãng mạn',
            'icon' => 'bi-heart',
            'genres' => ['tình cảm', 'lãng mạn', 'romance', 'romantic'],
        ],
        'emotional' => [
            'label' => 'Muốn nhiều cảm xúc',
            'icon' => 'bi-cloud-rain',
            'genres' => ['tâm lý', 'chính kịch', 'drama'],
        ],
        'family' => [
            'label' => 'Đi cùng gia đình',
            'icon' => 'bi-people',
            'genres' => ['gia đình', 'hoạt hình', 'phiêu lưu', 'family', 'animation', 'adventure'],
        ],
    ];

    private const TIME_OPTIONS = [
        'any' => 'Bất kỳ lúc nào',
        'today' => 'Hôm nay',
        'evening' => 'Tối nay',
        'weekend' => 'Cuối tuần',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'mood' => ['nullable', 'in:fun,thrilling,romantic,emotional,family'],
            'duration' => ['nullable', 'integer', 'min:60', 'max:300'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:5000000'],
            'time' => ['nullable', 'in:any,today,evening,weekend'],
            'surprise' => ['nullable', 'boolean'],
        ]);

        $mood = $filters['mood'] ?? '';
        $time = $filters['time'] ?? 'any';
        $genreTerms = self::MOODS[$mood]['genres'] ?? [];
        $timeRange = $this->timeRange($time);

        $showtimeFilters = function (Builder|Relation $query) use ($filters, $timeRange): void {
            $query->where('status', 'active')
                ->where('start_time', '>=', now())
                ->when(isset($filters['budget']), fn (Builder $query) => $query->where('price', '<=', $filters['budget']))
                ->when($timeRange, fn (Builder $query) => $query
                    ->where('start_time', '>=', $timeRange[0])
                    ->where('start_time', '<=', $timeRange[1]))
                ->orderBy('start_time');
        };

        $moviesQuery = Movie::query()
            ->where('status', 'showing')
            ->when(isset($filters['duration']), fn (Builder $query) => $query->where('duration', '<=', $filters['duration']))
            ->when($genreTerms, function (Builder $query) use ($genreTerms): void {
                $query->where(function (Builder $query) use ($genreTerms): void {
                    foreach ($genreTerms as $term) {
                        $query->orWhere('genre', 'like', '%'.$term.'%');
                    }
                });
            })
            ->whereHas('showtimes', $showtimeFilters)
            ->with([
                'showtimes' => $showtimeFilters,
                'showtimes.room.cinema',
            ])
            ->orderBy('title');

        $movies = $moviesQuery->get();
        $selectedMovie = null;

        if ($request->boolean('surprise') && $movies->isNotEmpty()) {
            $selectedMovie = $movies->random();
        }

        return view('movies.recommendations', [
            'movies' => $movies,
            'selectedMovie' => $selectedMovie,
            'moods' => self::MOODS,
            'timeOptions' => self::TIME_OPTIONS,
            'filters' => [
                'mood' => $mood,
                'duration' => $filters['duration'] ?? '',
                'budget' => $filters['budget'] ?? '',
                'time' => $time,
                'surprise' => $request->boolean('surprise'),
            ],
        ]);
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}|null
     */
    private function timeRange(string $time): ?array
    {
        $now = now();

        return match ($time) {
            'today' => [$now, $now->copy()->endOfDay()],
            'evening' => [
                $now->copy()->startOfDay()->setTime(17, 0)->greaterThan($now)
                    ? $now->copy()->startOfDay()->setTime(17, 0)
                    : $now,
                $now->copy()->endOfDay(),
            ],
            'weekend' => $this->weekendRange($now),
            default => null,
        };
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function weekendRange(\Illuminate\Support\Carbon $now): array
    {
        $start = $now->copy()->startOfDay();

        if (! $start->isSaturday() && ! $start->isSunday()) {
            $start = $start->next(Carbon::SATURDAY)->startOfDay();
        }

        if ($start->isToday()) {
            $start = $now;

            if ($now->isSunday()) {
                return [$start, $now->copy()->endOfDay()];
            }
        }

        return [$start, $start->copy()->addDay()->endOfDay()];
    }
}
