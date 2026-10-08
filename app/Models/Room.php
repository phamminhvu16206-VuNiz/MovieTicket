<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'cinema_id',
        'name',
        'rows',
        'seats_per_row',
        'status',
    ];

    // Một phòng chiếu thuộc về 1 rạp
    public function cinema()
    {
        return $this->belongsTo(Cinema::class);
    }

    // Một phòng chiếu có nhiều ghế
    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    // Một phòng chiếu có nhiều suất chiếu
    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }
}
