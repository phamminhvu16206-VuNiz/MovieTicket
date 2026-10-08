@extends('layouts.app')

@section('title', $movie->title . ' - MovieTicket')

@section('content')
<div class="container my-4">
    <!-- Chi Tiết Phim -->
    <div class="card card-custom p-4 shadow-lg mb-5 border-0">
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <img src="{{ asset('images/posters/'.$movie->poster) }}" class="img-fluid rounded shadow border border-secondary" alt="{{ $movie->title }}">
            </div>
            <div class="col-md-8 d-flex flex-column justify-content-between">
                <div>
                    <h2 class="text-warning fw-bold mb-3">{{ $movie->title }}</h2>
                    <p class="mb-2"><strong class="text-light">Thể loại:</strong> <span class="badge bg-secondary">{{ $movie->genre }}</span></p>
                    <p class="mb-2"><strong class="text-light">Thời lượng:</strong> {{ $movie->duration }} phút</p>
                    <p class="mb-2"><strong class="text-light">Đạo diễn:</strong> {{ $movie->director ?? 'Đang cập nhật' }}</p>
                    <p class="mb-2"><strong class="text-light">Diễn viên:</strong> {{ $movie->actors ?? 'Đang cập nhật' }}</p>
                    <p class="mb-3"><strong class="text-light">Khởi chiếu:</strong> {{ date('d/m/Y', strtotime($movie->release_date)) }}</p>

                    <h5 class="text-warning mt-4 fw-bold">Nội Dung Phim:</h5>
                    <p class="text-light leading-relaxed">{{ $movie->description }}</p>
                </div>

                @if($movie->trailer)
                    <div class="mt-3">
                        <a href="{{ $movie->trailer }}" target="_blank" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-youtube me-1"></i> Xem Trailer Youtube
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Lịch Chiếu & Suất Chiếu -->
    <div class="card card-custom p-4 shadow-lg border-0">
        <h4 class="text-warning fw-bold mb-4 border-start border-4 border-warning ps-3 text-uppercase">
            <i class="bi bi-clock-history me-2"></i>LỊCH CHIẾU VÀ ĐẶT VÉ
        </h4>

        @if($movie->showtimes->count() > 0)
            <div class="row g-3">
                @foreach($movie->showtimes as $showtime)
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 rounded border border-secondary bg-dark d-flex flex-column justify-content-between h-100">
                            <div>
                                <h6 class="fw-bold text-info mb-1"><i class="bi bi-geo-alt-fill me-1"></i>{{ $showtime->room->cinema->name ?? 'Rạp MovieTicket' }}</h6>
                                <p class="small text-muted mb-2"><i class="bi bi-door-open me-1"></i>{{ $showtime->room->name }}</p>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="fs-4 fw-bold text-warning me-2">
                                        {{ date('H:i', strtotime($showtime->start_time)) }}
                                    </span>
                                    <span class="badge bg-outline-light border text-light">
                                        {{ date('d/m/Y', strtotime($showtime->start_time)) }}
                                    </span>
                                </div>
                                <p class="text-success fw-bold mb-3">Giá vé: {{ number_format($showtime->price) }} VNĐ</p>
                            </div>

                            <a href="{{ route('booking.selectSeat', $showtime->id) }}" class="btn btn-warning w-100 fw-bold">
                                CHỌN SUẤT CHIẾU
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-secondary text-center my-3" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>Hiện chưa có suất chiếu nào mở bán cho phim này.
            </div>
        @endif
    </div>
    <div class="card card-custom p-4 shadow-lg border-0 mt-4">
        <h4 class="text-warning fw-bold mb-3"><i class="bi bi-star-fill me-2"></i>ĐÁNH GIÁ PHIM</h4>
        @php($reviews = $movie->reviews->where('status', true))
        <div class="mb-3"><span class="fs-3 text-warning">{{ $reviews->count() ? number_format($reviews->avg('rating'),1) : '—' }}</span> / 5 <span class="text-muted">({{ $reviews->count() }} đánh giá)</span></div>
        @auth
        <form method="POST" action="{{ route('review.store',$movie->id) }}" class="border border-secondary rounded p-3 mb-4">@csrf
            <div class="row g-2"><div class="col-md-3"><select name="rating" class="form-select" required><option value="">Chọn sao</option>@for($i=5;$i>=1;$i--)<option value="{{$i}}">{{$i}} sao</option>@endfor</select></div><div class="col-md-7"><input name="comment" class="form-control" maxlength="1000" placeholder="Nhận xét của bạn..."></div><div class="col-md-2"><button class="btn btn-warning w-100">Gửi</button></div></div>
        </form>
        @else <div class="alert alert-secondary">Đăng nhập để đánh giá phim sau khi đã mua vé.</div> @endauth
        @forelse($reviews->sortByDesc('created_at')->take(10) as $review)<div class="border-bottom border-secondary py-3"><strong>{{ $review->user->name }}</strong> <span class="text-warning">{{ str_repeat('★',$review->rating) }}{{ str_repeat('☆',5-$review->rating) }}</span><div class="text-muted small">{{ $review->created_at->format('d/m/Y H:i') }}</div><div>{{ $review->comment ?: 'Không có nhận xét.' }}</div></div>@empty<div class="text-muted">Chưa có đánh giá.</div>@endforelse
    </div>

</div>
@endsection