<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard - MovieTicket')</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #1e293b;
        }

        .sidebar a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            border-radius: 6px;
            margin-bottom: 4px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #334155;
            color: #f59e0b;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <a href="{{ route('admin.dashboard') }}" class="fs-4 text-warning fw-bold mb-4 d-block px-0">
                    <i class="bi bi-shield-lock me-2"></i>Movie Admin
                </a>
                <nav class="nav flex-column">
                    <a href="{{ route('admin.dashboard') }}"
                        class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i>Tổng Quan
                    </a>
                    <a href="{{ route('admin.movies.index') }}"
                        class="{{ request()->routeIs('admin.movies.*') ? 'active' : '' }}">
                        <i class="bi bi-film me-2"></i>Quản Lý Phim
                    </a>
                    <a href="{{ route('admin.showtimes.index') }}" class="nav-link text-white my-1">
                        <i class="bi bi-clock-history me-2"></i>Quản Lý Suất Chiếu
                    </a>
                    <a href="{{ route('admin.bookings.index') }}" class="nav-link text-white my-1"><i
                            class="bi bi-ticket-detailed me-2"></i>Quản Lý Đặt Vé</a>
                    <a href="{{ route('admin.users.index') }}" class="nav-link text-white my-1"><i
                            class="bi bi-people me-2"></i>Quản Lý Người Dùng</a>
                    <a href="{{ route('admin.refunds.index') }}"
                        class="nav-link text-white {{ request()->routeIs('admin.refunds.*') ? 'active bg-primary' : '' }}">
                        <i class="bi bi-wallet2 me-2"></i> Quản Lý Hoàn Tiền
                    </a>
                    <a href="{{ route('home') }}" target="_blank" class="mt-4 text-info">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Xem Trang Chủ
                    </a>
                </nav>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10 p-4">
                <!-- Top Header Bar -->
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <h3 class="fw-bold text-secondary">@yield('page_title', 'Bảng Quản Trị')</h3>
                    <div class="dropdown">
                        <button class="btn btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle text-warning me-1"></i> {{ Auth::user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-1"></i> Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button"
                class="btn-close" data-bs-dismiss="alert"></button></div>@endif
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>