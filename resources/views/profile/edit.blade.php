@extends('layouts.app')
@section('title','Hồ sơ cá nhân - MovieTicket')
@section('content')
<div class="container my-5"><div class="row g-4"><div class="col-md-6"><div class="card card-custom p-4 shadow"><h4 class="text-warning">THÔNG TIN CÁ NHÂN</h4><form method="POST" action="{{ route('profile.update') }}">@csrf @method('PUT')
<label class="form-label mt-3">Họ tên</label><input name="name" value="{{ old('name',$user->name) }}" class="form-control" required>
<label class="form-label mt-3">Email</label><input value="{{ $user->email }}" class="form-control" disabled>
<label class="form-label mt-3">Số điện thoại</label><input name="phone" value="{{ old('phone',$user->phone) }}" class="form-control">
<button class="btn btn-warning mt-4 fw-bold">LƯU THAY ĐỔI</button></form></div></div>
<div class="col-md-6"><div class="card card-custom p-4 shadow"><h4 class="text-warning">ĐỔI MẬT KHẨU</h4><form method="POST" action="{{ route('profile.password') }}">@csrf @method('PUT')
<label class="form-label mt-3">Mật khẩu hiện tại</label><input type="password" name="current_password" class="form-control" required>
<label class="form-label mt-3">Mật khẩu mới</label><input type="password" name="password" class="form-control" required>
<label class="form-label mt-3">Xác nhận</label><input type="password" name="password_confirmation" class="form-control" required>
<button class="btn btn-outline-warning mt-4 fw-bold">ĐỔI MẬT KHẨU</button></form></div></div></div></div>
@endsection
