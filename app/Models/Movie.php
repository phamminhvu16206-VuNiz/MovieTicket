<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $casts = ['release_date' => 'date'];

    protected $fillable = [
        'title',
        'poster',
        'description',
        'genre',
        'director',
        'actors',
        'duration',
        'release_date',
        'trailer',
        'status',
    ];

    // Một phim có nhiều suất chiếu
    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
