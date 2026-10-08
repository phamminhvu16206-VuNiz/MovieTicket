<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'seat_id',
        'price',
    ];

    // Chi tiết ghế thuộc về 1 Đơn đặt vé
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    // Chi tiết ghế thuộc về 1 Ghế cụ thể
    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }
}
