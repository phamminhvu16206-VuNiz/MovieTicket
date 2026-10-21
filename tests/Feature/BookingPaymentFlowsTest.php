<?php

namespace Tests\Feature;

use App\Mail\BookingInvoiceMail;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Cinema;
use App\Models\Combo;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class BookingPaymentFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_holds_seats_for_three_minutes(): void
    {
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();

        $this->actingAs($user)
            ->post(route('booking.process', $showtime->id), ['seat_ids' => [$seat->id]])
            ->assertRedirect();

        $booking = Booking::firstOrFail();
        $this->assertSame('pending', $booking->status);
        $this->assertTrue($booking->expires_at->isFuture());
        $this->assertTrue($booking->expires_at->between(now()->addMinutes(2), now()->addMinutes(3)));
        $this->assertDatabaseHas('booking_seats', [
            'booking_id' => $booking->id,
            'seat_id' => $seat->id,
        ]);
    }

    public function test_payment_applies_combo_and_voucher_and_persists_ticket(): void
    {
        config([
            'services.sepay.enabled' => true,
            'services.sepay.api_key' => 'test-sepay-key',
            'services.sepay.bank_account' => '0123456789',
            'services.sepay.bank_name' => 'VCB',
            'services.sepay.account_name' => 'MOVIETICKET',
        ]);
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $combo = Combo::create(['name' => 'Combo Solo', 'price' => 55000, 'status' => true]);
        $voucher = Voucher::create([
            'code' => 'MOVIE10',
            'name' => '10% off',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order' => 100000,
            'max_discount' => 30000,
            'used_count' => 0,
            'status' => true,
        ]);
        $booking = $this->createBooking($user, $showtime, $seat, 100000);

        $this->actingAs($user)
            ->post(route('payment.voucher', $booking->id), ['code' => $voucher->code])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('payment.process', $booking->id), [
                'method' => 'sepay_qr',
                'combo_qty' => [$combo->id => 1],
            ])
            ->assertRedirect(route('payment.checkout', $booking->id));

        $this->postJson(route('sepay.webhook'), [
            'content' => 'Thanh toan '.$booking->booking_code,
            'transferAmount' => 139500,
            'referenceCode' => 'SEPAY-COMBO-TEST',
        ], ['Authorization' => 'Apikey test-sepay-key'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $booking->refresh();
        $this->assertSame('paid', $booking->status);
        $this->assertEquals(139500, (float) $booking->total_amount);
        $this->assertNull($booking->expires_at);
        $this->assertSame('success', $booking->payment->status);
        $this->assertDatabaseHas('booking_combos', [
            'booking_id' => $booking->id,
            'combo_id' => $combo->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('booking_vouchers', [
            'booking_id' => $booking->id,
            'voucher_id' => $voucher->id,
            'discount_amount' => 15500,
        ]);
        $this->assertSame(1, $voucher->fresh()->used_count);
    }

    public function test_sepay_requires_auth_and_exact_amount_before_confirming_booking(): void
    {
        Mail::fake();
        config([
            'services.sepay.enabled' => true,
            'services.sepay.api_key' => 'test-sepay-key',
        ]);

        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        $booking->update(['expires_at' => now()->addMinutes(3)]);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAYTEST123',
            'method' => 'sepay_qr',
            'amount' => 100000,
            'status' => 'pending',
        ]);
        $payload = [
            'content' => 'Thanh toan '.$booking->booking_code,
            'transferAmount' => 100000,
            'referenceCode' => 'SEPAY-TEST-1',
        ];

        $this->postJson(route('sepay.webhook'), $payload)
            ->assertUnauthorized();
        $this->postJson(route('sepay.webhook'), [...$payload, 'transferAmount' => 99000], [
            'Authorization' => 'Apikey test-sepay-key',
        ])->assertUnprocessable();

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame('pending', $booking->payment->fresh()->status);

        $this->postJson(route('sepay.webhook'), $payload, [
            'Authorization' => 'Apikey test-sepay-key',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertSame('success', $booking->payment->fresh()->status);
        $this->assertSame('SEPAY-TEST-1', $booking->payment->fresh()->transaction_ref);
        Mail::assertQueued(BookingInvoiceMail::class, fn (BookingInvoiceMail $mail): bool => $mail->booking->is($booking)
            && $mail->to[0]['address'] === $user->email
            && str_contains($mail->render(), $booking->booking_code));
    }

    public function test_expiration_status_check_does_not_cancel_another_users_booking(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($owner, $showtime, $seat, 100000);
        $booking->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($otherUser)
            ->getJson(route('payment.status', $booking->id))
            ->assertNotFound();

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_expired_booking_is_deleted_when_owner_returns_to_checkout(): void
    {
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAYEXPIRED1',
            'method' => 'sepay_qr',
            'amount' => 100000,
            'status' => 'pending',
        ]);
        $booking->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($user)
            ->get(route('payment.checkout', $booking->id))
            ->assertRedirect(route('home'));

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
        $this->assertDatabaseMissing('booking_seats', ['booking_id' => $booking->id]);
    }

    public function test_expired_pending_bookings_are_pruned_by_the_scheduler(): void
    {
        $this->travelTo(now()->startOfMinute()->addSeconds(5));
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        $booking->update(['expires_at' => now()->subMinute()]);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAYSCHEDULE1',
            'method' => 'sepay_qr',
            'amount' => 100000,
            'status' => 'pending',
        ]);

        Artisan::call('schedule:run');

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
        $this->assertDatabaseMissing('booking_seats', ['booking_id' => $booking->id]);
    }

    public function test_sepay_does_not_confirm_an_expired_booking(): void
    {
        config([
            'services.sepay.enabled' => true,
            'services.sepay.api_key' => 'test-sepay-key',
        ]);
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        $booking->update(['expires_at' => now()->subMinute()]);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAYTEST456',
            'method' => 'sepay_qr',
            'amount' => 100000,
            'status' => 'pending',
        ]);

        $this->postJson(route('sepay.webhook'), [
            'content' => $booking->booking_code,
            'transferAmount' => 100000,
            'referenceCode' => 'SEPAY-EXPIRED',
        ], ['Authorization' => 'Apikey test-sepay-key'])
            ->assertStatus(409);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
        $this->assertDatabaseMissing('booking_seats', ['booking_id' => $booking->id]);
    }

    public function test_expired_pending_booking_no_longer_blocks_its_seat(): void
    {
        $owner = User::factory()->create();
        $nextCustomer = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $expiredBooking = $this->createBooking($owner, $showtime, $seat, 100000);
        $expiredBooking->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($nextCustomer)
            ->post(route('booking.process', $showtime->id), ['seat_ids' => [$seat->id]])
            ->assertRedirect();

        $this->assertSame(1, Booking::where('showtime_id', $showtime->id)->count());
        $this->assertDatabaseMissing('bookings', ['id' => $expiredBooking->id]);
        $this->assertSame('pending', Booking::where('user_id', $nextCustomer->id)->firstOrFail()->status);
    }

    public function test_voucher_with_zero_usage_limit_is_not_valid(): void
    {
        $voucher = Voucher::create([
            'code' => 'SOLD-OUT',
            'name' => 'Unavailable voucher',
            'discount_type' => 'fixed',
            'discount_value' => 10000,
            'min_order' => 0,
            'usage_limit' => 0,
            'used_count' => 0,
            'status' => true,
        ]);

        $this->assertFalse($voucher->isValidFor(100000));
    }

    public function test_checkout_shows_simulated_methods_when_sepay_is_disabled(): void
    {
        Mail::fake();
        config([
            'services.sepay.enabled' => false,
            'services.sepay.bank_account' => null,
            'services.sepay.bank_name' => null,
            'services.sepay.api_key' => null,
        ]);
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);

        $this->actingAs($user)
            ->get(route('payment.checkout', $booking->id))
            ->assertOk()
            ->assertDontSee('value="qr"', false)
            ->assertDontSee('value="card"', false)
            ->assertSee('value="cash"', false)
            ->assertDontSee('value="sepay_qr"', false)
            ->assertDontSee('QR mô phỏng')
            ->assertDontSee('Thẻ mô phỏng')
            ->assertSee('SePay chưa được cấu hình');

        $this->post(route('payment.process', $booking->id), ['method' => 'card'])
            ->assertSessionHasErrors('method');

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);

        $this->post(route('payment.process', $booking->id), ['method' => 'cash'])
            ->assertRedirect(route('booking.success', $booking->id));

        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertSame('cash', $booking->payment->fresh()->method);
        $ticketEmail = (new BookingInvoiceMail($booking->fresh([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
            'user',
        ])))->with('message', new Message(new Email));
        $expectedQrCodeSvg = $ticketEmail->getQrCodeSvg();
        unset($ticketEmail->qrCodeSvg);
        $emailHtml = $ticketEmail->render();

        $this->get(route('profile.bookings'))
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertSee('Đã thanh toán');
        Mail::assertQueued(BookingInvoiceMail::class, fn (BookingInvoiceMail $mail): bool => $mail->booking->is($booking)
            && $mail->to[0]['address'] === $user->email
            && str_contains($mail->getQrCodeSvg(), '<svg'));
        $this->assertSame($expectedQrCodeSvg, $ticketEmail->getQrCodeSvg());
        $this->assertSame(1, preg_match('/src="(data:image\/svg\+xml;base64,[^"]+)"/', $emailHtml, $qrSource));
        $this->assertSame(
            'data:image/svg+xml;base64,'.base64_encode($ticketEmail->getQrCodeSvg()),
            preg_replace('/\s+/', '', $qrSource[1])
        );
    }

    public function test_customer_can_start_sepay_qr_payment_and_wait_for_webhook_confirmation(): void
    {
        config([
            'services.sepay.enabled' => true,
            'services.sepay.api_key' => 'test-sepay-key',
            'services.sepay.bank_account' => '0123456789',
            'services.sepay.bank_name' => 'VCB',
            'services.sepay.account_name' => 'MOVIETICKET',
        ]);
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);

        $this->actingAs($user)
            ->get(route('payment.checkout', $booking->id))
            ->assertOk()
            ->assertSee('value="sepay_qr"', false)
            ->assertSee('SePay • tự động xác nhận');

        $this->post(route('payment.process', $booking->id), [
            'method' => 'sepay_qr',
        ])->assertRedirect(route('payment.checkout', $booking->id));

        $booking->refresh();
        $this->assertSame('pending', $booking->status);
        $this->assertSame('pending', $booking->payment->status);
        $this->assertSame('sepay_qr', $booking->payment->method);

        $this->get(route('payment.checkout', $booking->id))
            ->assertOk()
            ->assertSee('alt="Mã QR thanh toán SePay"', false)
            ->assertSee('id="sepayModal"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('class="sepay-modal-backdrop is-open"', false)
            ->assertSee('data-sepay-close', false)
            ->assertSee('Nội dung chuyển khoản cần có mã đơn')
            ->assertSee($booking->booking_code);
    }

    public function test_sepay_webhook_only_confirms_a_pending_sepay_payment(): void
    {
        config([
            'services.sepay.enabled' => true,
            'services.sepay.api_key' => 'test-sepay-key',
        ]);
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAYTEST789',
            'method' => 'qr',
            'amount' => 100000,
            'status' => 'pending',
        ]);

        $this->postJson(route('sepay.webhook'), [
            'content' => 'Thanh toan '.$booking->booking_code,
            'transferAmount' => 100000,
            'referenceCode' => 'SEPAY-WRONG-METHOD',
        ], ['Authorization' => 'Apikey test-sepay-key'])
            ->assertOk()
            ->assertJson(['status' => 'ignored']);

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame('qr', $booking->payment->fresh()->method);
        $this->assertSame('pending', $booking->payment->fresh()->status);
    }

    public function test_sepay_cannot_be_started_when_its_configuration_is_incomplete(): void
    {
        config([
            'services.sepay.enabled' => false,
            'services.sepay.bank_account' => null,
            'services.sepay.bank_name' => null,
            'services.sepay.api_key' => null,
        ]);
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);

        $this->actingAs($user)
            ->post(route('payment.process', $booking->id), ['method' => 'sepay_qr'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
    }

    public function test_legacy_cancel_route_only_cancels_pending_bookings(): void
    {
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAY'.strtoupper(bin2hex(random_bytes(4))),
            'method' => 'qr',
            'amount' => 100000,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('booking.cancel', $booking->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
        $this->assertDatabaseMissing('booking_seats', ['booking_id' => $booking->id]);

        $paidBooking = $this->createBooking($user, $showtime, $seat, 100000);
        $paidBooking->update(['status' => 'paid']);
        Payment::create([
            'booking_id' => $paidBooking->id,
            'payment_code' => 'PAY'.strtoupper(bin2hex(random_bytes(4))),
            'method' => 'qr',
            'amount' => 100000,
            'status' => 'success',
        ]);

        $this->post(route('booking.cancel', $paidBooking->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('paid', $paidBooking->fresh()->status);
        $this->assertSame('success', $paidBooking->payment->fresh()->status);
    }

    public function test_cancelling_payment_deletes_pending_booking_and_related_records(): void
    {
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAYCANCEL1',
            'method' => 'sepay_qr',
            'amount' => 100000,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('payment.cancel', $booking->id))
            ->assertRedirect(route('profile.bookings'));

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
        $this->assertDatabaseMissing('booking_seats', ['booking_id' => $booking->id]);
    }

    public function test_cancelling_paid_booking_keeps_payment_record_and_history(): void
    {
        $this->travelTo('2026-10-21 12:00:00');
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $showtime->update([
            'start_time' => now()->addHours(48),
            'end_time' => now()->addHours(49)->addMinutes(40),
        ]);
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        $booking->update(['status' => 'paid']);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAYCANCELPAID',
            'method' => 'sepay_qr',
            'amount' => 100000,
            'status' => 'success',
            'paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('profile.bookings'))
            ->assertOk()
            ->assertSee('Yêu cầu hoàn tiền');

        $this->actingAs($user)
            ->post(route('booking.cancel_paid', $booking->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('booking_seats', [
            'booking_id' => $booking->id,
            'seat_id' => $seat->id,
        ]);

        $this->get(route('profile.bookings'))
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertSee($seat->seat_number)
            ->assertSee('Vé đã hủy');

        $nextCustomer = User::factory()->create();
        $this->actingAs($nextCustomer)
            ->post(route('booking.process', $showtime->id), ['seat_ids' => [$seat->id]])
            ->assertRedirect();

        $this->assertSame(2, Booking::where('showtime_id', $showtime->id)->count());
    }

    public function test_cannot_cancel_paid_booking_within_24_hours_of_showtime(): void
    {
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        $booking->update(['status' => 'paid']);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAYNO24H',
            'method' => 'sepay_qr',
            'amount' => 100000,
            'status' => 'success',
        ]);
        $this->travelTo(now()->startOfSecond());
        $showtime->update(['start_time' => now()->addHours(23)]);

        $this->actingAs($user)
            ->post(route('booking.cancel_paid', $booking->id))
            ->assertRedirect()
            ->assertSessionHas('error', 'Không thể hủy vé trong vòng 24 giờ trước giờ chiếu.');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id, 'status' => 'success']);
        $this->assertDatabaseHas('booking_seats', ['booking_id' => $booking->id, 'seat_id' => $seat->id]);

        $this->get(route('profile.bookings'))
            ->assertOk()
            ->assertDontSee('Yêu cầu hoàn tiền');
    }

    public function test_booking_history_hides_unpaid_orders_and_keeps_paid_cancelled_ones(): void
    {
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $pendingBooking = $this->createBooking($user, $showtime, $seat, 100000);
        $cancelledUnpaidBooking = $this->createBooking($user, $showtime, $seat, 100000);
        $cancelledUnpaidBooking->update(['status' => 'cancelled']);
        $paidBooking = $this->createBooking($user, $showtime, $seat, 100000);
        $paidBooking->update(['status' => 'paid']);
        Payment::create([
            'booking_id' => $paidBooking->id,
            'payment_code' => 'PAYHISTORY1',
            'method' => 'sepay_qr',
            'amount' => 100000,
            'status' => 'success',
        ]);
        $paidCancelledBooking = $this->createBooking($user, $showtime, $seat, 100000);
        $paidCancelledBooking->update(['status' => 'cancelled']);
        Payment::create([
            'booking_id' => $paidCancelledBooking->id,
            'payment_code' => 'PAYHISTORY2',
            'method' => 'sepay_qr',
            'amount' => 100000,
            'status' => 'success',
        ]);

        $this->actingAs($user)
            ->get(route('profile.bookings'))
            ->assertOk()
            ->assertSee($paidBooking->booking_code)
            ->assertSee($paidCancelledBooking->booking_code)
            ->assertDontSee($pendingBooking->booking_code)
            ->assertDontSee($cancelledUnpaidBooking->booking_code);
    }

    public function test_booking_history_shows_a_pending_booking_as_paid_when_cash_payment_succeeded(): void
    {
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAYCASHOLD1',
            'method' => 'cash',
            'amount' => 100000,
            'status' => 'success',
            'paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('profile.bookings'))
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertSee('Đã thanh toán')
            ->assertSee('Mã QR Vé Điện Tử')
            ->assertDontSee('Hủy đơn');
    }

    public function test_cancelled_booking_cannot_be_paid_or_shown_as_a_ticket(): void
    {
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        $booking->update(['status' => 'cancelled']);

        $this->actingAs($user)
            ->post(route('payment.process', $booking->id), ['method' => 'cash'])
            ->assertRedirect(route('profile.bookings'));

        $this->get(route('booking.success', $booking->id))
            ->assertNotFound();

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
    }

    public function test_success_ticket_renders_when_admin_confirms_booking_without_payment_record(): void
    {
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        $booking->update(['status' => 'paid']);

        $this->actingAs($user)
            ->get(route('booking.success', $booking->id))
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertSee('Đã xác nhận')
            ->assertSee('class="btn booking-action-home"', false)
            ->assertSee('VỀ TRANG CHỦ');
    }

    public function test_success_ticket_handles_unicode_movie_titles_in_qr_code(): void
    {
        $user = User::factory()->create();
        [$showtime, $seat] = $this->createShowtime();
        $showtime->movie()->update(['title' => 'Mắt Biếc: Tình Yêu Thời Học Trò']);
        $booking = $this->createBooking($user, $showtime, $seat, 100000);
        $booking->update(['status' => 'paid']);

        $this->actingAs($user)
            ->get(route('booking.success', $booking->id))
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertSee('Mắt Biếc: Tình Yêu Thời Học Trò');
    }

    private function createShowtime(): array
    {
        $movie = Movie::create([
            'title' => 'Test Movie',
            'genre' => 'Drama',
            'duration' => 90,
            'release_date' => today(),
            'status' => 'showing',
        ]);
        $cinema = Cinema::create([
            'name' => 'Test Cinema',
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

        return [$showtime, $seat];
    }

    private function createBooking(User $user, Showtime $showtime, Seat $seat, float $amount): Booking
    {
        $booking = Booking::create([
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'booking_code' => 'MVT'.strtoupper(bin2hex(random_bytes(4))),
            'total_amount' => $amount,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(3),
        ]);
        BookingSeat::create([
            'booking_id' => $booking->id,
            'seat_id' => $seat->id,
            'price' => $amount,
        ]);

        return $booking;
    }
}
