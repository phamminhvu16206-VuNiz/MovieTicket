<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookingInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $booking;

    public $qrCodeSvg = '';

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;

        $this->qrCodeSvg = $this->generateQrCodeSvg($booking);
    }

    public function getQrCodeSvg(): string
    {
        if (! isset($this->qrCodeSvg) || $this->qrCodeSvg === '') {
            $this->qrCodeSvg = $this->generateQrCodeSvg($this->booking);
        }

        return $this->qrCodeSvg;
    }

    private function generateQrCodeSvg(Booking $booking): string
    {
        // Email clients commonly block data URI images, so the SVG is embedded as a CID image.
        $qrPayload = 'MOVIETICKET|'.$booking->booking_code.'|'.Str::ascii($booking->showtime->movie->title);

        return (string) QrCode::format('svg')->size(240)->margin(2)->generate($qrPayload);
    }

    public function buildViewData(): array
    {
        $this->getQrCodeSvg();

        return parent::buildViewData();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vé Xem Phim & Hóa Đơn Đặt Vé - '.$this->booking->booking_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking_invoice',
        );
    }
}
