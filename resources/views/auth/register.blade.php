@extends('layouts.app')

@section('title', 'Đăng Ký - MovieTicket')

@section('content')
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-custom p-4 shadow-lg">
                    <h3 class="text-center text-warning mb-4"><i class="bi bi-person-plus me-2"></i>Đăng Ký Tài Khoản</h3>

                    <form action="{{ route('register') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="Nhập họ tên...">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" placeholder="Nhập email...">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}" placeholder="Nhập số điện thoại...">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu</label>
                                <input type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror" placeholder="Mật khẩu...">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" name="password_confirmation" class="form-control"
                                    placeholder="Nhập lại mật khẩu...">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold py-2 mt-3">ĐĂNG KÝ TÀI KHOẢN</button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0 text-muted">Đã có tài khoản? <a href="{{ route('login') }}"
                                class="text-warning text-decoration-none">Đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection