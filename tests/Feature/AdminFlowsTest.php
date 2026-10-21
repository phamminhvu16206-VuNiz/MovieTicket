<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_view_or_approve_refunds(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.refunds.index'))
            ->assertRedirect(route('home'));

        $this->actingAs($customer)
            ->post(route('admin.refunds.approve', 1))
            ->assertRedirect(route('home'));
    }

    public function test_admin_status_endpoint_updates_booking_and_payment_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$booking] = $this->createBooking();

        $this->actingAs($admin)
            ->postJson(route('admin.bookings.update_status', $booking->id), ['status' => 'refund_pending'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('refund_pending', $booking->fresh()->status);
        $this->assertSame('refund_pending', $booking->payment->fresh()->status);

        $this->actingAs($admin)
            ->postJson(route('admin.bookings.update_status', $booking->id), ['status' => 'paid'])
            ->assertOk();

        $this->assertSame('success', $booking->payment->fresh()->status);

        $this->actingAs($admin)
            ->postJson(route('admin.bookings.update_status', $booking->id), ['status' => 'cancelled'])
            ->assertOk();

        $this->assertSame('success', $booking->payment->fresh()->status);
    }

    public function test_admin_can_approve_refund_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$booking, $customer] = $this->createBooking();
        $booking->update(['status' => 'refund_pending']);
        $booking->payment->update(['status' => 'refund_pending']);
        DB::table('refund_requests')->insert([
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
            'amount' => 100000,
            'bank_name' => 'Test Bank',
            'account_number' => '12345678',
            'account_name' => 'Test User',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', 1))
            ->assertRedirect();

        $this->assertSame('refunded', $booking->fresh()->status);
        $this->assertSame('refunded', $booking->payment->fresh()->status);
        $this->assertDatabaseHas('refund_requests', ['booking_id' => $booking->id, 'status' => 'completed']);
        $this->assertDatabaseHas('booking_seats', ['booking_id' => $booking->id]);

        $this->actingAs($admin)
            ->post(route('admin.refunds.approve', 1))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_admin_refund_page_shows_request_and_manual_confirmation_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$booking, $customer] = $this->createBooking();
        $booking->update(['status' => 'refund_pending']);
        $booking->payment->update(['status' => 'refund_pending']);
        DB::table('refund_requests')->insert([
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
            'amount' => 100000,
            'bank_name' => 'Test Bank',
            'account_number' => '12345678',
            'account_name' => 'TEST USER',
            'reason' => 'Không thể tham gia',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.refunds.index'))
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertSee($customer->email)
            ->assertSee('Test Bank')
            ->assertSee('12345678')
            ->assertSee('Không thể tham gia')
            ->assertSee('Chờ Admin xác nhận')
            ->assertSee('Xác nhận đã chuyển khoản');
    }

    public function test_customer_refund_request_updates_booking_and_payment(): void
    {
        $customer = User::factory()->create();
        [$booking] = $this->createBooking($customer);
        $booking->showtime->update([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addMinutes(100),
        ]);

        $this->actingAs($customer)
            ->post(route('booking.refund', $booking->id), [
                'bank_name' => 'Test Bank',
                'account_number' => '12345678',
                'account_name' => 'Test Customer',
                'reason' => 'Schedule changed',
            ])
            ->assertRedirect();

        $this->assertSame('refund_pending', $booking->fresh()->status);
        $this->assertSame('refund_pending', $booking->payment->fresh()->status);
        $this->assertDatabaseHas('refund_requests', [
            'booking_id' => $booking->id,
            'account_name' => 'TEST CUSTOMER',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('refund_requests', [
            'booking_id' => $booking->id,
            'reason' => 'Schedule changed',
        ]);
        $this->assertDatabaseHas('booking_seats', ['booking_id' => $booking->id]);
    }

    public function test_customer_must_provide_a_refund_reason(): void
    {
        $customer = User::factory()->create();
        [$booking] = $this->createBooking($customer);
        $booking->showtime->update([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addMinutes(100),
        ]);

        $this->actingAs($customer)
            ->from(route('profile.bookings'))
            ->post(route('booking.refund', $booking->id), [
                'bank_name' => 'Test Bank',
                'account_number' => '12345678',
                'account_name' => 'Test Customer',
            ])
            ->assertRedirect(route('profile.bookings'))
            ->assertSessionHasErrors('reason');

        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertDatabaseMissing('refund_requests', ['booking_id' => $booking->id]);
    }

    public function test_customer_cannot_cancel_or_request_refund_within_24_hours_of_showtime(): void
    {
        $customer = User::factory()->create();
        [$booking] = $this->createBooking($customer);
        $this->travelTo('2026-10-21 12:00:00');
        $booking->showtime->update([
            'start_time' => now()->addHours(23)->addMinutes(59),
            'end_time' => now()->addHours(25)->addMinutes(39),
        ]);

        $this->actingAs($customer)
            ->post(route('booking.refund', $booking->id), [
                'bank_name' => 'Test Bank',
                'account_number' => '12345678',
                'account_name' => 'Test Customer',
                'reason' => 'Không thể tham gia',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Không thể hủy/hoàn vé trong vòng 24 giờ trước giờ chiếu.');

        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertSame('success', $booking->payment->fresh()->status);
        $this->assertDatabaseMissing('refund_requests', ['booking_id' => $booking->id]);
    }

    public function test_customer_cannot_request_refund_at_the_24_hour_cutoff(): void
    {
        $customer = User::factory()->create();
        [$booking] = $this->createBooking($customer);
        $this->travelTo('2026-10-21 12:00:00');
        $booking->showtime->update([
            'start_time' => now()->addHours(24),
            'end_time' => now()->addHours(25)->addMinutes(40),
        ]);

        $this->actingAs($customer)
            ->post(route('booking.refund', $booking->id), [
                'bank_name' => 'Test Bank',
                'account_number' => '12345678',
                'account_name' => 'Test Customer',
                'reason' => 'Không thể tham gia',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Không thể hủy/hoàn vé trong vòng 24 giờ trước giờ chiếu.');

        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertDatabaseMissing('refund_requests', ['booking_id' => $booking->id]);
    }

    private function createBooking(?User $customer = null): array
    {
        $customer ??= User::factory()->create();
        $movie = Movie::create([
            'title' => 'Admin Test Movie',
            'genre' => 'Drama',
            'duration' => 90,
            'release_date' => today(),
            'status' => 'showing',
        ]);
        $cinema = Cinema::create([
            'name' => 'Admin Test Cinema',
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
        $seat = Seat::create([
            'room_id' => $room->id,
            'seat_number' => 'A1',
            'row' => 'A',
            'column' => 1,
        ]);
        $start = now()->addDay();
        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addMinutes(100),
            'price' => 100000,
            'status' => 'active',
        ]);
        $booking = Booking::create([
            'user_id' => $customer->id,
            'showtime_id' => $showtime->id,
            'booking_code' => 'MVT'.strtoupper(bin2hex(random_bytes(4))),
            'total_amount' => 100000,
            'status' => 'paid',
        ]);
        $booking->bookingSeats()->create(['seat_id' => $seat->id, 'price' => 100000]);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAY'.strtoupper(bin2hex(random_bytes(4))),
            'method' => 'qr',
            'amount' => 100000,
            'status' => 'success',
        ]);

        return [$booking, $customer];
    }
}
