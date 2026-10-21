<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MovieReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Movie $movie,
        public string $title,
        public string $reminderMessage,
        public ?Booking $booking = null,
    ) {
        $this->booking?->load([
            'showtime.room.cinema',
            'bookingSeats.seat',
            'user',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title.' - MovieTicket',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.movie_reminder',
        );
    }
}
