<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f5;
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 600px;
            background: #ffffff;
            margin: 0 auto;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 15px;
        }

        .header h2 {
            color: #d97706;
            margin: 0;
        }

        .qr-section {
            text-align: center;
            margin: 25px 0;
            padding: 15px;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
        }

        .ticket-info {
            margin-top: 20px;
            line-height: 1.6;
            color: #334155;
        }

        .ticket-info td {
            padding: 6px 0;
        }

        .footer {
            text-align: center;
            margin-top: 25px;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>MOVIETICKET - VÉ XEM PHIM</h2>
            <p style="margin-top: 5px; color: #64748b;">Cảm ơn <strong>{{ $booking->user->name }}</strong> đã đặt vé!
            </p>
        </div>

        <div class="qr-section">
            <p style="font-weight: bold; margin-bottom: 10px; color: #1e293b;">MÃ VÉ ĐIỆN TỬ (E-TICKET)</p>
            <img src="{{ $qrCodeBase64 }}" alt="QR Code Vé Xem Phim" />
            <p style="margin-top: 10px; font-weight: bold; color: #d97706; font-size: 18px;">
                {{ $booking->booking_code }}
            </p>
            <small style="color: #64748b;">Vui lòng xuất trình mã QR này tại quầy soát vé</small>
        </div>

        <div class="ticket-info">
            <h4 style="color: #1e293b; border-bottom: 1px solid #ddd; padding-bottom: 5px;">CHI TIẾT VÉ:</h4>
            <table style="width: 100%;">
                <tr>
                    <td><strong>Phim:</strong></td>
                    <td style="text-align: right; font-weight: bold; color: #2563eb;">
                        {{ $booking->showtime->movie->title }}</td>
                </tr>
                <tr>
                    <td><strong>Rạp / Phòng:</strong></td>
                    <td style="text-align: right;">{{ $booking->showtime->room->cinema->name ?? 'MovieTicket' }} -
                        {{ $booking->showtime->room->name }}</td>
                </tr>
                <tr>
                    <td><strong>Suất chiếu:</strong></td>
                    <td style="text-align: right; color: #d97706; font-weight: bold;">
                        {{ date('H:i - d/m/Y', strtotime($booking->showtime->start_time)) }}</td>
                </tr>
                <tr>
                    <td><strong>Ghế ngồi:</strong></td>
                    <td style="text-align: right; font-weight: bold; color: #dc2626;">
                        {{ $booking->bookingSeats->map(fn($bs) => $bs->seat->seat_number)->join(', ') }}
                    </td>
                </tr>
                <tr style="border-top: 1px solid #eee;">
                    <td style="padding-top: 10px;"><strong>Tổng thanh toán:</strong></td>
                    <td
                        style="padding-top: 10px; text-align: right; font-size: 18px; font-weight: bold; color: #16a34a;">
                        {{ number_format($booking->total_amount) }} VNĐ
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Chúc bạn có những phút giây xem phim vui vẻ tại MovieTicket!</p>
        </div>
    </div>
</body>

</html>