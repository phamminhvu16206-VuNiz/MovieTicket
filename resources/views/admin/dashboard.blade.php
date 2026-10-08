@extends('layouts.admin')

@section('title', 'Admin Dashboard - MovieTicket')
@section('page_title', 'Tổng Quan Hệ Thống')

@section('content')
    <!-- Thẻ Thống Kê Tổng Quan -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white border-0 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1">Tổng Số Phim</h6>
                        <h2 class="fw-bold mb-0">{{ $totalMovies }}</h2>
                    </div>
                    <i class="bi bi-film display-5 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white border-0 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1">Tổng Vé Đã Bán</h6>
                        <h2 class="fw-bold mb-0">{{ $totalBookings }} vé</h2>
                    </div>
                    <i class="bi bi-ticket-perforated-fill display-5 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark border-0 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-uppercase fw-bold mb-1">Tổng Doanh Thu</h6>
                        <h2 class="fw-bold mb-0">{{ number_format($totalRevenue) }} VNĐ</h2>
                    </div>
                    <i class="bi bi-cash-stack display-5 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><small class="text-muted">Giao dịch thành công</small><h3 class="fw-bold text-success">{{ $totalPayments }}</h3></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><small class="text-muted">Đánh giá phim</small><h3 class="fw-bold text-warning">{{ $totalReviews }}</h3></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><small class="text-muted">Combo đã bán</small><h3 class="fw-bold text-primary">{{ $totalComboSales }}</h3></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><small class="text-muted">Voucher đã dùng</small><h3 class="fw-bold text-danger">{{ $totalVoucherUsed }}</h3></div></div>
    </div>

    <!-- Khu Vực Biểu Đồ Thống Kê -->
    <div class="row g-4">
        <!-- Biểu đồ Doanh Thu -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 p-3">
                <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Doanh Thu
                    7 Ngày Gần Nhất</h5>
                <canvas id="revenueChart" height="180"></canvas>
            </div>
        </div>

        <!-- Biểu đồ Tỷ Lệ Vé Theo Phim -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 p-3">
                <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-pie-chart-fill me-2 text-warning"></i>Tỷ Lệ Vé Bán
                    Ra Theo Phim</h5>
                <canvas id="movieChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Nhúng thư viện Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // 1. Cấu hình Biểu đồ Cột (Doanh Thu)
        const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctxRevenue, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartDates) !!},
                datasets: [{
                    label: 'Doanh Thu (VNĐ)',
                    data: {!! json_encode($chartRevenues) !!},
                    backgroundColor: 'rgba(13, 110, 253, 0.75)',
                    borderColor: '#0d6efd',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // 2. Cấu hình Biểu đồ Tròn (Vé Theo Phim)
        const ctxMovie = document.getElementById('movieChart').getContext('2d');
        new Chart(ctxMovie, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($movieLabels) !!},
                datasets: [{
                    data: {!! json_encode($movieCounts) !!},
                    backgroundColor: ['#0d6efd', '#ffc107', '#198754', '#dc3545', '#0dcaf0', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
@endsection