<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\User; use Illuminate\Http\Request;
class UserController extends Controller
{
 public function index(){ $users=User::where('role','!=','admin')->latest()->paginate(15); return view('admin.users.index',compact('users')); }
 public function toggleLock(User $user){ if($user->role==='admin') return back()->with('error','Không thể khóa tài khoản Admin.'); $user->update(['status'=>$user->status==='locked'?'active':'locked']); return back()->with('success',$user->status==='locked'?'Đã khóa tài khoản.':'Đã mở khóa tài khoản.'); }
}
