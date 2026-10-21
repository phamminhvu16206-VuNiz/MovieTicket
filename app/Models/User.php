<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Một User có thể có nhiều đơn đặt vé
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function watchlistMovies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_watchlists')->withTimestamps();
    }
}
