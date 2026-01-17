<?php

namespace App\Models;

use App\Enum\Orderstatus;
use App\Enum\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $fillable = [
        'user_id',
        'status',
        'shipping_name',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_zipcode',
        'shipping_country',
        'shipping_phone',
        'subtotal',
        'tax',
        'shipping_cost',
        'total',
        'payment_method',
        'payment_status',
        'order_number',
        'notes',
        'transaction_id',
        'paid_at',
    ];
    protected $casts = [
        'status' => Orderstatus::class,
        'payment_status' => PaymentStatus::class,
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
   public static function generateOrderNumber()
    {
        // سيتولد رقم مثل: ORD-20260116-ABCD
        return 'ORD-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
    }
    // الناتج: ORD-202601-00125
    public function canBeCancelled()
    {
        return in_array($this->status, [
            Orderstatus::PENDING,
            Orderstatus::PAID,
        ]);
    }
    public function markAsPaid($transactionId)
    {
        $this->update([
            'status' => Orderstatus::PAID,
            'payment_status' => PaymentStatus::COMPLETED,
            'transction_id' => $transactionId,
            'paid_at' => now(),
        ]);
    }
    public function markAsFailed($transctionId)
    {
        $this->update([
            'payment_status' => PaymentStatus::FAILED,
        ]);
    }
}
