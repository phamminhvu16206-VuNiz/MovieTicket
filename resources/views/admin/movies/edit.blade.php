@extends('layouts.admin')

@section('title', 'Sửa Phim - Admin')
@section('page_title', 'Chỉnh Sửa Phim')

@section('content')
    <div class="card shadow-sm border-0 p-4">
        <form action="{{ route('admin.movies.update', $movie->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Tên Phim</label>
                    <input type="text" name="title" class="form-control" value="{{ $movie->title }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Thể Loại</label>
                    <input type="text" name="genre" class="form-control" value="{{ $movie->genre }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Thời Lượng (Phút)</label>
                    <input type="number" name="duration" class="form-control" value="{{ $movie->duration }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Ngày Khởi Chiếu</label>
                    <input type="date" name="release_date" class="form-control" value="{{ $movie->release_date }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Trạng Thái</label>
                    <select name="status" class="form-select">
                        <option value="showing" {{ $movie->status == 'showing' ? 'selected' : '' }}>Đang chiếu</option>
                        <option value="upcoming" {{ $movie->status == 'upcoming' ? 'selected' : '' }}>Sắp chiếu</option>
                        <option value="stopped" {{ $movie->status == 'stopped' ? 'selected' : '' }}>Ngừng chiếu</option>
                    </select>
                </div>

                <div class="col-md-6"><label class="form-label fw-bold">Đạo diễn</label><input type="text" name="director" class="form-control" value="{{ old('director', $movie->director ?? '') }}"></div>
                <div class="col-md-6"><label class="form-label fw-bold">Diễn viên</label><input type="text" name="actors" class="form-control" value="{{ old('actors', $movie->actors ?? '') }}"></div>
                <div class="col-md-8"><label class="form-label fw-bold">Poster</label><input type="text" name="poster" class="form-control" value="{{ old('poster', $movie->poster ?? '') }}" placeholder="ten-file.svg hoặc URL hình ảnh"></div>
                <div class="col-md-4"><label class="form-label fw-bold">Trailer</label><input type="url" name="trailer" class="form-control" value="{{ old('trailer', $movie->trailer ?? '') }}" placeholder="https://..."></div>

                <div class="col-12">
                    <label class="form-label fw-bold">Mô Tả Nội Dung Phim</label>
                    <textarea name="description" class="form-control" rows="4">{{ $movie->description }}</textarea>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary fw-bold px-4">CẬP NHẬT PHIM</button>
                    <a href="{{ route('admin.movies.index') }}" class="btn btn-secondary px-4 me-2">Hủy Bỏ</a>
                </div>
            </div>
        </form>
    </div>
@endsection