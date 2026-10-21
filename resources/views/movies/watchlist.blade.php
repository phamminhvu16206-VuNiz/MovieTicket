@extends('layouts.app')

@section('title', 'Phim muốn xem - MovieTicket')

@section('content')
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-kicker">DANH SÁCH CÁ NHÂN</span>
        <h1 class="text-warning fw-bold">Phim muốn xem</h1>
        <p class="text-secondary mb-0">Lưu phim yêu thích để nhận nhắc lịch chiếu ngay trên MovieTicket.</p>
    </div>

    <div class="row g-4">
        <section class="col-lg-7" aria-labelledby="watchlist-heading">
            <div class="card card-custom border-0 shadow-sm p-3 p-md-4">
                <h2 id="watchlist-heading" class="h4 text-warning fw-bold mb-4">
                    <i class="bi bi-bookmark-heart me-2"></i>Danh sách của bạn
                </h2>

                @forelse($movies as $movie)
                    <article class="d-flex gap-3 border-bottom border-secondary pb-3 mb-3">
                        <a href="{{ route('movie.detail', $movie) }}" class="flex-shrink-0">
                            <img
                                src="{{ $movie->poster ? asset('images/posters/'.$movie->poster) : asset('images/posters/default.svg') }}"
                                alt="Poster phim {{ $movie->title }}"
                                class="rounded"
                                style="width: 84px; height: 120px; object-fit: cover;"
                                loading="lazy"
                            >
                        </a>
                        <div class="flex-grow-1 min-w-0">
                            <h3 class="h5 mb-1">
                                <a class="text-decoration-none text-light" href="{{ route('movie.detail', $movie) }}">{{ $movie->title }}</a>
                            </h3>
                            <p class="text-secondary small mb-2">{{ $movie->genre }} · {{ $movie->duration }} phút</p>
                            @if($movie->status === 'upcoming')
                                <p class="small text-info mb-2"><i class="bi bi-calendar-event me-1"></i>Khởi chiếu {{ $movie->release_date->format('d/m/Y') }}</p>
                            @elseif($movie->showtimes->isNotEmpty())
                                <p class="small text-info mb-2">
                                    <i class="bi bi-clock me-1"></i>Suất gần nhất: {{ $movie->showtimes->first()->start_time->format('d/m/Y H:i') }}
                                </p>
                            @else
                                <p class="small text-secondary mb-2">Chưa có suất chiếu sắp tới.</p>
                            @endif
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('movie.detail', $movie) }}" class="btn btn-outline-brand btn-sm">Xem phim</a>
                                <form method="POST" action="{{ route('watchlist.destroy', $movie) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Bỏ khỏi danh sách</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="text-center py-4">
                        <i class="bi bi-bookmark-plus display-5 text-secondary"></i>
                        <p class="text-secondary mt-3">Danh sách còn trống. Hãy lưu phim từ trang chủ hoặc trang chi tiết phim.</p>
                        <a href="{{ route('home') }}" class="btn btn-brand">Khám phá phim</a>
                    </div>
                @endforelse

                @if($movies->hasPages())
                    <div class="mt-3">{{ $movies->links() }}</div>
                @endif
            </div>
        </section>

        <section id="reminders" class="col-lg-5" aria-labelledby="reminders-heading">
            <div class="card card-custom border-0 shadow-sm p-3 p-md-4">
                <h2 id="reminders-heading" class="h4 text-warning fw-bold mb-4">
                    <i class="bi bi-bell me-2"></i>Lời nhắc gần đây
                </h2>

                @forelse($reminders as $reminder)
                    <article class="border-bottom border-secondary pb-3 mb-3 {{ $reminder->read_at ? 'opacity-75' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <h3 class="h6 mb-1 {{ $reminder->read_at ? 'text-light' : 'text-info' }}">{{ $reminder->data['title'] }}</h3>
                            @unless($reminder->read_at)
                                <span class="badge bg-warning text-dark">Mới</span>
                            @endunless
                        </div>
                        <p class="small text-secondary mb-2">{{ $reminder->data['message'] }}</p>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <a href="{{ route('movie.detail', $reminder->data['movie_id']) }}" class="small link-info">Xem phim</a>
                            <span class="small text-secondary">{{ $reminder->created_at->diffForHumans() }}</span>
                        </div>
                        @unless($reminder->read_at)
                            <form method="POST" action="{{ route('watchlist.reminders.read', $reminder->id) }}" class="mt-2">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-link btn-sm p-0 text-secondary">Đánh dấu đã xem</button>
                            </form>
                        @endunless
                    </article>
                @empty
                    <p class="text-secondary mb-0">Chưa có lời nhắc. Bạn sẽ thấy thông báo tại đây khi phim sắp chiếu, có suất mới hoặc sắp hết lịch chiếu.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
