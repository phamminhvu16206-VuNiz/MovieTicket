<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\Booking; use Illuminate\Http\Request;
class BookingController extends Controller
{
 public function index(){ $bookings=Booking::with(['user','showtime.movie','showtime.room.cinema','bookingSeats.seat'])->latest()->paginate(15); return view('admin.bookings.index',compact('bookings')); }
 public function updateStatus(Request $request, Booking $booking){ $data=$request->validate(['status'=>'required|in:pending,paid,cancelled,completed']); $booking->update($data); return back()->with('success','Đã cập nhật trạng thái đơn vé.'); }
}
