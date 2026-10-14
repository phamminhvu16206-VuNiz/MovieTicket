<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SePayWebhookController;
use App\Http\Controllers\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Admin\ShowtimeController as AdminShowtimeController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\BookingActionController;
use App\Http\Controllers\Admin\RefundController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/movie/{id}', [HomeController::class, 'showMovieDetail'])->name('movie.detail');

Route::get('/login', [AuthController::class, 'showFormLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showFormRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Payment webhook
|--------------------------------------------------------------------------
| Không đặt auth middleware: SePay server phải gọi được endpoint này.
*/
Route::post('/sepay/webhook', [SePayWebhookController::class, 'handleWebhook'])
    ->name('sepay.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/showtime/{showtimeId}/select-seat', [BookingController::class, 'selectSeat'])->name('booking.selectSeat');
    Route::post('/showtime/{showtimeId}/process-booking', [BookingController::class, 'processBooking'])->name('booking.process');

    // V3 Payment
    Route::get('/payment/{bookingId}', [PaymentController::class, 'show'])->name('payment.checkout');
    Route::post('/payment/{bookingId}/voucher', [PaymentController::class, 'applyVoucher'])->name('payment.voucher');
    Route::post('/payment/{bookingId}/process', [PaymentController::class, 'process'])->name('payment.process');
    Route::get('/payment/{bookingId}/status', [PaymentController::class, 'checkStatus'])->name('payment.status');
    Route::post('/payment/{bookingId}/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');

    Route::get('/booking/success/{bookingId}', [BookingController::class, 'showSuccess'])->name('booking.success');

    Route::post('/movie/{movieId}/review', [ReviewController::class, 'store'])->name('review.store');
    Route::delete('/movie/{movieId}/review', [ReviewController::class, 'destroy'])->name('review.destroy');

    Route::get('/profile/bookings', [BookingController::class, 'history'])->name('profile.bookings');
    Route::post('/profile/bookings/{bookingId}/cancel', [BookingController::class, 'cancel'])->name('booking.cancel');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    // Route xử lý Hủy & Hoàn vé
    Route::post('/booking/{id}/cancel-paid', [BookingActionController::class, 'cancelPaidBooking'])->name('booking.cancel_paid');
    Route::post('/booking/{id}/refund', [BookingActionController::class, 'refundBooking'])->name('booking.refund');
});

Route::middleware([AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminMovieController::class, 'dashboard'])->name('dashboard');
    Route::resource('movies', AdminMovieController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('showtimes', AdminShowtimeController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/toggle-lock', [AdminUserController::class, 'toggleLock'])->name('users.toggle-lock');
    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::patch('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('bookings.status');
});

Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/refunds', [RefundController::class, 'index'])->name('admin.refunds.index');
    Route::post('/refunds/{id}/approve', [RefundController::class, 'approve'])->name('admin.refunds.approve');
    Route::post('/bookings/{id}/update-status', [BookingController::class, 'updateStatus'])->name('admin.bookings.update_status');
});
