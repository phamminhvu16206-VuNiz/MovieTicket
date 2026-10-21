<?php

namespace Tests\Feature;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovieRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_movies_match_mood_duration_budget_and_today_filters(): void
    {
        $this->travelTo('2026-10-21 12:00:00');
        $room = $this->createRoom();
        $suitableMovie = $this->createMovie('Hài, Tình cảm', 100);
        $longMovie = $this->createMovie('Hài', 150);
        $expensiveMovie = $this->createMovie('Hài', 100);
        $wrongMoodMovie = $this->createMovie('Hành động', 100);
        $this->createShowtime($suitableMovie, $room, '2026-10-21 19:00:00', 100000);
        $this->createShowtime($longMovie, $room, '2026-10-21 19:30:00', 100000);
        $this->createShowtime($expensiveMovie, $room, '2026-10-21 20:00:00', 160000);
        $this->createShowtime($wrongMoodMovie, $room, '2026-10-21 20:30:00', 100000);

        $this->get(route('movie.recommendations', [
            'mood' => 'fun',
            'duration' => 120,
            'budget' => 120000,
            'time' => 'today',
        ]))
            ->assertOk()
            ->assertSee($suitableMovie->title)
            ->assertDontSee($longMovie->title)
            ->assertDontSee($expensiveMovie->title)
            ->assertDontSee($wrongMoodMovie->title);
    }

    public function test_movies_without_matching_future_showtimes_are_not_recommended(): void
    {
        $room = $this->createRoom();
        $withoutShowtime = $this->createMovie('Hài', 100);
        $pastMovie = $this->createMovie('Hài', 100);
        $cancelledMovie = $this->createMovie('Hài', 100);
        $this->createShowtime($pastMovie, $room, now()->subHour()->format('Y-m-d H:i:s'), 100000);
        $this->createShowtime($cancelledMovie, $room, now()->addDay()->format('Y-m-d H:i:s'), 100000, 'cancelled');

        $this->get(route('movie.recommendations', ['mood' => 'fun']))
            ->assertOk()
            ->assertDontSee($withoutShowtime->title)
            ->assertDontSee($pastMovie->title)
            ->assertDontSee($cancelledMovie->title);
    }

    public function test_surprise_pick_selects_a_matching_movie(): void
    {
        $room = $this->createRoom();
        $movie = $this->createMovie('Tình cảm', 110);
        $this->createShowtime($movie, $room, now()->addDay()->format('Y-m-d H:i:s'), 120000);

        $this->get(route('movie.recommendations', [
            'mood' => 'romantic',
            'surprise' => 1,
        ]))
            ->assertOk()
            ->assertSee('MÌNH CHỌN BỘ NÀY CHO BẠN')
            ->assertSee($movie->title);
    }

    public function test_evening_and_weekend_filters_only_show_scheduled_movies(): void
    {
        $this->travelTo('2026-10-23 16:00:00');
        $room = $this->createRoom();
        $eveningMovie = $this->createMovie('Hài', 100);
        $pastMovie = $this->createMovie('Hài', 100);
        $saturdayMovie = $this->createMovie('Hài', 100);
        $sundayMovie = $this->createMovie('Hài', 100);
        $mondayMovie = $this->createMovie('Hài', 100);
        $this->createShowtime($eveningMovie, $room, '2026-10-23 18:00:00', 100000);
        $this->createShowtime($pastMovie, $room, '2026-10-23 15:00:00', 100000);
        $this->createShowtime($saturdayMovie, $room, '2026-10-24 18:00:00', 100000);
        $this->createShowtime($sundayMovie, $room, '2026-10-25 12:00:00', 100000);
        $this->createShowtime($mondayMovie, $room, '2026-10-26 12:00:00', 100000);

        $this->get(route('movie.recommendations', ['time' => 'evening']))
            ->assertOk()
            ->assertSee($eveningMovie->title)
            ->assertDontSee($pastMovie->title)
            ->assertDontSee($saturdayMovie->title)
            ->assertDontSee($sundayMovie->title)
            ->assertDontSee($mondayMovie->title);

        $this->get(route('movie.recommendations', ['time' => 'weekend']))
            ->assertOk()
            ->assertSee($saturdayMovie->title)
            ->assertSee($sundayMovie->title)
            ->assertDontSee($eveningMovie->title)
            ->assertDontSee($mondayMovie->title);

        $this->travelTo('2026-10-25 10:00:00');
        $this->get(route('movie.recommendations', ['time' => 'weekend']))
            ->assertOk()
            ->assertSee($sundayMovie->title)
            ->assertDontSee($saturdayMovie->title)
            ->assertDontSee($mondayMovie->title);
    }

    public function test_homepage_links_to_movie_recommendations(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('movie.recommendations'))
            ->assertSee('Chưa biết xem gì tối nay?');
    }

    public function test_invalid_recommendation_filters_are_rejected(): void
    {
        $this->from(route('movie.recommendations'))
            ->get(route('movie.recommendations', ['mood' => 'unknown']))
            ->assertRedirect(route('movie.recommendations'))
            ->assertSessionHasErrors('mood');
    }

    private function createRoom(): Room
    {
        $cinema = Cinema::create([
            'name' => 'Recommendation Cinema',
            'address' => 'Test address',
            'phone' => '0123456789',
            'status' => 'active',
        ]);

        return Room::create([
            'cinema_id' => $cinema->id,
            'name' => 'Room 1',
            'rows' => 2,
            'seats_per_row' => 2,
            'status' => 'active',
        ]);
    }

    private function createMovie(string $genre, int $duration): Movie
    {
        return Movie::create([
            'title' => 'Recommendation Film '.strtoupper(bin2hex(random_bytes(3))),
            'genre' => $genre,
            'duration' => $duration,
            'release_date' => today(),
            'status' => 'showing',
        ]);
    }

    private function createShowtime(
        Movie $movie,
        Room $room,
        string $startTime,
        float $price,
        string $status = 'active',
    ): Showtime {
        return Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => $startTime,
            'end_time' => date('Y-m-d H:i:s', strtotime($startTime.' + 100 minutes')),
            'price' => $price,
            'status' => $status,
        ]);
    }
}
