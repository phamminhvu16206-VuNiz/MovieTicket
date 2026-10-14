<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketEmailed extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    public function __construct(Booking $booking)
    {
        // Load đầy đủ thông tin vé, phim, rạp, ghế, combo
        $this->booking = $booking->load([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
            'bookingCombos.combo',
            'user'
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vé điện tử xem phim - ' . ($this->booking->showtime->movie->title ?? 'MovieTicket') . ' [' . $this->booking->booking_code . ']',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket',
        );
    }
}
