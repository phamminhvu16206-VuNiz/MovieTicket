@extends('layouts.admin')

@section('title', 'Quản Lý Suất Chiếu - Admin')
@section('page_title', 'Danh Sách Suất Chiếu')

@section('content')
    <div class="card shadow-sm border-0 p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Lịch Chiếu Phim</h5>
            <a href="{{ route('admin.showtimes.create') }}" class="btn btn-warning fw-bold">
                <i class="bi bi-plus-circle me-1"></i> Thêm Suất Chiếu Mới
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#ID</th>
                        <th>Tên Phim</th>
                        <th>Rạp / Phòng</th>
                        <th>Thời Gian Chiếu</th>
                        <th>Giá Vé</th>
                        <th class="text-center">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($showtimes as $showtime)
                        <tr>
                            <td><strong>#{{ $showtime->id }}</strong></td>
                            <td class="fw-bold text-primary">{{ $showtime->movie->title ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $showtime->room->cinema->name ?? 'MovieTicket' }}</span>
                                <span class="badge bg-info text-dark">{{ $showtime->room->name ?? '' }}</span>
                            </td>
                            <td class="fw-bold text-warning">{{ date('H:i - d/m/Y', strtotime($showtime->start_time)) }}</td>
                            <td class="text-success fw-bold">{{ number_format($showtime->price) }} VNĐ</td>
                            <td class="text-center">
                                <form action="{{ route('admin.showtimes.destroy', $showtime->id) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i>
                                        Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Chưa có suất chiếu nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $showtimes->links() }}</div>
    </div>
@endsection