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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // ربط العنصر بالطلب (رقم الطلب)
            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // ربط العنصر بالمنتج (رقم المنتج)
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // اسم المنتج
            $table->string('product_name');

            // SKU للمنتج
            $table->string('product_sku')->nullable();

            // سعر الوحدة وقت الشراء
            $table->decimal('price', 10, 2);

            // الكمية
            $table->integer('quantity')->default(1);

            // الإجمالي لهذا الصنف (الكمية × السعر)
            $table->decimal('subtotal', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
