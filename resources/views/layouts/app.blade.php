<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8fafc">
    <meta name="description" content="MovieTicket - Khám phá phim hay, chọn ghế yêu thích và đặt vé xem phim trực tuyến dễ dàng.">
    <title>@yield('title', 'MovieTicket - Đặt Vé Xem Phim')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="site-body">
    <nav class="navbar navbar-expand-lg navbar-light site-navbar sticky-top">
        <div class="container py-1">
            <a class="navbar-brand brand-mark" href="{{ route('home') }}" aria-label="MovieTicket trang chủ">
                <span class="brand-icon"><i class="bi bi-ticket-perforated-fill"></i></span>
                <span>Movie<span class="brand-accent">Ticket</span><small>YOUR CINEMA, YOUR MOMENT</small></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Mở menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-lg-5 me-auto gap-lg-2">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}"><i class="bi bi-house-door me-1"></i>Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('movie.recommendations') ? 'active' : '' }}" href="{{ route('movie.recommendations') }}"><i class="bi bi-stars me-1"></i>Tối nay xem gì?</a></li>
                </ul>
                <div class="navbar-nav align-items-lg-center gap-2 py-3 py-lg-0">
                    @auth
                        <a
                            class="btn notification-menu"
                            href="{{ route('watchlist.index') }}#reminders"
                            aria-label="Mở lời nhắc{{ $unreadReminderCount > 0 ? ', '.$unreadReminderCount.' lời nhắc chưa đọc' : '' }}"
                            title="{{ $unreadReminderCount > 0 ? $unreadReminderCount.' lời nhắc chưa đọc' : 'Lời nhắc' }}"
                        >
                            <i class="bi bi-bell" aria-hidden="true"></i>
                            @if($unreadReminderCount > 0)
                                <span class="notification-badge" aria-hidden="true">{{ $unreadReminderCount > 99 ? '99+' : $unreadReminderCount }}</span>
                            @endif
                            <span class="visually-hidden">Lời nhắc</span>
                        </a>
                        <div class="dropdown">
                            <button class="btn user-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span class="user-avatar"><i class="bi bi-person-fill"></i></span>{{ Auth::user()->name }}</button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                                <li><a class="dropdown-item py-2" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Hồ sơ cá nhân</a></li>
                                <li><a class="dropdown-item py-2" href="{{ route('profile.bookings') }}"><i class="bi bi-ticket-perforated me-2"></i>Lịch sử đặt vé</a></li>
                                <li><a class="dropdown-item py-2" href="{{ route('watchlist.index') }}"><i class="bi bi-bookmark-heart me-2"></i>Phim muốn xem</a></li>
                                @if(Auth::user()->role === 'admin')
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Trang quản trị</a></li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li><form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="dropdown-item py-2 text-danger"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</button></form></li>
                            </ul>
                        </div>
                    @else
                        <a class="btn btn-login" href="{{ route('login') }}">Đăng nhập</a>
                        <a class="btn btn-brand" href="{{ route('register') }}">Tạo tài khoản <i class="bi bi-arrow-up-right ms-1"></i></a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <div class="container flash-wrap">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button></div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert"><strong>Vui lòng kiểm tra lại thông tin.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button></div>
        @endif
    </div>

    <main class="site-main">
        @yield('content')
    </main>

    <footer class="site-footer mt-5">
        <div class="container py-4 py-lg-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <a class="navbar-brand brand-mark mb-2 d-inline-flex" href="{{ route('home') }}"><span class="brand-icon"><i class="bi bi-ticket-perforated-fill"></i></span><span>Movie<span class="brand-accent">Ticket</span><small>YOUR CINEMA, YOUR MOMENT</small></span></a>
                    <p class="footer-copy mb-0">Một trải nghiệm đặt vé gọn gàng, tiện lợi — để bạn dành nhiều thời gian hơn cho những câu chuyện trên màn ảnh.</p>
                </div>
                <div class="col-lg-6 text-lg-end">
                    <div class="footer-links mb-2"><a href="{{ route('home') }}">Trang chủ</a><a href="{{ route('profile.bookings') }}" @guest onclick="return false" @endguest>Lịch sử vé</a><a href="{{ route('login') }}">Tài khoản</a></div>
                    <small class="text-secondary">© {{ date('Y') }} MovieTicket · Đồ án hệ thống đặt vé xem phim</small>
                </div>
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
