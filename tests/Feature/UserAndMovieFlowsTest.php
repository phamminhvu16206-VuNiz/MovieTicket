<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAndMovieFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_login_and_profile_update_work_end_to_end(): void
    {
        $this->post(route('register'), [
            'name' => 'Test Customer',
            'email' => 'test-customer@example.com',
            'phone' => '0987654321',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('home'));

        $user = User::where('email', 'test-customer@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect(route('home'));

        $this->put(route('profile.update'), [
            'name' => 'Updated Customer',
            'phone' => '0912345678',
        ])->assertRedirect();

        $this->assertSame('Updated Customer', $user->fresh()->name);
        $this->assertSame('0912345678', $user->fresh()->phone);
    }

    public function test_movie_search_and_details_show_published_movie_information(): void
    {
        $movie = Movie::create([
            'title' => 'Searchable Film',
            'genre' => 'Drama',
            'duration' => 100,
            'release_date' => today(),
            'director' => 'Test Director',
            'actors' => 'Test Actor',
            'status' => 'showing',
        ]);
        Movie::create([
            'title' => 'Upcoming Film',
            'genre' => 'Comedy',
            'duration' => 95,
            'release_date' => today()->addDays(10),
            'status' => 'upcoming',
        ]);

        $this->get(route('home', ['q' => 'Test Director', 'genre' => 'Drama']))
            ->assertOk()
            ->assertSee('Searchable Film')
            ->assertDontSee('Upcoming Film');

        $this->get(route('movie.detail', $movie->id))
            ->assertOk()
            ->assertSee('Searchable Film')
            ->assertSee('Test Actor');
    }

    public function test_homepage_paginates_showing_and_upcoming_movies_independently_four_per_page(): void
    {
        $this->createPaginatedMovies('showing', 'Showing Pagination', 10);
        $this->createPaginatedMovies('upcoming', 'Upcoming Pagination', 6);

        $this->get(route('home', [
            'q' => 'Pagination',
            'genre' => 'Drama',
            'showing_page' => 2,
            'upcoming_page' => 1,
        ]))
            ->assertOk()
            ->assertSee('Showing Pagination 5')
            ->assertSee('Showing Pagination 8')
            ->assertDontSee('Showing Pagination 4')
            ->assertDontSee('Showing Pagination 9')
            ->assertSee('Upcoming Pagination 1')
            ->assertSee('Upcoming Pagination 4')
            ->assertDontSee('Upcoming Pagination 5')
            ->assertSee('showing_page=3#now-showing')
            ->assertSee('upcoming_page=2#upcoming')
            ->assertSee('home-pagination mt-4');
    }

    public function test_homepage_shows_remaining_upcoming_movies_on_their_own_second_page(): void
    {
        $this->createPaginatedMovies('showing', 'Showing Pagination', 2);
        $this->createPaginatedMovies('upcoming', 'Upcoming Pagination', 6);

        $this->get(route('home', [
            'q' => 'Pagination',
            'genre' => 'Drama',
            'showing_page' => 1,
            'upcoming_page' => 2,
        ]))
            ->assertOk()
            ->assertSee('Showing Pagination 1')
            ->assertSee('Showing Pagination 2')
            ->assertSee('Upcoming Pagination 5')
            ->assertSee('Upcoming Pagination 6')
            ->assertDontSee('Upcoming Pagination 4')
            ->assertSee('q=Pagination')
            ->assertSee('genre=Drama')
            ->assertSee('home-pagination home-pagination-upcoming mt-4');
    }

    public function test_customer_can_review_a_movie_after_a_paid_past_showtime(): void
    {
        $user = User::factory()->create();
        $movie = Movie::create([
            'title' => 'Reviewed Film',
            'genre' => 'Drama',
            'duration' => 100,
            'release_date' => today()->subDays(2),
            'status' => 'showing',
        ]);
        $cinema = Cinema::create([
            'name' => 'Review Cinema',
            'address' => 'Test address',
            'phone' => '0123456789',
            'status' => 'active',
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->id,
            'name' => 'Room 1',
            'rows' => 1,
            'seats_per_row' => 1,
            'status' => 'active',
        ]);
        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addMinutes(110),
            'price' => 100000,
            'status' => 'active',
        ]);
        Booking::create([
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'booking_code' => 'MVT'.strtoupper(bin2hex(random_bytes(4))),
            'total_amount' => 100000,
            'status' => 'paid',
        ]);

        $this->actingAs($user)
            ->post(route('review.store', $movie->id), [
                'rating' => 5,
                'comment' => 'Great film',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'rating' => 5,
            'comment' => 'Great film',
        ]);
    }

    private function createPaginatedMovies(string $status, string $titlePrefix, int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            Movie::create([
                'title' => "{$titlePrefix} {$index}",
                'genre' => 'Drama',
                'duration' => 100,
                'release_date' => $status === 'showing'
                    ? today()->subDays($index)
                    : today()->addDays($index),
                'status' => $status,
            ]);
        }
    }
}
