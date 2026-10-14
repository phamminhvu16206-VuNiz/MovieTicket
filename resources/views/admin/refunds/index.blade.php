@extends('layouts.admin')

@section('title', 'Quản lý yêu cầu hoàn tiền - Movie Admin')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-wallet2 text-warning me-2"></i>Quản Lý Yêu Cầu Hoàn Tiền
            </h3>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-secondary small text-uppercase">
                                <th class="ps-3">Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Số tiền</th>
                                <th>Thông tin ngân hàng nhận</th>
                                <th>Lý do hoàn</th>
                                <th>Trạng thái</th>
                                <th class="text-center pe-3">Thao tác / VietQR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($refundRequests as $req)
                                @php
                                    // Tự động tạo QR chuyển khoản VietQR cho Admin quét bằng App ngân hàng
                                    $vietQrUrl = "https://img.vietqr.io/image/{$req->bank_name}-{$req->account_number}-compact2.png?amount={$req->amount}&addInfo=HOAN TIEN VE {$req->booking_code}&accountName=" . urlencode($req->account_name);
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-bold text-primary">#{{ $req->booking_code }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $req->user_name }}</div>
                                        <div class="small text-muted">{{ $req->user_email }}</div>
                                    </td>
                                    <td class="fw-bold text-danger fs-6">{{ number_format($req->amount) }} VNĐ</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $req->bank_name }}</div>
                                        <div>STK: <code
                                                class="bg-light text-danger px-2 py-1 rounded fw-bold fs-6">{{ $req->account_number }}</code>
                                        </div>
                                        <div class="small text-uppercase text-muted fw-semibold">{{ $req->account_name }}</div>
                                    </td>
                                    <td class="small text-muted" style="max-width: 200px;">
                                        {{ $req->reason ?? 'Khách không nhập lý do' }}
                                    </td>
                                    <td>
                                        @if($req->status === 'pending')
                                            <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock me-1"></i>Chờ
                                                hoàn tiền</span>
                                        @else
                                            <span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Đã hoàn
                                                tiền</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-3">
                                        @if($req->status === 'pending')
                                            <div class="d-flex gap-2 justify-content-center">
                                                <!-- Nút Quét mã VietQR -->
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                    data-bs-target="#qrModal{{ $req->id }}">
                                                    <i class="bi bi-qr-code-scan me-1"></i>Mã VietQR
                                                </button>

                                                <!-- Form xác nhận hoàn tất chuyển khoản -->
                                                <form action="{{ route('admin.refunds.approve', $req->id) }}" method="POST"
                                                    onsubmit="return confirm('Xác nhận ĐÃ CHUYỂN TIỀN hoàn cho khách hàng?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-lg me-1"></i>Xác nhận đã chuyển
                                                    </button>
                                                </form>
                                            </div>

                                            <!-- Modal hiển thị mã VietQR -->
                                            <div class="modal fade" id="qrModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                                    <div class="modal-content border-0 shadow">
                                                        <div class="modal-header bg-dark text-white">
                                                            <h6 class="modal-title text-warning fw-bold"><i
                                                                    class="bi bi-qr-code me-1"></i>VietQR Hoàn Tiền</h6>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-center p-3">
                                                            <img src="{{ $vietQrUrl }}" alt="VietQR"
                                                                class="img-fluid rounded border mb-2">
                                                            <div class="small text-muted">Dùng App Ngân hàng quét mã để chuyển khoản
                                                                hoàn tiền cho khách</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-success small fw-bold"><i class="bi bi-check2-all me-1"></i>Đã hoàn
                                                tất</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                        Hiện chưa có yêu cầu hoàn tiền nào.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if(method_exists($refundRequests, 'links'))
            <div class="mt-3">
                {{ $refundRequests->links() }}
            </div>
        @endif
    </div>
@endsection