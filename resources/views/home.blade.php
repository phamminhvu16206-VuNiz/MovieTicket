@extends('layouts.app')
@section('title','MovieTicket - Đặt Vé Xem Phim')
@section('content')
<div class="container home-container">
    <section class="hero-panel mb-5">
        <div class="hero-orb hero-orb-one"></div><div class="hero-orb hero-orb-two"></div>
        <div class="row align-items-center position-relative g-4">
            <div class="col-lg-7">
                <span class="eyebrow"><span class="eyebrow-dot"></span> TRẢI NGHIỆM ĐIỆN ẢNH MỚI</span>
                <h1 class="hero-title">Câu chuyện hay.<br><span>Khoảnh khắc đáng nhớ.</span></h1>
                <p class="hero-copy">Khám phá những bộ phim đang được yêu thích, chọn chỗ ngồi lý tưởng và đặt vé chỉ trong vài bước đơn giản.</p>
                <div class="hero-actions"><a href="#now-showing" class="btn btn-brand btn-lg">Khám phá phim <i class="bi bi-arrow-right ms-2"></i></a><span class="hero-note"><i class="bi bi-shield-check me-1"></i> Đặt vé trực tuyến tiện lợi</span></div>
                <div class="hero-stats"><div><strong>{{ $showingMovies->count() }}</strong><span>Phim đang chiếu</span></div><div><strong>{{ $upcomingMovies->count() }}</strong><span>Phim sắp ra mắt</span></div><div><strong>24/7</strong><span>Chọn vé trực tuyến</span></div></div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="hero-art" aria-hidden="true"><div class="hero-ticket ticket-back"><i class="bi bi-stars"></i><span>YOUR NEXT<br>MOVIE NIGHT</span></div><div class="hero-ticket ticket-front"><div class="ticket-top"><i class="bi bi-ticket-perforated-fill"></i><span>MOVIETICKET</span></div><div class="ticket-screen"><i class="bi bi-play-fill"></i></div><div class="ticket-bottom"><span>GOOD FILMS<br>GOOD TIMES</span><span class="ticket-code">MVT · 2026</span></div></div><span class="art-spark spark-one">✦</span><span class="art-spark spark-two">✧</span><span class="art-circle"></span></div>
            </div>
        </div>
    </section>

    <section class="movie-night-promo mb-5">
        <div class="movie-night-promo-icon"><i class="bi bi-stars"></i></div>
        <div>
            <span class="section-kicker">CHỌN PHIM THEO TÂM TRẠNG</span>
            <h2>Chưa biết xem gì tối nay?</h2>
            <p>Chọn cảm xúc, thời gian và ngân sách — MovieTicket tìm phim hợp gu cho bạn.</p>
        </div>
        <a href="{{ route('movie.recommendations') }}" class="btn btn-brand">Gợi ý phim <i class="bi bi-arrow-right ms-2"></i></a>
    </section>

    <section class="search-panel mb-5" aria-label="Tìm kiếm phim">
        <div class="search-heading"><span class="section-kicker">TÌM KIẾM NHANH</span><h2>Tìm bộ phim dành cho bạn</h2><p>Nhập tên phim hoặc chọn thể loại yêu thích.</p></div>
        <form method="GET" action="{{ route('home') }}" class="row g-3 align-items-end">
            <div class="col-lg-6"><label for="movie-search" class="form-label">Tên phim, diễn viên hoặc đạo diễn</label><div class="input-icon-wrap"><i class="bi bi-search"></i><input id="movie-search" name="q" value="{{ $q }}" class="form-control form-control-lg" placeholder="Ví dụ: Interstellar, Trấn Thành..."></div></div>
            <div class="col-lg-4"><label for="movie-genre" class="form-label">Thể loại</label><select id="movie-genre" name="genre" class="form-select form-select-lg"><option value="">Tất cả thể loại</option>@foreach($genres as $g)<option value="{{ $g }}" @selected($genre===$g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-lg-2 d-grid"><button class="btn btn-brand btn-lg" type="submit">Tìm phim</button></div>
        </form>
        @if($q || $genre)<div class="filter-summary mt-3">Đang lọc kết quả @if($q)<span>“{{ $q }}”</span>@endif @if($genre)<span>{{ $genre }}</span>@endif <a href="{{ route('home') }}">Xóa bộ lọc <i class="bi bi-x-circle"></i></a></div>@endif
    </section>

    <section id="now-showing" class="movie-section mb-5">
        <div class="section-heading"><div><span class="section-kicker">ĐANG ĐƯỢC QUAN TÂM</span><h2>Phim đang chiếu</h2><p>Chọn phim yêu thích và lên lịch cho buổi xem tiếp theo.</p></div><span class="count-pill"><i class="bi bi-film me-1"></i>{{ $showingMovies->total() }} phim</span></div>
        <div class="row g-4">
            @forelse($showingMovies as $movie)
                <div class="col-6 col-md-4 col-lg-3"><article class="movie-card h-100">
                    <a class="poster-wrap" href="{{ route('movie.detail',$movie->id) }}" aria-label="Xem chi tiết {{ $movie->title }}"><img src="{{ $movie->poster ? asset('images/posters/'.$movie->poster) : asset('images/posters/default.svg') }}" class="movie-poster" alt="Poster phim {{ $movie->title }}" loading="lazy"><span class="poster-badge"><i class="bi bi-play-circle-fill me-1"></i>Đang chiếu</span><span class="poster-overlay"><span class="poster-cta"><i class="bi bi-ticket-perforated me-2"></i>Xem lịch chiếu</span></span></a>
                    <div class="movie-card-body"><div class="movie-meta"><span><i class="bi bi-clock me-1"></i>{{ $movie->duration }} phút</span><span class="movie-status-dot"></span></div><h3 class="movie-title"><a href="{{ route('movie.detail',$movie->id) }}">{{ $movie->title }}</a></h3><p class="movie-genre">{{ $movie->genre }}</p><a href="{{ route('movie.detail',$movie->id) }}" class="movie-link">Chi tiết phim <i class="bi bi-arrow-up-right"></i></a>
                        @auth
                            @if(in_array($movie->id, $watchlistMovieIds, true))
                                <a href="{{ route('watchlist.index') }}" class="btn btn-outline-brand btn-sm mt-2"><i class="bi bi-bookmark-check me-1"></i>Đã lưu · Muốn xem</a>
                            @else
                                <form method="POST" action="{{ route('watchlist.store', $movie) }}" class="mt-2">@csrf<button type="submit" class="btn btn-outline-brand btn-sm"><i class="bi bi-bookmark-heart me-1"></i>Muốn xem</button></form>
                            @endif
                        @endauth
                    </div>
                </article></div>
            @empty
                <div class="col-12"><div class="empty-state"><span class="empty-icon"><i class="bi bi-camera-reels"></i></span><h3>Chưa tìm thấy phim phù hợp</h3><p>Thử đổi từ khóa hoặc bỏ bộ lọc để xem thêm phim.</p><a href="{{ route('home') }}" class="btn btn-outline-brand">Xem tất cả phim</a></div></div>
            @endforelse
        </div>
        @if($showingMovies->hasPages())
            <div class="home-pagination mt-4" aria-label="Phân trang phim đang chiếu">
                {{ $showingMovies->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>

    @if($upcomingMovies->total())
        <section id="upcoming" class="movie-section upcoming-section mb-5">
            <div class="section-heading"><div><span class="section-kicker">SẮP CÓ MẶT TẠI RẠP</span><h2>Phim sắp chiếu</h2><p>Đánh dấu những bộ phim bạn không muốn bỏ lỡ.</p></div><span class="count-pill count-pill-purple"><i class="bi bi-calendar-event me-1"></i>{{ $upcomingMovies->total() }} phim</span></div>
            <div class="row g-4">
                @foreach($upcomingMovies as $movie)
                    <div class="col-6 col-md-4 col-lg-3"><article class="movie-card upcoming-card h-100"><a class="poster-wrap" href="{{ route('movie.detail',$movie->id) }}"><img src="{{ $movie->poster ? asset('images/posters/'.$movie->poster) : asset('images/posters/default.svg') }}" class="movie-poster" alt="Poster phim {{ $movie->title }}" loading="lazy"><span class="poster-badge upcoming-badge"><i class="bi bi-calendar2-week me-1"></i>Sắp chiếu</span><span class="poster-overlay"><span class="poster-cta"><i class="bi bi-info-circle me-2"></i>Xem thông tin</span></span></a><div class="movie-card-body"><div class="movie-meta"><span><i class="bi bi-calendar3 me-1"></i>{{ $movie->release_date->format('d/m/Y') }}</span></div><h3 class="movie-title"><a href="{{ route('movie.detail',$movie->id) }}">{{ $movie->title }}</a></h3><p class="movie-genre">{{ $movie->genre }}</p><a href="{{ route('movie.detail',$movie->id) }}" class="movie-link">Xem chi tiết <i class="bi bi-arrow-up-right"></i></a>
                        @auth
                            @if(in_array($movie->id, $watchlistMovieIds, true))
                                <a href="{{ route('watchlist.index') }}" class="btn btn-outline-brand btn-sm mt-2"><i class="bi bi-bookmark-check me-1"></i>Đã lưu · Muốn xem</a>
                            @else
                                <form method="POST" action="{{ route('watchlist.store', $movie) }}" class="mt-2">@csrf<button type="submit" class="btn btn-outline-brand btn-sm"><i class="bi bi-bookmark-heart me-1"></i>Muốn xem</button></form>
                            @endif
                        @endauth
                    </div></article></div>
                @endforeach
            </div>
            @if($upcomingMovies->hasPages())
                <div class="home-pagination home-pagination-upcoming mt-4" aria-label="Phân trang phim sắp chiếu">
                    {{ $upcomingMovies->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    @endif

    <section class="booking-banner"><div class="booking-banner-icon"><i class="bi bi-ticket-perforated-fill"></i></div><div><span class="section-kicker">SẴN SÀNG CHO BUỔI CHIẾU?</span><h2>Một chiếc vé, thật nhiều cảm xúc.</h2><p>Đăng nhập để đặt ghế, quản lý vé và xem lại lịch sử đặt chỗ của bạn.</p></div><a href="{{ route('login') }}" class="btn btn-brand">Bắt đầu ngay <i class="bi bi-arrow-right ms-2"></i></a></section>
</div>
@endsection
