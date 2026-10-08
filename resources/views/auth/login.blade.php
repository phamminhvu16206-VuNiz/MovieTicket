@extends('layouts.app')

@section('title', 'Đăng Nhập - MovieTicket')

@section('content')
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card card-custom p-4 shadow-lg">
                    <h3 class="text-center text-warning mb-4"><i class="bi bi-person-lock me-2"></i>Đăng Nhập</h3>

                    <form action="{{ route('login') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" placeholder="Nhập email...">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror" placeholder="Nhập mật khẩu...">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold py-2 mt-3">ĐĂNG NHẬP</button>
                    </form>

                    <div class="text-end mt-2"><a href="{{ route('password.request') }}" class="text-info">Quên mật khẩu?</a></div>
                    <div class="text-center mt-4">
                        <p class="mb-0 text-muted">Chưa có tài khoản? <a href="{{ route('register') }}"
                                class="text-warning text-decoration-none">Đăng ký ngay</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection