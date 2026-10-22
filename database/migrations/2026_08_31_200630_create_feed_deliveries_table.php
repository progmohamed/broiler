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
        Schema::create('feed_deliveries', function (Blueprint $table) {
            $table->id();
            $table->date('delivered_on')->index();
            $table->string('feed_type', 100);
            $table->decimal('weight_tons', 10, 3);
            $table->decimal('price_per_ton', 12, 2);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('tip', 12, 2)->default(0);
            $table->string('supplier', 150);
            $table->string('invoice_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_deliveries');
    }
};
