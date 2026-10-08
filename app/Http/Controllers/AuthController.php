<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    public function showFormLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required'], ['email.required'=>'Vui lòng nhập Email!','email.email'=>'Email không đúng định dạng!','password.required'=>'Vui lòng nhập mật khẩu!']);
        if (Auth::attempt($credentials)) {
            if (Auth::user()->status === 'locked') { Auth::logout(); return back()->withErrors(['email'=>'Tài khoản của bạn đã bị khóa!'])->onlyInput('email'); }
            $request->session()->regenerate();
            return Auth::user()->role === 'admin' ? redirect()->route('admin.dashboard')->with('success','Đăng nhập trang quản trị thành công!') : redirect()->intended(route('home'))->with('success','Đăng nhập thành công!');
        }
        return back()->withErrors(['email'=>'Email hoặc mật khẩu không chính xác!'])->onlyInput('email');
    }

    public function showFormRegister() { return view('auth.register'); }

    public function register(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:255','email'=>'required|email|unique:users,email','phone'=>'required|digits_between:9,11','password'=>'required|min:6|confirmed']);
        $user = User::create([...$data, 'password'=>Hash::make($data['password']), 'role'=>'customer', 'status'=>'active']);
        Auth::login($user);
        return redirect()->route('home')->with('success','Đăng ký tài khoản thành công!');
    }

    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('home')->with('success','Đã đăng xuất tài khoản!');
    }

    public function showForgotPassword() { return view('password.forgot'); }

    public function sendResetLink(Request $request)
    {
        $data = $request->validate(['email'=>'required|email']);
        $user = User::where('email',$data['email'])->first();
        if (!$user) return back()->with('error','Không tìm thấy tài khoản với email này.');
        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(['email'=>$user->email],['token'=>Hash::make($token),'created_at'=>now()]);
        return redirect()->route('password.reset', ['token'=>$token,'email'=>$user->email])->with('success','Đã tạo liên kết đặt lại mật khẩu.');
    }

    public function showResetPassword(Request $request, string $token) { return view('password.reset', ['token'=>$token,'email'=>$request->query('email')]); }

    public function resetPassword(Request $request)
    {
        $data = $request->validate(['email'=>'required|email','token'=>'required','password'=>'required|min:6|confirmed']);
        $row = DB::table('password_reset_tokens')->where('email',$data['email'])->first();
        if (!$row || Carbon::parse($row->created_at)->addMinutes(60)->isPast() || !Hash::check($data['token'],$row->token)) return back()->withErrors(['email'=>'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
        User::where('email',$data['email'])->update(['password'=>Hash::make($data['password'])]);
        DB::table('password_reset_tokens')->where('email',$data['email'])->delete();
        return redirect()->route('login')->with('success','Đổi mật khẩu thành công. Bạn có thể đăng nhập lại.');
    }
}
