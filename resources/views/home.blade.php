@extends('layouts.app')

@section('title', 'MovieTicket - Đặt Vé Xem Phim')

@section('content')
    <style>
        .hero-banner {
            background: linear-gradient(135deg, #111827, #312e81, #7c2d12);
        }

        .card-custom {
            background-color: #1a202c;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
        }

        .poster-img {
            height: 360px;
            object-fit: cover;
            width: 100%;
        }
    </style>

    <div class="container my-4">
        {{-- Banner Hero --}}
        <div class="p-4 p-md-5 rounded-4 shadow mb-5 hero-banner text-white">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark mb-2">MOVIETICKET</span>
                    <h1 class="display-5 fw-bold">Đặt vé nhanh – Chọn ghế dễ dàng</h1>
                    <p class="lead text-light">Khám phá phim đang chiếu, phim sắp ra mắt và đặt vé trực tuyến chỉ trong vài
                        bước.</p>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block">
                    <i class="bi bi-film" style="font-size: 8rem; color: #fbbf24;"></i>
                </div>
            </div>
        </div>

        {{-- Form Tìm kiếm & Lọc --}}
        <form method="GET" action="{{ route('home') }}" class="card card-custom p-3 mb-5 shadow border-0">
            <div class="row g-2">
                <div class="col-md-6">
                    <input name="q" value="{{ $q }}" class="form-control bg-dark text-white border-secondary"
                        placeholder="Tìm tên phim, diễn viên, đạo diễn...">
                </div>
                <div class="col-md-4">
                    <select name="genre" class="form-select bg-dark text-white border-secondary">
                        <option value="">Tất cả thể loại</option>
                        @foreach($genres as $g)
                            <option value="{{ $g }}" @selected($genre === $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-warning w-100 fw-bold">
                        <i class="bi bi-search"></i> Tìm
                    </button>
                    @if($q || $genre)
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary" title="Xóa bộ lọc">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Phim Đang Chiếu --}}
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4 border-start border-4 border-warning ps-3">
                <h3 class="fw-bold mb-0 text-white">PHIM ĐANG CHIẾU</h3>
                <span class="badge bg-warning text-dark fs-6">{{ $showingMovies->count() }} phim</span>
            </div>

            <div class="row g-4">
                @forelse($showingMovies as $movie)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card card-custom h-100 shadow border-0 overflow-hidden text-white">
                            <div class="position-relative">
                                <img src="{{ $movie->poster ? asset('images/posters/' . $movie->poster) : asset('images/posters/default.svg') }}"
                                    class="card-img-top poster-img" alt="{{ $movie->title }}">
                                @if($movie->duration)
                                    <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
                                        {{ $movie->duration }} phút
                                    </span>
                                @endif
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="fw-bold text-warning mb-1 text-truncate" title="{{ $movie->title }}">
                                    {{ $movie->title }}</h5>
                                <p class="text-muted small mb-3 text-truncate">{{ $movie->genre ?? 'Chưa phân loại' }}</p>
                                <a href="{{ route('movie.detail', $movie->id) }}"
                                    class="btn btn-outline-warning mt-auto fw-bold">
                                    ĐẶT VÉ
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-secondary text-center py-4">
                            <i class="bi bi-exclamation-circle fs-4 d-block mb-2"></i>
                            Không tìm thấy phim đang chiếu phù hợp.
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Phim Sắp Chiếu --}}
        @if($upcomingMovies->count())
            <section class="mb-5">
                <div class="border-start border-4 border-info ps-3 mb-4">
                    <h3 class="fw-bold mb-0 text-white">PHIM SẮP CHIẾU</h3>
                </div>

                <div class="row g-4">
                    @foreach($upcomingMovies as $movie)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card card-custom h-100 shadow border-0 overflow-hidden text-white">
                                <img src="{{ $movie->poster ? asset('images/posters/' . $movie->poster) : asset('images/posters/default.svg') }}"
                                    class="card-img-top poster-img" alt="{{ $movie->title }}">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="text-info fw-bold mb-1 text-truncate" title="{{ $movie->title }}">{{ $movie->title }}
                                    </h5>
                                    <p class="small text-muted mb-3">
                                        Khởi chiếu:
                                        @if($movie->release_date)
                                            {{ \Carbon\Carbon::parse($movie->release_date)->format('d/m/Y') }}
                                        @else
                                            Đang cập nhật
                                        @endif
                                    </p>
                                    <a href="{{ route('movie.detail', $movie->id) }}" class="btn btn-outline-info mt-auto fw-bold">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection