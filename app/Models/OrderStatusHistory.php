<?php

namespace App\Models;

use App\Enum\Orderstatus;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    //
    protected $fillable=[
        'order_id',
        'from_status',
        'to_status',
        'user_id',
        'note',
    ];
    protected $casts=[
        'from_status'=>Orderstatus::class,
        'to_status'=>Orderstatus::class,

    ];

    public function order(){
        return $this->belongsTo(Order::class);
    }
    public function changedBy(){
        return $this->belongsTo(User::class);
    }
}
