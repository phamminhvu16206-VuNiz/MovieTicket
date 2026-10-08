<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Showtime extends Model
{
    use HasFactory;

    protected $casts = ['start_time' => 'datetime', 'end_time' => 'datetime', 'price' => 'decimal:2'];

    protected $fillable = [
        'movie_id',
        'room_id',
        'start_time',
        'end_time',
        'price',
        'status',
    ];

    // Suất chiếu thuộc về 1 Phim
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    // Suất chiếu thuộc về 1 Phòng
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Suất chiếu có nhiều Đơn đặt vé
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
