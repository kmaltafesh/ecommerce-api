<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::create('category_product', function (Blueprint $table) {
        $table->id();

        // ربط معرف المنتج
        $table->foreignId('product_id')
              ->constrained()
              ->onDelete('cascade'); // إذا حُذف المنتج، تُحذف روابطه بالأقسام تلقائياً

        // ربط معرف القسم
        $table->foreignId('category_id')
              ->constrained()
              ->onDelete('cascade'); // إذا حُذف القسم، تُحذف روابط المنتجات به تلقائياً

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
