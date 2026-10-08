<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'status',
    ];

    // Một rạp chiếu có nhiều phòng chiếu
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
