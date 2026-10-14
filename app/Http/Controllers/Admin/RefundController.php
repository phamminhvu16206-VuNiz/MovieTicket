<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;

class RefundController extends Controller
{
    public function index()
    {
        $refundRequests = DB::table('refund_requests')
            ->join('bookings', 'refund_requests.booking_id', '=', 'bookings.id')
            ->join('users', 'refund_requests.user_id', '=', 'users.id')
            ->select(
                'refund_requests.*',
                'bookings.booking_code',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->orderBy('refund_requests.created_at', 'desc')
            ->paginate(15);

        return view('admin.refunds.index', compact('refundRequests'));
    }

    /**
     * Admin xác nhận đã chuyển khoản -> Cập nhật trạng thái vé thành refunded
     */
    public function approve($id)
    {
        $refundReq = DB::table('refund_requests')->where('id', $id)->first();

        if (!$refundReq) {
            return back()->with('error', 'Yêu cầu hoàn tiền không tồn tại.');
        }

        DB::transaction(function () use ($refundReq) {
            // 1. Cập nhật trạng thái yêu cầu hoàn tiền trong admin
            DB::table('refund_requests')
                ->where('id', $refundReq->id)
                ->update([
                    'status'     => 'completed',
                    'updated_at' => now(),
                ]);

            // 2. Cập nhật trạng thái vé của khách hàng thành 'refunded'
            Booking::where('id', $refundReq->booking_id)->update([
                'status' => 'refunded'
            ]);

            DB::table('payments')->where('booking_id', $refundReq->booking_id)->update([
                'status' => 'refunded'
            ]);
        });

        return back()->with('success', 'Đã xác nhận hoàn tiền! Trạng thái vé của khách hàng đã chuyển sang ĐÃ HOÀN TIỀN.');
    }
}
