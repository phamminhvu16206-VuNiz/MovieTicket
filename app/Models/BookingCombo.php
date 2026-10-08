<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class BookingCombo extends Model {
 use HasFactory;
 protected $fillable=['booking_id','combo_id','quantity','unit_price'];
 protected $casts=['unit_price'=>'decimal:2'];
 public function booking(){return $this->belongsTo(Booking::class);}
 public function combo(){return $this->belongsTo(Combo::class);}
}
