<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class BookingVoucher extends Model {
 use HasFactory;
 protected $fillable=['booking_id','voucher_id','discount_amount'];
 protected $casts=['discount_amount'=>'decimal:2'];
 public function booking(){return $this->belongsTo(Booking::class);}
 public function voucher(){return $this->belongsTo(Voucher::class);}
}
