<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    // السماح بتعبئة هذه الحقول دفعة واحدة
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'price',
        'quantity',
        'subtotal',
    ];

    /**
     * العلاقة مع الطلب الأساسي
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * العلاقة مع المنتج الأصلي
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}