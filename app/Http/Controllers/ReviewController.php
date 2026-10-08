<?php
namespace App\Http\Controllers;
use App\Models\Movie;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ReviewController extends Controller {
 public function store(Request $request,$movieId){
  $data=$request->validate(['rating'=>'required|integer|min:1|max:5','comment'=>'nullable|string|max:1000']);
  $movie=Movie::findOrFail($movieId);
  $booking=Booking::where('user_id',Auth::id())->where('status','paid')->whereHas('showtime',fn($q)=>$q->where('movie_id',$movie->id)->where('start_time','<',now()))->latest()->first();
  if(!$booking) return back()->with('error','Bạn cần đã mua và xem phim này mới có thể đánh giá.');
  Review::updateOrCreate(['user_id'=>Auth::id(),'movie_id'=>$movie->id],['booking_id'=>$booking->id,'rating'=>$data['rating'],'comment'=>$data['comment']??null,'status'=>true]);
  return back()->with('success','Đã lưu đánh giá của bạn.');
 }
 public function destroy($movieId){ Review::where('user_id',Auth::id())->where('movie_id',$movieId)->delete(); return back()->with('success','Đã xóa đánh giá.'); }
}
