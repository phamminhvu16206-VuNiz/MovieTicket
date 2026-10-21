<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $casts = [
        'total_amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'showtime_id',
        'booking_code',
        'total_amount',
        'status',
        'expires_at',
    ];

    public function scopeHoldingSeats(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereIn('status', ['paid', 'completed'])
                ->orWhere(function (Builder $query) {
                    $query->where('status', 'pending')
                        ->where(function (Builder $query) {
                            $query->where('expires_at', '>', now())
                                ->orWhere(function (Builder $query) {
                                    $query->whereNull('expires_at')
                                        ->where('created_at', '>', now()->subMinutes(3));
                                });
                        });
                });
        });
    }

    public function scopeExpiredPending(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where(function (Builder $query) {
                $query->where('expires_at', '<=', now())
                    ->orWhere(function (Builder $query) {
                        $query->whereNull('expires_at')
                            ->where('created_at', '<=', now()->subMinutes(3));
                    });
            });
    }

    // Đơn đặt vé thuộc về 1 Khách hàng
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Đơn đặt vé thuộc về 1 Suất chiếu
    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    // Đơn đặt vé chứa nhiều Chi tiết ghế đã chọn
    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function bookingCombos()
    {
        return $this->hasMany(BookingCombo::class);
    }

    public function bookingVouchers()
    {
        return $this->hasMany(BookingVoucher::class);
    }
}
