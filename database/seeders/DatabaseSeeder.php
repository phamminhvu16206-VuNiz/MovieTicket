<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use App\Models\{User,Movie,Cinema,Room,Seat,Showtime,Combo,Voucher}; use Illuminate\Support\Facades\Hash; use Carbon\Carbon;
class DatabaseSeeder extends Seeder
{
 public function run(): void
 {
  User::updateOrCreate(['email'=>'admin@gmail.com'],['name'=>'Quản trị viên','password'=>Hash::make('12345678'),'phone'=>'0901234567','role'=>'admin','status'=>'active']);
  User::updateOrCreate(['email'=>'customer@gmail.com'],['name'=>'Nguyễn Văn A','password'=>Hash::make('12345678'),'phone'=>'0987654321','role'=>'customer','status'=>'active']);
  $movies=[
   ['Lật Mặt 7: Một Điều Ước','lat-mat-7.svg','Gia đình, Tình cảm','Lý Hải','Thanh Hiền, Trương Minh Cường',138,-180,'showing'],
   ['Dune: Part Two','dune-2.svg','Hành động, Viễn tưởng','Denis Villeneuve','Timothée Chalamet, Zendaya',166,-210,'showing'],
   ['Mai','mai.svg','Tâm lý, Tình cảm','Trấn Thành','Phương Anh Đào, Tuấn Trần',131,-240,'showing'],
   ['Interstellar','interstellar.svg','Khoa học viễn tưởng','Christopher Nolan','Matthew McConaughey, Anne Hathaway',169,-120,'showing'],
   ['Avengers: Endgame','avengers-endgame.svg','Hành động, Siêu anh hùng','Anthony Russo, Joe Russo','Robert Downey Jr., Chris Evans',181,-90,'showing'],
   ['Spider-Man: No Way Home','spider-man.svg','Hành động, Phiêu lưu','Jon Watts','Tom Holland, Zendaya',148,-60,'showing'],
   ['Inside Out 2','inside-out-2.svg','Hoạt hình, Gia đình','Kelsey Mann','Amy Poehler, Maya Hawke',96,14,'upcoming'],
   ['How to Train Your Dragon','how-to-train-dragon.svg','Phiêu lưu, Fantasy','Dean DeBlois','Mason Thames, Nico Parker',125,28,'upcoming'],
   ['Thám Tử Lừng Danh Conan','conan.svg','Hoạt hình, Trinh thám','Chika Nagaoka','Minami Takayama, Wakana Yamazaki',110,35,'upcoming'],
   ['Avatar','avatar.svg','Phiêu lưu, Khoa học viễn tưởng','James Cameron','Sam Worthington, Zoe Saldana',162,45,'upcoming'],
  ];
  $movieModels=[];
  foreach($movies as [$title,$poster,$genre,$director,$actors,$duration,$days,$status]) $movieModels[$title]=Movie::updateOrCreate(['title'=>$title],['poster'=>$poster,'description'=>"Khám phá câu chuyện hấp dẫn của {$title} tại MovieTicket. Đặt vé trực tuyến, chọn ghế và tận hưởng trải nghiệm điện ảnh.",'genre'=>$genre,'director'=>$director,'actors'=>$actors,'duration'=>$duration,'release_date'=>Carbon::today()->addDays($days)->toDateString(),'trailer'=>'https://www.youtube.com/results?search_query='.urlencode($title).' trailer','status'=>$status]);
  $cinemas=[['MovieTicket Quận 1','123 Nguyễn Trãi, Quận 1, TP.HCM','02812345678'],['MovieTicket Thủ Đức','45 Võ Văn Ngân, TP. Thủ Đức, TP.HCM','02898765432']];
  foreach($cinemas as [$name,$address,$phone]){
   $cinema=Cinema::updateOrCreate(['name'=>$name],['address'=>$address,'phone'=>$phone,'status'=>'active']);
   for($ri=1;$ri<=2;$ri++){
    $room=Room::updateOrCreate(['cinema_id'=>$cinema->id,'name'=>'Phòng '.$ri],['rows'=>5,'seats_per_row'=>10,'status'=>'active']);
    for($r=0;$r<5;$r++) for($c=1;$c<=10;$c++) Seat::updateOrCreate(['room_id'=>$room->id,'seat_number'=>chr(65+$r).$c],['row'=>chr(65+$r),'column'=>$c]);
   }
  }
  Combo::updateOrCreate(['name'=>'Combo Solo'],['description'=>'1 bắp vừa + 1 nước vừa','price'=>55000,'status'=>true]);
  Combo::updateOrCreate(['name'=>'Combo Couple'],['description'=>'1 bắp lớn + 2 nước vừa','price'=>95000,'status'=>true]);
  Combo::updateOrCreate(['name'=>'Combo Family'],['description'=>'2 bắp lớn + 4 nước vừa','price'=>175000,'status'=>true]);
  Voucher::updateOrCreate(['code'=>'MOVIE10'],['name'=>'Giảm 10%','discount_type'=>'percent','discount_value'=>10,'min_order'=>100000,'max_discount'=>30000,'usage_limit'=>1000,'status'=>true,'starts_at'=>Carbon::now()->subDay(),'expires_at'=>Carbon::now()->addMonths(3)]);
  Voucher::updateOrCreate(['code'=>'WELCOME30K'],['name'=>'Giảm 30.000đ','discount_type'=>'fixed','discount_value'=>30000,'min_order'=>150000,'usage_limit'=>500,'status'=>true,'starts_at'=>Carbon::now()->subDay(),'expires_at'=>Carbon::now()->addMonths(2)]);
  $rooms=Room::where('status','active')->get(); $i=0;
  foreach($movieModels as $movie){ if($movie->status!=='showing') continue; $room=$rooms[$i++%$rooms->count()]; foreach([18,20,22] as $hour){$start=Carbon::today()->addDay()->setHour($hour)->setMinute(0); if($hour===22)$start->addDay(); Showtime::firstOrCreate(['movie_id'=>$movie->id,'room_id'=>$room->id,'start_time'=>$start],['end_time'=>$start->copy()->addMinutes($movie->duration+10),'price'=>$hour===18?85000:95000,'status'=>'active']);}}
 }
}
