@extends('layouts.admin')
@section('title','Sửa suất chiếu') @section('page_title','Sửa Suất Chiếu')
@section('content')<div class="card border-0 shadow-sm p-4"><form method="POST" action="{{ route('admin.showtimes.update',$showtime) }}">@csrf @method('PUT')
<div class="row g-3"><div class="col-md-6"><label class="form-label">Phim</label><select name="movie_id" class="form-select" required>@foreach($movies as $m)<option value="{{ $m->id }}" @selected($m->id==$showtime->movie_id)>{{ $m->title }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Phòng</label><select name="room_id" class="form-select" required>@foreach($rooms as $r)<option value="{{ $r->id }}" @selected($r->id==$showtime->room_id)>{{ $r->cinema->name }} - {{ $r->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Bắt đầu</label><input type="datetime-local" name="start_time" value="{{ $showtime->start_time->format('Y-m-d\TH:i') }}" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Giá vé</label><input type="number" name="price" value="{{ $showtime->price }}" class="form-control" min="0" required></div>
<div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status" class="form-select"><option value="active" @selected($showtime->status==='active')>Hoạt động</option><option value="cancelled" @selected($showtime->status==='cancelled')>Đã hủy</option></select></div></div>
<button class="btn btn-warning mt-4 fw-bold">LƯU THAY ĐỔI</button> <a href="{{ route('admin.showtimes.index') }}" class="btn btn-secondary mt-4">Quay lại</a></form></div>@endsection
