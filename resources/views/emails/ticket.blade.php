<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Vé điện tử - MovieTicket</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0b1120;
            color: #ffffff;
            margin: 0;
            padding: 20px;
        }

        .eticket-container {
            max-width: 650px;
            margin: 0 auto;
            background: #172033;
            border: 2px solid #f59e0b;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .eticket-header {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #111827;
            padding: 20px 30px;
            text-align: center;
        }

        .eticket-header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .eticket-header p {
            margin: 5px 0 0;
            font-size: 13px;
            font-weight: 600;
            opacity: 0.9;
        }

        .eticket-body {
            padding: 30px;
        }

        .movie-title {
            font-size: 22px;
            font-weight: bold;
            color: #f59e0b;
            margin-bottom: 5px;
        }

        .movie-meta {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 20px;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-grid td {
            padding: 8px 0;
            font-size: 14px;
            vertical-align: top;
        }

        .label {
            color: #94a3b8;
            width: 35%;
        }

        .value {
            color: #ffffff;
            font-weight: 600;
        }

        .value-highlight {
            color: #f59e0b;
            font-size: 16px;
            font-weight: bold;
        }

        .qr-section {
            background: #0f172a;
            border: 1px dashed #f59e0b;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin: 25px 0 10px 0;
        }

        .qr-code-img {
            background: #ffffff;
            padding: 12px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .booking-code {
            font-size: 26px;
            font-weight: bold;
            color: #f59e0b;
            letter-spacing: 3px;
            font-family: monospace;
        }

        .eticket-footer {
            background: #0b1120;
            padding: 15px 30px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body>

    <div class="eticket-container">
        <!-- Header E-Ticket -->
        <div class="eticket-header">
            <h1>Vé Xem Phim Điện Tử (E-Ticket)</h1>
            <p>Vui lòng xuất trình mã QR này tại quầy vé / cổng vào rạp</p>
        </div>

        <!-- Body -->
        <div class="eticket-body">
            <div class="movie-title">{{ $booking->showtime->movie->title }}</div>
            <div class="movie-meta">
                Thời lượng: {{ $booking->showtime->movie->duration }} phút | Thể loại:
                {{ $booking->showtime->movie->genre ?? 'Chiếu rạp' }}
            </div>

            <table class="info-grid">
                <tr>
                    <td class="label">Rạp chiếu:</td>
                    <td class="value">{{ $booking->showtime->room->cinema->name ?? 'MovieTicket Cinema' }}</td>
                </tr>
                <tr>
                    <td class="label">Phòng chiếu:</td>
                    <td class="value">{{ $booking->showtime->room->name }}</td>
                </tr>
                <tr>
                    <td class="label">Suất chiếu:</td>
                    <td class="value-highlight">
                        {{ \Carbon\Carbon::parse($booking->showtime->start_time)->format('H:i - d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Ghế ngồi:</td>
                    <td class="value-highlight">
                        {{ $booking->bookingSeats->map(fn($s) => $s->seat->seat_number)->join(', ') }}</td>
                </tr>
                @if($booking->bookingCombos && $booking->bookingCombos->count() > 0)
                    <tr>
                        <td class="label">Combo bắp nước:</td>
                        <td class="value">
                            {{ $booking->bookingCombos->map(fn($c) => $c->combo->name . ' (x' . $c->quantity . ')')->join(', ') }}
                        </td>
                    </tr>
                @endif
                <tr>
                    <td class="label">Tổng tiền:</td>
                    <td class="value" style="color: #10b981;">{{ number_format($booking->total_amount) }} VNĐ</td>
                </tr>
            </table>

            <!-- Khung Mã QR & Mã Vé -->
            <div class="qr-section">
                <div class="qr-code-img">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ $booking->booking_code }}"
                        width="180" height="180" alt="E-Ticket QR Code">
                </div>
                <div style="color: #94a3b8; font-size: 12px; margin-bottom: 4px;">MÃ ĐẶT VÉ (BOOKING CODE)</div>
                <div class="booking-code">{{ $booking->booking_code }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="eticket-footer">
            Cảm ơn bạn đã lựa chọn MovieTicket. Chúc bạn có buổi xem phim vui vẻ!<br>
            Mọi thắc mắc xin liên hệ Hotline CSKH hoặc nhân viên tại rạp.
        </div>
    </div>

</body>

</html>