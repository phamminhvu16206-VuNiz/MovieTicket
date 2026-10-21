<?php

namespace Tests\Feature;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Database\Seeders\AdditionalMoviesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalMoviesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_adds_five_movies_with_two_non_overlapping_showtimes_and_is_idempotent(): void
    {
        $cinema = Cinema::create([
            'name' => 'Seeder Cinema',
            'address' => 'Test address',
            'phone' => '0123456789',
            'status' => 'active',
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->id,
            'name' => 'Room 1',
            'rows' => 5,
            'seats_per_row' => 10,
            'status' => 'active',
        ]);
        $titles = [
            'Oppenheimer',
            'Kung Fu Panda 4',
            'Godzilla x Kong: Đế Chế Mới',
            'Wonka',
            'Doraemon: Nobita Và Bản Giao Hưởng Địa Cầu',
        ];

        $this->seed(AdditionalMoviesSeeder::class);
        $this->seed(AdditionalMoviesSeeder::class);

        $this->assertSame(5, Movie::query()->whereIn('title', $titles)->count());
        $this->assertSame(10, Showtime::query()->where('room_id', $room->id)->whereHas(
            'movie',
            fn ($query) => $query->whereIn('title', $titles)
        )->count());

        $showtimes = Showtime::query()
            ->where('room_id', $room->id)
            ->whereHas('movie', fn ($query) => $query->whereIn('title', $titles))
            ->orderBy('start_time')
            ->get();

        foreach ($showtimes->values() as $index => $showtime) {
            if ($index === 0) {
                continue;
            }

            $this->assertTrue($showtimes[$index - 1]->end_time->lessThanOrEqualTo($showtime->start_time));
        }
    }
}
