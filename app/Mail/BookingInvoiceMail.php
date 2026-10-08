<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookingInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $qrCodeBase64;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
        
        // Sinh mã QR dạng SVG Base64 để nhúng thẳng vào Email HTML
        $qrCodeSvg = QrCode::format('svg')->size(150)->generate($booking->booking_code);
        $this->qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vé Xem Phim & Hóa Đơn Đặt Vé - ' . $this->booking->booking_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking_invoice',
        );
    }
}