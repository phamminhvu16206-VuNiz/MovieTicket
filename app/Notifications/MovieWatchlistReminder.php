<?php

namespace App\Notifications;

use App\Mail\MovieReminderMail;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class MovieWatchlistReminder extends Notification
{
    public function __construct(
        private readonly string $eventKey,
        private readonly string $eventType,
        private readonly int $movieId,
        private readonly string $title,
        private readonly string $message,
    ) {}

    public static function sendOnce(
        User $user,
        Movie $movie,
        string $eventType,
        string $eventKey,
        string $title,
        string $message,
        ?Booking $booking = null,
    ): bool {
        $alreadySent = $user->notifications()
            ->where('type', self::class)
            ->where('data->event_key', $eventKey)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        $user->notify(new self($eventKey, $eventType, $movie->id, $title, $message));

        if (in_array($eventType, [
            'new_showtime',
            'showtime_available',
            'booked_showtime_24h',
            'booked_showtime_3h',
        ], true)) {
            Mail::to($user->email)->queue(new MovieReminderMail($movie, $title, $message, $booking));
        }

        return true;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{event_key: string, event_type: string, movie_id: int, title: string, message: string}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'event_key' => $this->eventKey,
            'event_type' => $this->eventType,
            'movie_id' => $this->movieId,
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
