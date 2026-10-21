<?php

use App\Models\Booking;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Booking::expiredPending()->delete();
})->name('prune-expired-pending-bookings')->everyMinute()->withoutOverlapping(1);

Schedule::command('app:send-movie-watchlist-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping(30);

Schedule::command('app:send-booked-showtime-reminders')
    ->everyFifteenMinutes()
    ->withoutOverlapping(30);
