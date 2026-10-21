<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'discount_type', 'discount_value', 'min_order', 'max_discount', 'usage_limit', 'used_count', 'starts_at', 'expires_at', 'status'];

    protected $casts = ['discount_value' => 'decimal:2', 'min_order' => 'decimal:2', 'max_discount' => 'decimal:2', 'starts_at' => 'datetime', 'expires_at' => 'datetime', 'status' => 'boolean'];

    public function bookingVouchers()
    {
        return $this->hasMany(BookingVoucher::class);
    }

    public function isValidFor($amount): bool
    {
        return $this->status && $amount >= $this->min_order && (! $this->starts_at || now()->gte($this->starts_at)) && (! $this->expires_at || now()->lte($this->expires_at)) && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function calculateDiscount($amount): float
    {
        $discount = $this->discount_type === 'percent' ? $amount * ((float) $this->discount_value / 100) : (float) $this->discount_value;
        if ($this->max_discount) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return min($discount, $amount);
    }
}
