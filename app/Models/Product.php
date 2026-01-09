<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    //
    use Hasfactory, SoftDeletes;
    protected $fillable = [
        'name',
        'slug',
        'decription',
        'price',
        'stock',
        'sku',
        'is_active',
        'image'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'price'     => 'decimal:2',
    ];
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function instock()
    {
        return $this->stock > 0;
    }

    protected static function booted()
    {
        static::addGlobalScope('active', function ($query) {

            $query->where('is_active', true);
        });
    }

    public function scopepriceBetween($query, $min, $max)
    {
        return $query->wherebetween('price', [$min, $max]);
    }


    public function getFormattedNameAttribute()
    {
        return ucwords($this->name);
    }
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}
