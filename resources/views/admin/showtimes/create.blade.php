@extends('layouts.admin')

@section('title', 'Thêm Suất Chiếu - Admin')
@section('page_title', 'Thêm Suất Chiếu Mới')

@section('content')
    <div class="card shadow-sm border-0 p-4">
        <form action="{{ route('admin.showtimes.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Chọn Phim</label>
                    <select name="movie_id" class="form-select" required>
                        <option value="">-- Chọn phim --</option>
                        @foreach($movies as $movie)
                            <option value="{{ $movie->id }}">{{ $movie->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Chọn Phòng Chiếu</label>
                    <select name="room_id" class="form-select" required>
                        <option value="">-- Chọn phòng --</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->cinema->name ?? 'Rạp' }} - {{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Thời Gian Chiếu</label>
                    <input type="datetime-local" name="start_time" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Giá Vé (VNĐ)</label>
                    <input type="number" name="price" class="form-control" value="80000" min="0" required>
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-warning fw-bold px-4">LƯU SUẤT CHIẾU</button>
                    <a href="{{ route('admin.showtimes.index') }}" class="btn btn-secondary px-4 ms-2">Hủy Bỏ</a>
                </div>
            </div>
        </form>
    </div>
@endsection