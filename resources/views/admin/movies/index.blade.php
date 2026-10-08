@extends('layouts.admin')

@section('title', 'Quản Lý Phim - Admin')
@section('page_title', 'Danh Sách Phim')

@section('content')
    <div class="card shadow-sm border-0 p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Tất Cả Phim</h5>
            <a href="{{ route('admin.movies.create') }}" class="btn btn-warning fw-bold">
                <i class="bi bi-plus-circle me-1"></i> Thêm Phim Mới
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#ID</th>
                        <th>Tên Phim</th>
                        <th>Thể Loại</th>
                        <th>Thời Lượng</th>
                        <th>Ngày Khởi Chiếu</th>
                        <th>Trạng Thái</th>
                        <th class="text-center">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movies as $movie)
                        <tr>
                            <td><strong>#{{ $movie->id }}</strong></td>
                            <td class="fw-bold text-primary">{{ $movie->title }}</td>
                            <td><span class="badge bg-secondary">{{ $movie->genre }}</span></td>
                            <td>{{ $movie->duration }} phút</td>
                            <td>{{ date('d/m/Y', strtotime($movie->release_date)) }}</td>
                            <td>
                                @if($movie->status == 'showing')
                                    <span class="badge bg-success">Đang chiếu</span>
                                @elseif($movie->status == 'upcoming')
                                    <span class="badge bg-info">Sắp chiếu</span>
                                @else
                                    <span class="badge bg-danger">Đã ngừng</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.movies.edit', $movie->id) }}"
                                    class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil-square"></i> Sửa
                                </a>
                                <form action="{{ route('admin.movies.destroy', $movie->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa phim này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Chưa có dữ liệu phim nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $movies->links() }}
        </div>
    </div>
@endsection