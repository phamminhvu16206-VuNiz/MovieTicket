<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Combo extends Model {
 use HasFactory;
 protected $fillable=['name','description','price','image','status'];
 protected $casts=['price'=>'decimal:2','status'=>'boolean'];
 public function bookingCombos(){return $this->hasMany(BookingCombo::class);}
}
