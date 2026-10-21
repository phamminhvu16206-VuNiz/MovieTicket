<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,sans-serif;color:#1e293b">
    <main style="max-width:600px;margin:0 auto;padding:28px;background:#fff;border:1px solid #e2e8f0;border-radius:14px">
        <h1 style="margin:0 0 16px;color:#d97706;font-size:22px">MovieTicket</h1>
        <h2 style="margin:0 0 12px;font-size:20px">{{ $title }}</h2>
        <p style="line-height:1.6">{{ $reminderMessage }}</p>
        <p style="font-size:18px;font-weight:bold;color:#b45309">{{ $movie->title }}</p>

        @if($booking)
            <table style="width:100%;border-collapse:collapse;line-height:1.8">
                <tr>
                    <td><strong>Mã đặt vé:</strong></td>
                    <td>{{ $booking->booking_code }}</td>
                </tr>
                <tr>
                    <td><strong>Suất chiếu:</strong></td>
                    <td>{{ $booking->showtime->start_time->format('H:i - d/m/Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Rạp:</strong></td>
                    <td>{{ $booking->showtime->room->cinema->name ?? 'MovieTicket Cinema' }}</td>
                </tr>
                <tr>
                    <td><strong>Phòng:</strong></td>
                    <td>{{ $booking->showtime->room->name }}</td>
                </tr>
                @if($booking->bookingSeats->isNotEmpty())
                    <tr>
                        <td><strong>Ghế:</strong></td>
                        <td>{{ $booking->bookingSeats->map(fn ($seat) => $seat->seat->seat_number)->join(', ') }}</td>
                    </tr>
                @endif
            </table>
        @endif

        <p style="margin:24px 0 0">
            <a href="{{ route('movie.detail', $movie) }}" style="display:inline-block;padding:11px 18px;background:#f59e0b;color:#111827;text-decoration:none;font-weight:bold;border-radius:8px">
                Xem thông tin phim
            </a>
        </p>
        <p style="margin:24px 0 0;color:#64748b;font-size:13px">Email tự động từ MovieTicket. Vui lòng không trả lời email này.</p>
    </main>
</body>
</html>
