<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $fillable=[
        'name',
        'slug',
        'decription',
        'price',
        'stock',
        'sku',
        'is_active'
    ];

    public function instock(){
        return $this->stock > 0;
    }
}
