<?php

namespace Tests\Feature;

use App\Mail\MovieReminderMail;
use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use App\Notifications\MovieWatchlistReminder;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MovieWatchlistTest extends TestCase
{
    use DatabaseMigrations;

    public function test_watchlist_requires_authentication(): void
    {
        $this->get(route('watchlist.index'))
            ->assertRedirect(route('login'));

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('notification-menu');
    }

    public function test_authenticated_navigation_shows_bell_link_to_reminders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('watchlist.index').'#reminders')
            ->assertSee('aria-label="Mở lời nhắc"', false)
            ->assertSee('bi-bell')
            ->assertDontSee('notification-badge');
    }

    public function test_authenticated_navigation_shows_only_the_unread_watchlist_reminder_count(): void
    {
        $user = User::factory()->create();
        $movie = $this->createMovie();
        $user->notify(new MovieWatchlistReminder(
            'movie:'.$movie->id.':showtime:1',
            'showtime_available',
            $movie->id,
            'Phim đã có lịch chiếu',
            'Phim đã có suất chiếu.'
        ));
        $readReminder = $user->notifications()->firstOrFail();
        $readReminder->markAsRead();
        $user->notify(new MovieWatchlistReminder(
            'movie:'.$movie->id.':showtime:2',
            'new_showtime',
            $movie->id,
            'Có suất chiếu mới',
            'Phim vừa có suất chiếu.'
        ));

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('class="notification-badge" aria-hidden="true">1</span>', false)
            ->assertSee('aria-label="Mở lời nhắc, 1 lời nhắc chưa đọc"', false)
            ->assertSee('title="1 lời nhắc chưa đọc"', false);
    }

    public function test_customer_can_save_a_movie_and_sees_existing_showtime_reminder_once(): void
    {
        $user = User::factory()->create();
        $movie = $this->createMovie();
        $this->createShowtime($movie, now()->addDay());
        Mail::fake();

        $this->actingAs($user)
            ->post(route('watchlist.store', $movie))
            ->assertRedirect(route('watchlist.index'));

        $this->assertDatabaseHas('movie_watchlists', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
        ]);
        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame('showtime_available', $user->notifications()->firstOrFail()->data['event_type']);
        $this->get(route('movie.detail', $movie))
            ->assertOk()
            ->assertSee('Đã lưu · Bỏ khỏi danh sách');
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Đã lưu · Muốn xem');

        $this->post(route('watchlist.store', $movie))
            ->assertRedirect(route('watchlist.index'));

        $this->assertDatabaseCount('movie_watchlists', 1);
        $this->assertSame(1, $user->notifications()->count());
        Mail::assertQueued(MovieReminderMail::class, fn (MovieReminderMail $mail): bool => $mail->movie->is($movie)
            && $mail->title === 'Phim đã có lịch chiếu'
            && $mail->to[0]['address'] === $user->email
            && str_contains($mail->render(), $movie->title));
    }

    public function test_stopped_movie_cannot_be_added_to_the_watchlist(): void
    {
        $user = User::factory()->create();
        $movie = $this->createMovie('stopped');

        $this->actingAs($user)
            ->post(route('watchlist.store', $movie))
            ->assertRedirect()
            ->assertSessionHas('error', 'Phim này hiện không thể thêm vào danh sách muốn xem.');

        $this->assertDatabaseMissing('movie_watchlists', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
        ]);
    }

    public function test_customer_can_only_remove_their_own_watchlist_entry(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $movie = $this->createMovie();
        $owner->watchlistMovies()->attach($movie);

        $this->actingAs($otherUser)
            ->delete(route('watchlist.destroy', $movie))
            ->assertRedirect(route('watchlist.index'));

        $this->assertDatabaseHas('movie_watchlists', [
            'user_id' => $owner->id,
            'movie_id' => $movie->id,
        ]);
        $this->assertDatabaseMissing('movie_watchlists', [
            'user_id' => $otherUser->id,
            'movie_id' => $movie->id,
        ]);

        $this->actingAs($owner)
            ->delete(route('watchlist.destroy', $movie))
            ->assertRedirect(route('watchlist.index'));

        $this->assertDatabaseMissing('movie_watchlists', [
            'user_id' => $owner->id,
            'movie_id' => $movie->id,
        ]);
    }

    public function test_user_sees_only_their_movies_and_can_mark_their_reminder_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $movie = $this->createMovie();
        $otherMovie = $this->createMovie();
        $user->watchlistMovies()->attach($movie);
        $otherUser->watchlistMovies()->attach($otherMovie);
        $user->notify(new MovieWatchlistReminder(
            'movie:'.$movie->id.':release:'.today()->toDateString(),
            'release_soon',
            $movie->id,
            'Phim sắp khởi chiếu',
            'Phim sắp ra mắt.'
        ));
        $otherUser->notify(new MovieWatchlistReminder(
            'movie:'.$otherMovie->id.':release:'.today()->toDateString(),
            'release_soon',
            $otherMovie->id,
            'Phim sắp khởi chiếu',
            'Phim sắp ra mắt.'
        ));
        $notification = $user->notifications()->firstOrFail();
        $otherNotification = $otherUser->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('watchlist.index'))
            ->assertOk()
            ->assertSee($movie->title)
            ->assertDontSee($otherMovie->title)
            ->assertSee('Lời nhắc gần đây')
            ->assertSee('id="reminders"', false);

        $this->patch(route('watchlist.reminders.read', $otherNotification->id))
            ->assertNotFound();

        $this->patch(route('watchlist.reminders.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_new_active_future_showtime_notifies_watchlist_users_after_commit(): void
    {
        $user = User::factory()->create();
        $movie = $this->createMovie();
        $user->watchlistMovies()->attach($movie);
        $room = $this->createRoom();
        Mail::fake();

        Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addMinutes(110),
            'price' => 100000,
            'status' => 'active',
        ]);

        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame('new_showtime', $user->notifications()->firstOrFail()->data['event_type']);
        Mail::assertQueued(MovieReminderMail::class, fn (MovieReminderMail $mail): bool => $mail->movie->is($movie)
            && $mail->title === 'Có suất chiếu mới'
            && $mail->to[0]['address'] === $user->email
            && str_contains($mail->render(), $movie->title));
    }

    public function test_cancelled_or_past_showtime_does_not_notify_watchlist_users(): void
    {
        $user = User::factory()->create();
        $movie = $this->createMovie();
        $user->watchlistMovies()->attach($movie);
        $room = $this->createRoom();

        Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addMinutes(110),
            'price' => 100000,
            'status' => 'cancelled',
        ]);
        Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addMinutes(110),
            'price' => 100000,
            'status' => 'active',
        ]);

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_rolled_back_showtime_does_not_send_a_reminder(): void
    {
        $user = User::factory()->create();
        $movie = $this->createMovie();
        $user->watchlistMovies()->attach($movie);
        $room = $this->createRoom();

        DB::beginTransaction();
        Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addMinutes(110),
            'price' => 100000,
            'status' => 'active',
        ]);
        DB::rollBack();

        $this->assertDatabaseCount('showtimes', 0);
        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_scheduled_reminders_are_sent_once_for_releases_and_ending_schedules(): void
    {
        $this->travelTo('2026-10-21 09:00:00');
        $user = User::factory()->create();
        $releaseMovie = $this->createMovie('upcoming', today()->addDays(5));
        $endingMovie = $this->createMovie('showing');
        $extendedScheduleMovie = $this->createMovie('showing');
        $room = $this->createRoom();
        $this->createShowtime($endingMovie, now()->addDays(2), $room);
        $this->createShowtime($extendedScheduleMovie, now()->addDays(2), $room);
        $this->createShowtime($extendedScheduleMovie, now()->addDays(7), $room);
        $user->watchlistMovies()->attach([$releaseMovie->id, $endingMovie->id, $extendedScheduleMovie->id]);

        Artisan::call('app:send-movie-watchlist-reminders');
        Artisan::call('app:send-movie-watchlist-reminders');

        $this->assertSame(2, $user->notifications()->count());
        $eventTypes = $user->notifications()->get()->map(
            fn ($notification) => $notification->data['event_type']
        )->all();
        $this->assertContains('release_soon', $eventTypes);
        $this->assertContains('schedule_ending', $eventTypes);
    }

    public function test_paid_bookings_receive_24_hour_and_3_hour_showtime_reminders_once(): void
    {
        $this->travelTo('2026-10-21 09:00:00');
        $user = User::factory()->create();
        $movie = $this->createMovie();
        $dayBeforeBooking = $this->createPaidBooking($user, $movie, now()->addHours(24));
        $threeHoursBeforeBooking = $this->createPaidBooking($user, $movie, now()->addHours(3));
        $cancelledBooking = $this->createPaidBooking($user, $movie, now()->addHours(24));
        $cancelledBooking->update(['status' => 'cancelled']);
        Mail::fake();

        Artisan::call('app:send-booked-showtime-reminders');
        Artisan::call('app:send-booked-showtime-reminders');

        $reminderTypes = $user->notifications()
            ->where('type', MovieWatchlistReminder::class)
            ->oldest()
            ->get()
            ->map(fn ($notification) => $notification->data['event_type'])
            ->all();

        $this->assertSame(['booked_showtime_24h', 'booked_showtime_3h'], $reminderTypes);
        Mail::assertQueuedCount(2);
        Mail::assertQueued(MovieReminderMail::class, fn (MovieReminderMail $mail): bool => $mail->booking->is($dayBeforeBooking)
            && $mail->to[0]['address'] === $user->email);
        Mail::assertQueued(MovieReminderMail::class, fn (MovieReminderMail $mail): bool => $mail->booking->is($threeHoursBeforeBooking)
            && $mail->to[0]['address'] === $user->email);
    }

    private function createMovie(string $status = 'showing', DateTimeInterface|string|null $releaseDate = null): Movie
    {
        return Movie::create([
            'title' => 'Watchlist Film '.strtoupper(bin2hex(random_bytes(3))),
            'genre' => 'Drama',
            'duration' => 100,
            'release_date' => $releaseDate ?? today(),
            'status' => $status,
        ]);
    }

    private function createRoom(): Room
    {
        $cinema = Cinema::create([
            'name' => 'Watchlist Cinema',
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

    private function createShowtime(Movie $movie, DateTimeInterface|string $startTime, ?Room $room = null): Showtime
    {
        $room ??= $this->createRoom();
        $start = Carbon::parse($startTime);

        return Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addMinutes(110),
            'price' => 100000,
            'status' => 'active',
        ]);
    }

    private function createPaidBooking(User $user, Movie $movie, DateTimeInterface|string $startTime): Booking
    {
        $showtime = $this->createShowtime($movie, $startTime);

        return Booking::create([
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'booking_code' => 'MVT'.strtoupper(bin2hex(random_bytes(4))),
            'total_amount' => 100000,
            'status' => 'paid',
        ]);
    }
}
