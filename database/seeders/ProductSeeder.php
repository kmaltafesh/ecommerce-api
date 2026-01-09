<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء 50 منتج وهمي
        Product::factory()->count(10)->create();
    }
}
