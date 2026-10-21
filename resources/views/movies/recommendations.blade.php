@extends('layouts.app')

@section('title', 'Tối nay xem gì? - MovieTicket')

@section('content')
    <div class="container home-container movie-night-page">
        <section class="movie-night-hero mb-4">
            <span class="eyebrow"><span class="eyebrow-dot"></span> GỢI Ý RIÊNG CHO BUỔI XEM PHIM CỦA BẠN</span>
            <h1>Tối nay xem gì?</h1>
            <p>Chọn tâm trạng và điều kiện của bạn. Bọn mình sẽ tìm phim đang chiếu có suất phù hợp.</p>
        </section>

        <section class="search-panel mb-5" aria-label="Tùy chọn gợi ý phim">
            <form method="GET" action="{{ route('movie.recommendations') }}" class="row g-3 align-items-end">
                <div class="col-12">
                    <span class="form-label d-block">Bạn đang muốn cảm giác nào?</span>
                    <div class="row g-2">
                        @foreach($moods as $key => $mood)
                            <div class="col-6 col-md">
                                <input class="btn-check" type="radio" name="mood" id="mood-{{ $key }}" value="{{ $key }}" @checked($filters['mood'] === $key)>
                                <label class="mood-choice w-100" for="mood-{{ $key }}">
                                    <i class="bi {{ $mood['icon'] }}"></i>
                                    <span>{{ $mood['label'] }}</span>
                                </label>
                            </div>
                        @endforeach
                        <div class="col-6 col-md">
                            <input class="btn-check" type="radio" name="mood" id="mood-any" value="" @checked($filters['mood'] === '')>
                            <label class="mood-choice w-100" for="mood-any">
                                <i class="bi bi-magic"></i>
                                <span>Bất kỳ</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="recommendation-duration" class="form-label">Thời lượng tối đa</label>
                    <select id="recommendation-duration" name="duration" class="form-select">
                        <option value="">Không giới hạn</option>
                        @foreach([90, 120, 150, 180] as $duration)
                            <option value="{{ $duration }}" @selected((string) $filters['duration'] === (string) $duration)>Tối đa {{ $duration }} phút</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="recommendation-budget" class="form-label">Giá vé tối đa / người</label>
                    <select id="recommendation-budget" name="budget" class="form-select">
                        <option value="">Không giới hạn</option>
                        @foreach([80000, 120000, 150000, 200000] as $budget)
                            <option value="{{ $budget }}" @selected((string) $filters['budget'] === (string) $budget)>{{ number_format($budget) }}đ</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="recommendation-time" class="form-label">Thời gian xem</label>
                    <select id="recommendation-time" name="time" class="form-select">
                        @foreach($timeOptions as $key => $label)
                            <option value="{{ $key }}" @selected($filters['time'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-brand"><i class="bi bi-search me-2"></i>Tìm phim hợp gu</button>
                    <button type="submit" name="surprise" value="1" class="btn btn-outline-brand"><i class="bi bi-dice-5 me-2"></i>Chọn giúp tôi</button>
                    <a href="{{ route('movie.recommendations') }}" class="btn btn-light">Xóa lựa chọn</a>
                </div>
            </form>
        </section>

        @if($selectedMovie)
            <section class="surprise-result mb-5" aria-live="polite">
                <div class="surprise-result-icon"><i class="bi bi-stars"></i></div>
                <div>
                    <span class="section-kicker">MÌNH CHỌN BỘ NÀY CHO BẠN</span>
                    <h2>{{ $selectedMovie->title }}</h2>
                    <p>{{ $selectedMovie->genre }} · {{ $selectedMovie->duration }} phút</p>
                    <a class="btn btn-brand" href="{{ route('movie.detail', $selectedMovie->id) }}">Xem suất chiếu <i class="bi bi-arrow-right ms-2"></i></a>
                </div>
            </section>
        @endif

        <section class="movie-section mb-5">
            <div class="section-heading">
                <div>
                    <span class="section-kicker">CÁC LỰA CHỌN PHÙ HỢP</span>
                    <h2>{{ $movies->count() }} phim có suất chiếu phù hợp</h2>
                    <p>Giá vé và giờ chiếu bên dưới được lọc theo lựa chọn của bạn.</p>
                </div>
            </div>

            <div class="row g-4">
                @forelse($movies as $movie)
                    @php($nextShowtime = $movie->showtimes->first())
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="movie-card recommendation-card h-100">
                            <a class="recommendation-poster" href="{{ route('movie.detail', $movie->id) }}">
                                <img src="{{ $movie->poster ? asset('images/posters/'.$movie->poster) : asset('images/posters/default.svg') }}" alt="Poster phim {{ $movie->title }}" loading="lazy">
                                <span class="poster-badge"><i class="bi bi-clock me-1"></i>{{ $movie->duration }} phút</span>
                            </a>
                            <div class="movie-card-body">
                                <h3 class="movie-title"><a href="{{ route('movie.detail', $movie->id) }}">{{ $movie->title }}</a></h3>
                                <p class="movie-genre">{{ $movie->genre }}</p>
                                @if($nextShowtime)
                                    <div class="recommendation-showtime">
                                        <span><i class="bi bi-calendar-event me-1"></i>{{ $nextShowtime->start_time->format('H:i - d/m') }}</span>
                                        <strong>{{ number_format($nextShowtime->price) }}đ</strong>
                                    </div>
                                    <p class="small text-muted mt-2 mb-3">{{ $nextShowtime->room->cinema->name }} · {{ $nextShowtime->room->name }}</p>
                                @endif
                                <a href="{{ route('movie.detail', $movie->id) }}" class="btn btn-brand w-100">Chọn suất chiếu</a>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <span class="empty-icon"><i class="bi bi-camera-reels"></i></span>
                            <h3>Chưa tìm thấy suất chiếu phù hợp</h3>
                            <p>Thử tăng ngân sách, thời lượng hoặc chọn thời gian khác.</p>
                            <a href="{{ route('movie.recommendations') }}" class="btn btn-outline-brand">Thử lại</a>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
