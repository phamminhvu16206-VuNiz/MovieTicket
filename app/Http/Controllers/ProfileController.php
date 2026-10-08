<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit() { return view('profile.edit', ['user'=>Auth::user()]); }
    public function update(Request $request)
    {
        $user=Auth::user();
        $data=$request->validate(['name'=>'required|string|max:255','phone'=>'nullable|digits_between:9,11']);
        $user->update($data);
        return back()->with('success','Cập nhật thông tin cá nhân thành công.');
    }
    public function password(Request $request)
    {
        $data=$request->validate(['current_password'=>'required','password'=>'required|min:6|confirmed']);
        if(!Hash::check($data['current_password'],Auth::user()->password)) return back()->withErrors(['current_password'=>'Mật khẩu hiện tại không đúng.']);
        Auth::user()->update(['password'=>Hash::make($data['password'])]);
        return back()->with('success','Đổi mật khẩu thành công.');
    }
}
