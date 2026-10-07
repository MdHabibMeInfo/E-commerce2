<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'order_number',
        'customer_name',
        'phone',
        'address',
        'city',
        'area',
        'payment_method',
        'payment_sender_phone',
        'payment_transaction_id',
        'payment_amount',
        'payment_proof_path',
        'payment_reviewed_by',
        'payment_reviewed_at',
        'notes',
        'subtotal',
        'delivery_fee',
        'delivery_discount',
        'discount',
        'coupon_id',
        'coupon_code',
        'total',
        'status',
        'payment_status',
        'shipping_status',
        'courier_name',
        'courier_tracking_code',
        'shipment_created_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'delivery_discount' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'payment_reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            $order->order_number ??= 'ORD-'.strtoupper(Str::random(8));
            $order->status ??= 'pending';
            $order->payment_status ??= 'pending';
            $order->shipping_status ??= 'pending';
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_reviewed_by');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(CourierShipment::class);
    }
}
