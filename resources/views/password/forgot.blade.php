@extends('layouts.app')
@section('title','Quên mật khẩu - MovieTicket')
@section('content')
<div class="container my-5"><div class="row justify-content-center"><div class="col-md-6"><div class="card card-custom p-4 shadow-lg">
<h3 class="text-warning text-center mb-4">QUÊN MẬT KHẨU</h3>
<form method="POST" action="{{ route('password.email') }}">@csrf
<label class="form-label">Email tài khoản</label><input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
<button class="btn btn-warning w-100 mt-4 fw-bold">TẠO LIÊN KẾT ĐẶT LẠI</button></form>
</div></div></div></div>
@endsection
