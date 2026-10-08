@extends('layouts.app')

@section('title', 'Chọn Ghế - ' . $showtime->movie->title)

@section('content')
    <div class="container my-4">
        <div class="row g-4">
            <!-- Cột Bên Trái: Sơ Đồ Chọn Ghế -->
            <div class="col-lg-8">
                <div class="card card-custom p-4 shadow border-0 text-center">
                    <h4 class="text-warning fw-bold mb-3"><i class="bi bi-grid-3x3-gap-fill me-2"></i>SƠ ĐỒ CHỌN GHẾ</h4>

                    <!-- Màn Chiếu Simulation -->
                    <div class="w-100 bg-secondary text-dark fw-bold py-2 rounded mb-5 shadow-sm"
                        style="letter-spacing: 5px;">
                        MÀN CHIẾU (SCREEN)
                    </div>

                    <form id="seatForm" action="{{ route('booking.process', $showtime->id) }}" method="POST">
                        @csrf
                        <!-- Danh Sách Ghế Ngồi -->
                        <div class="d-flex flex-column align-items-center gap-3 overflow-auto pb-3">
                            @foreach($seatsByRow as $row => $seats)
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-warning me-2" style="width: 20px;">{{ $row }}</span>
                                    @foreach($seats as $seat)
                                        @php
                                            $isBooked = in_array($seat->id, $bookedSeatIds);
                                        @endphp

                                        <input type="checkbox" name="seat_ids[]" value="{{ $seat->id }}" id="seat-{{ $seat->id }}"
                                            class="btn-check seat-checkbox" data-price="{{ $showtime->price }}"
                                            data-name="{{ $seat->seat_number }}" {{ $isBooked ? 'disabled' : '' }}>

                                        <label class="btn {{ $isBooked ? 'btn-danger' : 'btn-outline-light' }} seat-btn shadow-sm"
                                            for="seat-{{ $seat->id }}"
                                            style="width: 45px; height: 40px; line-height: 26px; font-weight: bold;">
                                            {{ $seat->column }}
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        <!-- Ghi Chú Chú Thích Ghế -->
                        <div class="d-flex justify-content-center gap-4 mt-4 pt-3 border-top border-secondary">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-outline-light border px-3 py-2">Ghế trống</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark px-3 py-2">Đang chọn</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger px-3 py-2">Đã được đặt</span>
                            </div>
                        </div>
                </div>
            </div>

            <!-- Cột Bên Phải: Thông Tin Suất Chiếu & Tóm Tắt Đơn Hàng -->
            <div class="col-lg-4">
                <div class="card card-custom p-4 shadow border-0">
                    <h5 class="text-warning fw-bold mb-3 border-bottom border-secondary pb-2">THÔNG TIN ĐẶT VÉ</h5>

                    <h4 class="text-light fw-bold">{{ $showtime->movie->title }}</h4>
                    <p class="text-muted small mb-2"><i
                            class="bi bi-geo-alt me-1"></i>{{ $showtime->room->cinema->name ?? 'Rạp MovieTicket' }} -
                        {{ $showtime->room->name }}</p>
                    <p class="text-muted small mb-3"><i class="bi bi-clock me-1"></i>Suất: <strong
                            class="text-warning">{{ date('H:i - d/m/Y', strtotime($showtime->start_time)) }}</strong></p>

                    <div class="border-top border-bottom border-secondary py-3 my-3">
                        <p class="mb-1 d-flex justify-content-between">
                            <span>Ghế đã chọn:</span>
                            <strong id="selectedSeatsText" class="text-warning">Chưa chọn</strong>
                        </p>
                        <p class="mb-0 d-flex justify-content-between">
                            <span>Giá vé mỗi ghế:</span>
                            <span>{{ number_format($showtime->price) }} VNĐ</span>
                        </p>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fs-5 fw-bold">Tổng tiền:</span>
                        <span id="totalPriceText" class="fs-4 fw-bold text-success">0 VNĐ</span>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2 fs-5">
                        XÁC NHẬN ĐẶT VÉ
                    </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Tính Toán Tiền Trực Tiếp -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.seat-checkbox');
            const selectedSeatsText = document.getElementById('selectedSeatsText');
            const totalPriceText = document.getElementById('totalPriceText');

            checkboxes.forEach(chk => {
                chk.addEventListener('change', function () {
                    let selectedNames = [];
                    let total = 0;

                    checkboxes.forEach(c => {
                        if (c.checked) {
                            selectedNames.push(c.dataset.name);
                            total += parseFloat(c.dataset.price);
                        }
                    });

                    if (selectedNames.length > 0) {
                        selectedSeatsText.textContent = selectedNames.join(', ');
                    } else {
                        selectedSeatsText.textContent = 'Chưa chọn';
                    }

                    totalPriceText.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' VNĐ';
                });
            });
        });
    </script>
@endsection