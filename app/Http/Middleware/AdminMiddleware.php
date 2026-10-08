<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nếu đã đăng nhập VÀ có quyền admin thì cho qua
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // Nếu không phải admin thì chặn lại và đẩy về trang chủ
        return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập khu vực Quản trị!');
    }
}
