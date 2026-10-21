<?php

namespace App\Providers;

use App\Models\Showtime;
use App\Notifications\MovieWatchlistReminder;
use App\Observers\MovieWatchlistObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Showtime::observe(MovieWatchlistObserver::class);

        View::composer('layouts.app', function ($view): void {
            $unreadReminderCount = auth()->user()
                ?->unreadNotifications()
                ->where('type', MovieWatchlistReminder::class)
                ->count() ?? 0;

            $view->with('unreadReminderCount', $unreadReminderCount);
        });
    }
}
