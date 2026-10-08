@extends('layouts.app')
@section('title','Đặt lại mật khẩu - MovieTicket')
@section('content')
<div class="container my-5"><div class="row justify-content-center"><div class="col-md-6"><div class="card card-custom p-4 shadow-lg">
<h3 class="text-warning text-center mb-4">ĐẶT LẠI MẬT KHẨU</h3>
<form method="POST" action="{{ route('password.update') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}"><input type="hidden" name="email" value="{{ $email }}">
<label class="form-label">Mật khẩu mới</label><input type="password" name="password" class="form-control mb-3" required>
<label class="form-label">Xác nhận mật khẩu</label><input type="password" name="password_confirmation" class="form-control" required>
<button class="btn btn-warning w-100 mt-4 fw-bold">ĐỔI MẬT KHẨU</button></form>
</div></div></div></div>
@endsection
