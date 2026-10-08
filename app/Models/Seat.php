<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'seat_number',
        'row',
        'column',
    ];

    // Một ghế thuộc về 1 phòng chiếu
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
