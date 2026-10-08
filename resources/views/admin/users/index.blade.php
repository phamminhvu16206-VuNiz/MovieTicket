@extends('layouts.admin')
@section('title','Quản lý người dùng') @section('page_title','Quản Lý Người Dùng')
@section('content')
<div class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Họ tên</th><th>Email</th><th>Điện thoại</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
@forelse($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->phone }}</td><td><span class="badge {{ $user->status==='active'?'bg-success':'bg-danger' }}">{{ $user->status==='active'?'Hoạt động':'Đã khóa' }}</span></td><td><form method="POST" action="{{ route('admin.users.toggle-lock',$user) }}">@csrf @method('PATCH')<button class="btn btn-sm {{ $user->status==='active'?'btn-outline-danger':'btn-outline-success' }}">{{ $user->status==='active'?'Khóa':'Mở khóa' }}</button></form></td></tr>@empty<tr><td colspan="5" class="text-center py-4">Chưa có người dùng.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $users->links() }}</div>
@endsection
