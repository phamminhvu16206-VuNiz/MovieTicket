@extends('layouts.admin')

@section('title', 'Quản Lý Đặt Vé - Movie Admin')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-ticket-detailed text-primary me-2"></i>Quản Lý Đặt Vé
            </h3>
        </div>

        <div id="alert-container"></div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-secondary small text-uppercase">
                                <th class="ps-3">Mã vé</th>
                                <th>Khách hàng</th>
                                <th>Phim / Suất chiếu</th>
                                <th>Ghế</th>
                                <th>Tổng tiền</th>
                                <th style="width: 200px;">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $booking)
                                @php
                                    $seats = $booking->bookingSeats && $booking->bookingSeats->count() > 0
                                        ? $booking->bookingSeats->map(fn($s) => $s->seat->seat_number ?? '')->filter()->join(', ')
                                        : '—';
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-bold text-dark">
                                        <div>#{{ $booking->booking_code }}</div>
                                        <small
                                            class="text-muted fw-normal">{{ \Carbon\Carbon::parse($booking->created_at)->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $booking->user->name ?? 'Khách vãng lai' }}</div>
                                        <small class="text-muted">{{ $booking->user->email ?? '' }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $booking->showtime->movie->title ?? 'N/A' }}</div>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($booking->showtime->start_time ?? now())->format('H:i d/m/Y') }}
                                        </small>
                                    </td>
                                    <td class="fw-bold text-primary">{{ $seats }}</td>
                                    <td class="fw-bold text-danger">{{ number_format($booking->total_amount) }}đ</td>
                                    <td>
                                        {{-- Dropdown cập nhật trạng thái vé --}}
                                        <select
                                            class="form-select form-select-sm status-select {{ $booking->status === 'paid' ? 'border-success text-success fw-bold' : '' }}"
                                            data-id="{{ $booking->id }}">
                                            <option value="pending" {{ $booking->status === 'pending' ? 'selected' : '' }}>Chờ
                                                thanh toán</option>
                                            <option value="paid" {{ $booking->status === 'paid' ? 'selected' : '' }}>Đã thanh toán
                                            </option>
                                            <option value="refund_pending" {{ $booking->status === 'refund_pending' ? 'selected' : '' }}>Chờ hoàn tiền</option>
                                            <option value="refunded" {{ $booking->status === 'refunded' ? 'selected' : '' }}>Đã
                                                hoàn tiền</option>
                                            <option value="cancelled" {{ $booking->status === 'cancelled' ? 'selected' : '' }}>Đã
                                                hủy</option>
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                        Chưa có đơn đặt vé nào.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if(method_exists($bookings, 'links'))
            <div class="mt-3">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>

    {{-- Script tự động cập nhật trạng thái qua AJAX --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selects = document.querySelectorAll('.status-select');

            selects.forEach(select => {
                select.addEventListener('change', function () {
                    const bookingId = this.getAttribute('data-id');
                    const newStatus = this.value;
                    const originalColor = this.style.borderColor;

                    this.disabled = true;

                    fetch(`/admin/bookings/${bookingId}/update-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ status: newStatus })
                    })
                        .then(res => res.json())
                        .then(data => {
                            this.disabled = false;
                            if (data.success) {
                                showAlert('success', data.message);
                            } else {
                                showAlert('danger', 'Có lỗi xảy ra, vui lòng thử lại.');
                            }
                        })
                        .catch(err => {
                            this.disabled = false;
                            showAlert('danger', 'Lỗi kết nối máy chủ!');
                        });
                });
            });

            function showAlert(type, message) {
                const alertBox = `
                <div class="alert alert-${type} alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
                document.getElementById('alert-container').innerHTML = alertBox;
            }
        });
    </script>
@endsection