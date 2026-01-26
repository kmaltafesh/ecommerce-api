<?php

use App\Enum\PaymentProvider;
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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('order_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->string('provider');
        $table->string('payment_intent_id')->nullable()->unique();

        $table->decimal('amount', 10, 2);
        $table->string('currency',3)->default('USD');
        $table->string('status');

        $table->json('metadata')->nullable();
        $table->dateTime('completed_at')->nullable();

        $table->timestamps();

        $table->index(['order_id', 'status']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
