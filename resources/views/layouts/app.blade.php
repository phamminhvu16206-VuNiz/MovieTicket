<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MovieTicket - Đặt Vé Xem Phim Trực Tuyến')</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
        }

        .navbar-custom {
            background-color: #1e293b;
        }

        .card-custom {
            background-color: #1e293b;
            border: 1px solid #334155;
            color: #fff;
        }

        .footer-custom {
            background-color: #020617;
            border-top: 1px solid #334155;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning fs-3" href="{{ route('home') }}">
                <i class="bi bi-film me-2"></i>MovieTicket
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link text-light fs-5" href="{{ route('home') }}">Trang Chủ</a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-warning fw-bold" href="#" id="navbarDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="navbarDropdown">
                                <!-- NÚT XEM LỊCH SỬ ĐẶT VÉ -->
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2 text-info"></i>Hồ Sơ Cá Nhân</a></li><li><a class="dropdown-item" href="{{ route('profile.bookings') }}">
                                        <i class="bi bi-clock-history me-2 text-warning"></i>Lịch Sử Đặt Vé
                                    </a>
                                </li>

                                @if(Auth::user()->role === 'admin')
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-info" href="{{ route('admin.dashboard') }}">
                                            <i class="bi bi-speedometer2 me-2"></i>Trang Quản Trị Admin
                                        </a>
                                    </li>
                                @endif

                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-box-arrow-right me-2"></i>Đăng Xuất
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="btn btn-outline-warning btn-sm me-2" href="{{ route('login') }}">Đăng Nhập</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-warning btn-sm fw-bold text-dark" href="{{ route('register') }}">Đăng Ký</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Thông báo Alert (Flash Message) -->
    <div class="container mt-3">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="py-4">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer-custom py-4 text-center text-muted mt-5">
        <div class="container">
            <p class="mb-1">&copy; 2026 MovieTicket - Hệ Thống Bán Vé Xem Phim Trực Tuyến đồ án sinh viên.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>